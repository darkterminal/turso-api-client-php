<?php

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\ApiTokenScope;
use Darkterminal\TursoPlatformAPI\Exceptions\ConfigurationException;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;
use Darkterminal\TursoPlatformAPI\Tests\FakeTransport;
use Nyholm\Psr7\Response;

if (!defined('ORG')) {
    define('ORG', 'acme');
}
const ORG_ID = 'd0b1a2c3-0000-4000-8000-000000000001';

it('personal api tokens list hits /v1/auth/api-tokens and unwraps tokens', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"tokens":[{"name":"t1"}]}'));
    $client = Client::builder()->withToken('tk')->withTransport($transport)->build();

    $tokens = $client->apiTokens()->list();

    expect($tokens[0]['name'])->toBe('t1')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/auth/api-tokens');
});

it('personal api tokens create validates group-scopes rules and unwraps jwt envelope', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"name":"my-tok","id":"abc","token":"jwt-tok"}'));
    $client = Client::builder()->withToken('tk')->withTransport($transport)->build();

    expect(fn () => $client->apiTokens()->create('tok', group: 'default'))->toThrow(ValidationException::class);
    expect(fn () => $client->apiTokens()->create('tok', organization: ORG, group: 'default'))->toThrow(ValidationException::class);

    $result = $client->apiTokens()->create('tok', organization: ORG, group: 'default', scopes: [ApiTokenScope::DB_CREATE]);

    expect($result['token'])->toBe('jwt-tok');
    $body = json_decode((string) $transport->requests[0]->getBody(), true);
    expect($body)->toBe(['organization' => ORG, 'group' => 'default', 'scopes' => ['db:create']]);
});

it('personal api tokens revoke hits DELETE with path-encoded tokenName', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"token":"x"}'));
    $client = Client::builder()->withToken('tk')->withTransport($transport)->build();

    $client->apiTokens()->revoke('tok-name');
    expect($transport->requests[0]->getUri()->getPath())->toBe('/v1/auth/api-tokens/tok-name');

    expect(fn () => $client->apiTokens()->revoke(''))->toThrow(ValidationException::class);
    expect(fn () => $client->apiTokens()->revoke('tok/with/slashes'))->toThrow(ValidationException::class);
});

it('organization api tokens list and revoke use org path', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"tokens":[{"name":"ot"}]}'));

    $client = Client::builder()->withToken('tk')->withOrganization(ORG)->withTransport($transport)->build();

    $tokens = $client->organizationApiTokens()->list();
    expect($tokens[0]['name'])->toBe('ot');

    $client = Client::builder()->withToken('tk')->withOrganization(ORG)->withTransport(
        new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"token":"x"}'))
    )->build();
    $client->organizationApiTokens()->revoke('some_id');
    expect($transport)->not->toBeNull();
});

it('validate hits /v1/auth/validate and returns exp payload', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"exp":1893456000}'));
    $client = Client::builder()->withToken('tk')->withTransport($transport)->build();

    $exp = $client->auth()->validate();

    expect($exp['exp'])->toBe(1893456000)
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/auth/validate');
});

it('locations list unwraps locations array', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"locations":[{"loc":"lhr"}]}'));
    $client = Client::builder()->withToken('tk')->withTransport($transport)->build();

    $locs = $client->locations()->list();

    expect($locs[0]['loc'])->toBe('lhr');
});

it('recovery requires organizationId configuration', function () {
    $client = Client::builder()->withToken('tk')->withTransport(
        new FakeTransport(new Response(200, [], ''))
    )->build();

    expect(fn () => $client->recovery()->listDeletedDatabases())->toThrow(ConfigurationException::class);
});

it('listDeletedDatabases forwards deleted=true and optional params', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"databases":[],"pagination":{"next":"abc"}}'));
    $client = Client::builder()->withToken('tk')->withOrganizationId(ORG_ID)->withTransport($transport)->build();

    $result = $client->recovery()->listDeletedDatabases(search: 'foo', limit: 10, cursor: 'cur');

    expect($result['pagination']['next'])->toBe('abc');
    $q = $transport->requests[0]->getUri()->getQuery();
    expect($q)->toContain('deleted=true')->toContain('search=foo')->toContain('limit=10')->toContain('cursor=cur');
});

it('restoreDatabase sends name (if given) and unwraps database envelope', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"database":{"Name":"restored"}}'));
    $client = Client::builder()->withToken('tk')->withOrganizationId(ORG_ID)->withTransport($transport)->build();

    $out = $client->recovery()->restoreDatabase('db-uuid', name: 'new-name');

    expect($out['Name'])->toBe('restored');
    expect(json_decode((string) $transport->requests[0]->getBody(), true))->toBe(['name' => 'new-name']);
    expect($transport->requests[0]->getUri()->getPath())->toBe('/v3/organizations/' . ORG_ID . '/databases/db-uuid/restore');

    expect(fn () => $client->recovery()->restoreDatabase(''))->toThrow(ValidationException::class);
});

it('listDeletedGroups hits v3 path with limit+cursor; restoreGroup hits POST restore', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"groups":[],"pagination":{}}'));
    $client = Client::builder()->withToken('tk')->withOrganizationId(ORG_ID)->withTransport($transport)->build();

    $client->recovery()->listDeletedGroups(limit: 5, cursor: 'c1');
    expect($transport->requests[0]->getUri()->getPath())->toBe('/v3/organizations/' . ORG_ID . '/deleted-groups');
    expect($transport->requests[0]->getUri()->getQuery())->toBe('limit=5&cursor=c1');

    $client->recovery()->restoreGroup('group-uuid');
    expect($transport->requests[1]->getMethod())->toBe('POST');
    expect($transport->requests[1]->getUri()->getPath())->toBe('/v3/organizations/' . ORG_ID . '/groups/group-uuid/restore');
    expect(fn () => $client->recovery()->restoreGroup(''))->toThrow(ValidationException::class);
});
