<?php

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\Authorization;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;
use Darkterminal\TursoPlatformAPI\Tests\FakeTransport;
use Nyholm\Psr7\Response;

if (!defined('ORG')) { define('ORG', 'acme'); }

it('list hits /v1/organizations/{ORG}/groups and unwraps groups', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"groups":[{"name":"default"}]}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $groups = $client->groups()->list();

    expect($groups[0]['name'])->toBe('default');
    expect($transport->requests[0]->getMethod())->toBe('GET')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/groups');
});

it('validates extensions strictly (enum + "all")', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"group":{"name":"g1"}}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    expect(fn () => $client->groups()->create('g1', 'l1', ['bogus-ext']))->toThrow(ValidationException::class);
    expect(fn () => $client->groups()->create('g1', 'l1', []))->toThrow(ValidationException::class);

    $client->groups()->create('g2', 'l1', 'all');
    $body = json_decode((string) $transport->requests[0]->getBody(), true);
    expect($body['extensions'])->toBe('all');

    $client->groups()->create('g3', 'l1', ['vector', 'uuid']);
    $body = json_decode((string) $transport->requests[1]->getBody(), true);
    expect($body['extensions'])->toBe(['vector', 'uuid']);
});

it('unwraps {group} envelope for get/delete/unarchive/addLocation/removeLocation', function (string $method, array $responseBody) {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], json_encode($responseBody)));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $out = match ($method) {
        'get' => $client->groups()->get('g1'),
        'delete' => $client->groups()->delete('g1'),
        'unarchive' => $client->groups()->unarchive('g1'),
        'addLocation' => $client->groups()->addLocation('g1', 'aws-eu-west-1'),
        'removeLocation' => $client->groups()->removeLocation('g1', 'aws-eu-west-1'),
    };

    expect($out)->toBe(['name' => 'g1']);
})->with([
    ['get', ['group' => ['name' => 'g1']]],
    ['delete', ['group' => ['name' => 'g1']]],
    ['unarchive', ['group' => ['name' => 'g1']]],
    ['addLocation', ['group' => ['name' => 'g1']]],
    ['removeLocation', ['group' => ['name' => 'g1']]],
]);

it('configuration only accepts delete_protection', function () {
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport(
        new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"delete_protection": true}'))
    )->build();

    expect(fn () => $client->groups()->updateConfiguration('g1', ['bogus' => true]))->toThrow(ValidationException::class);
    expect(fn () => $client->groups()->updateConfiguration('g1', ['delete_protection' => 'yes']))->toThrow(ValidationException::class);

    $out = $client->groups()->updateConfiguration('g1', ['delete_protection' => true]);
    expect($out['delete_protection'])->toBeTrue();
});

it('transfer sends organization slug in body and returns bare Group', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"name":"g1","uuid":"u1"}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $out = $client->groups()->transfer('g1', 'targetorg');

    expect($out)->toBe(['name' => 'g1', 'uuid' => 'u1']);
    expect(json_decode((string) $transport->requests[0]->getBody(), true))->toBe(['organization' => 'targetorg']);
});

it('group token creation uses expiration/authorization query and returns jwt', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"jwt":"grp-tok"}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $jwt = $client->groups()->createToken('g1', expiration: 'never', authorization: Authorization::READ_ONLY, attachDatabases: ['db1']);

    expect($jwt)->toBe('grp-tok');

    $req = $transport->requests[0];
    expect($req->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/groups/g1/auth/tokens')
        ->and($req->getUri()->getQuery())->toContain('expiration=never')->toContain('authorization=read-only');
    expect(json_decode((string) $req->getBody(), true))->toBe([
        'permissions' => ['read_attach' => ['databases' => ['db1']]],
    ]);
});

it('rotate and update use no-content endpoints', function () {
    foreach (['invalidateTokens', 'update'] as $op) {
        $transport = new FakeTransport(new Response(200, [], ''));
        $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();
        $out = $op === 'invalidateTokens' ? $client->groups()->invalidateTokens('g1') : $client->groups()->update('g1');
        expect($out)->toBe([]);
        $path = $op === 'invalidateTokens' ? '/auth/rotate' : '/update';
        expect($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/groups/g1' . $path);
    }
});
