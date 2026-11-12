<?php

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\Authorization;
use Darkterminal\TursoPlatformAPI\Exceptions\ConfigurationException;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;
use Darkterminal\TursoPlatformAPI\Tests\FakeTransport;
use Nyholm\Psr7\Response;

if (!defined('ORG')) { define('ORG', 'acme'); }

function orgClient(\Psr\Http\Message\ResponseInterface $response): Client
{
    return Client::builder()
        ->withToken('test-token')
        ->withOrganization(ORG)
        ->withTransport(new FakeTransport($response))
        ->build();
}

it('requires an organization for organization-scoped resources', function () {
    $client = Client::builder()->withToken('t')->withTransport(
        new FakeTransport(new Response(200, [], '{}'))
    )->build();
    expect(fn () => $client->databases()->list())->toThrow(ConfigurationException::class);
});

it('lists databases with optional group and schema query params', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"databases":[{"Name":"a"},{"Name":"b"}]}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $result = $client->databases()->list(group: 'default', schema: 's');

    expect($result)->toHaveCount(2);

    $req = $transport->requests[0];
    expect($req->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/databases')
        ->and($req->getUri()->getQuery())->toContain('group=default')->toContain('schema=s');
});

it('creates a database and unwraps the database envelope', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"database":{"Name":"db1","DbId":"uuid-1"}}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $db = $client->databases()->create('db1', 'default');

    expect($db['Name'])->toBe('db1');

    $req = $transport->requests[0];
    expect($req->getMethod())->toBe('POST')
        ->and($req->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/databases')
        ->and(json_decode((string) $req->getBody(), true))->toBe(['name' => 'db1', 'group' => 'default']);
});

it('rejects invalid database names', function (string $name) {
    $client = orgClient(new Response(200, ['Content-Type' => 'application/json'], '{"database":{}}'));
    expect(fn () => $client->databases()->create($name, 'default'))->toThrow(ValidationException::class);
})->with(['Has_Upper', 'space name', 'a' . str_repeat('a', 64), 'bad!name', '']);

it('validates seed structure and type', function () {
    $client = orgClient(new Response(200, ['Content-Type' => 'application/json'], '{"database":{}}'));

    expect(fn () => $client->databases()->create('db1', 'default', ['type' => 'bogus']))->toThrow(ValidationException::class);
    expect(fn () => $client->databases()->create('db1', 'default', ['type' => 'database']))->toThrow(ValidationException::class); // missing name
    expect(fn () => $client->databases()->create('db1', 'default', ['type' => 'database_upload', 'extra' => 'x']))->toThrow(ValidationException::class); // unknown key
});

it('sends seed and size_limit when provided', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"database":{"Name":"db1"}}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $client->databases()->create('db1', 'default', seed: ['type' => 'database', 'name' => 'source-db'], sizeLimit: '1gb');

    $body = json_decode((string) $transport->requests[0]->getBody(), true);
    expect($body['seed'])->toBe(['type' => 'database', 'name' => 'source-db'])
        ->and($body['size_limit'])->toBe('1gb');
});

it('rejects unsupported configuration keys', function () {
    $client = orgClient(new Response(200, ['Content-Type' => 'application/json'], '{}'));
    expect(fn () => $client->databases()->updateConfiguration('db1', ['nonsense' => 1]))->toThrow(ValidationException::class);
});

it('sends allowed configuration keys', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"size_limit":"1000"}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $client->databases()->updateConfiguration('db1', ['block_writes' => true, 'allowed_ips' => ['10.0.0.0/8']]);

    $body = json_decode((string) $transport->requests[0]->getBody(), true);
    expect($transport->requests[0]->getMethod())->toBe('PATCH')
        ->and($body)->toBe(['block_writes' => true, 'allowed_ips' => ['10.0.0.0/8']]);
});

it('creates a token with authorization query params and read_attach body', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"jwt":"tok123"}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $jwt = $client->databases()->createToken('db1', expiration: 'never', authorization: Authorization::READ_ONLY, attachDatabases: ['other-db']);

    expect($jwt)->toBe('tok123');

    $req = $transport->requests[0];
    expect($req->getUri()->getQuery())->toContain('expiration=never')->toContain('authorization=read-only');
    expect(json_decode((string) $req->getBody(), true))->toBe([
        'permissions' => ['read_attach' => ['databases' => ['other-db']]],
    ]);
});

it('rejects an empty attach database name', function () {
    $client = orgClient(new Response(200, ['Content-Type' => 'application/json'], '{"jwt":"x"}'));
    expect(fn () => $client->databases()->createToken('db1', attachDatabases: ['']))->toThrow(ValidationException::class);
});

it('usage forwards from/to query params', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"database":{"uuid":"x"}}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $result = $client->databases()->usage('db1', from: '2026-01-01', to: '2026-02-01');

    expect($result['uuid'])->toBe('x');
    expect($transport->requests[0]->getUri()->getQuery())->toContain('from=2026-01-01')->toContain('to=2026-02-01');
});

it('delete unwraps scalar-string "database" envelope (contract-correct shape)', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"database":"db1"}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $out = $client->databases()->delete('db1');

    expect($out)->toBe('db1')
        ->and($transport->requests[0]->getMethod())->toBe('DELETE')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/databases/db1');
});

it('instances endpoints use correct paths', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"instances":[{"uuid":"i"}]}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $out = $client->databases()->listInstances('db1');
    expect($out[0]['uuid'])->toBe('i');
    expect($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/databases/db1/instances');
});

it('stats returns top_queries', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"top_queries":[{"query":"SELECT 1"}]}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $out = $client->databases()->stats('db1');
    expect($out[0]['query'])->toBe('SELECT 1');
});
