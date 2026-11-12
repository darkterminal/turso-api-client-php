<?php

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Enums\MemberRole;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;
use Darkterminal\TursoPlatformAPI\Tests\FakeTransport;
use Nyholm\Psr7\Response;

if (!defined('ORG')) { define('ORG', 'acme'); }

function orgClient2(\Psr\Http\Message\ResponseInterface|FakeTransport $input): Client
{
    $transport = $input instanceof FakeTransport ? $input : new FakeTransport($input);
    return Client::builder()->withToken('test-token')->withOrganization(ORG)->withTransport($transport)->build();
}

it('listOrganizations hits GET /v1/organizations and returns bare array', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '[{"slug":"acme"}]'));
    $client = Client::builder()->withToken('t')->withTransport($transport)->build();

    $orgs = $client->organizations()->list();

    expect($orgs[0]['slug'])->toBe('acme')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations');
});

it('getOrganization unwraps {organization}', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"organization":{"name":"Acme","slug":"acme"}}'));
    $client = orgClient2($transport);

    $org = $client->organizations()->get();

    expect($org['name'])->toBe('Acme')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG);
});

it('updateOrganization only accepts allowlisted booleans', function () {
    $client = orgClient2(new Response(200, ['Content-Type' => 'application/json'], '{"organization":{}}'));

    expect(fn () => $client->organizations()->update(['bogus' => true]))->toThrow(ValidationException::class);
    expect(fn () => $client->organizations()->update(['overages' => 'yes']))->toThrow(ValidationException::class);

    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"organization":{"name":"Acme"}}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();
    $client->organizations()->update(['overages' => true, 'require_mfa' => false]);

    $body = json_decode((string) $transport->requests[0]->getBody(), true);
    expect($body)->toBe(['overages' => true, 'require_mfa' => false])
        ->and($transport->requests[0]->getMethod())->toBe('PATCH');
});

it('invoices passes type query; rejects unknown type', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"invoices":[]}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    expect(fn () => $client->organizations()->listInvoices('bogus'))->toThrow(ValidationException::class);

    $client->organizations()->listInvoices('issued');
    expect($transport->requests[0]->getUri()->getQuery())->toBe('type=issued');
});

it('subscription unwraps {subscription}', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"subscription":{"status":"active"}}'));
    $client = orgClient2($transport);

    $sub = $client->organizations()->getSubscription();

    expect($sub['status'])->toBe('active')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/subscription');
});

it('usage unwraps {organization}', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"organization":{"rows_read":1}}'));
    $client = orgClient2($transport);

    $usage = $client->organizations()->getUsage();

    expect($usage['rows_read'])->toBe(1);
});

it('members list unwraps {members}', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"members":[{"username":"a"}]}'));
    $client = orgClient2($transport);

    $members = $client->members()->list();

    expect($members[0]['username'])->toBe('a')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/members');
});

it('add member requires exactly one of email/username', function () {
    $client = orgClient2(new Response(200, ['Content-Type' => 'application/json'], '{"member":"u"}'));

    expect(fn () => $client->members()->add())->toThrow(ValidationException::class);
    expect(fn () => $client->members()->add('user', 'user@example.com'))->toThrow(ValidationException::class);

    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"member":"u","role":"member"}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();
    $client->members()->add(email: 'a@b.c', role: MemberRole::ADMIN);

    expect(json_decode((string) $transport->requests[0]->getBody(), true))->toBe(['role' => 'admin', 'email' => 'a@b.c']);
});

it('updateRole sends exactly {"role":"..."} and unwraps member object', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"member":{"username":"u","role":"viewer"}}'));
    $client = orgClient2($transport);

    $member = $client->members()->updateRole('u', MemberRole::VIEWER);

    expect($member['role'])->toBe('viewer');
    expect(json_decode((string) $transport->requests[0]->getBody(), true))->toBe(['role' => 'viewer'])
        ->and($transport->requests[0]->getMethod())->toBe('PATCH');
});

it('get member uses /members/{username} path and unwraps the Member object', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"member":{"username":"u","role":"member"}}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $member = $client->members()->get('u');

    expect($member['username'])->toBe('u');
    expect($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/members/u');
});

it('remove member uses /members/{username} path and returns the raw envelope', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"member":"u"}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();

    $out = $client->members()->remove('u');

    expect($out)->toBe(['member' => 'u']);
    expect($transport->requests[0]->getMethod())->toBe('DELETE')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v1/organizations/' . ORG . '/members/u');
});

it('invite requires exactly one of email/username; returns invited envelope', function () {
    $client = orgClient2(new Response(200, ['Content-Type' => 'application/json'], '{"invited":{}}'));

    expect(fn () => $client->invites()->create())->toThrow(ValidationException::class);
    expect(fn () => $client->invites()->create(email: 'a@b.c', username: 'u'))->toThrow(ValidationException::class);

    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"invited":{"email":"a@b.c"}}'));
    $client = Client::builder()->withToken('t')->withOrganization(ORG)->withTransport($transport)->build();
    $invited = $client->invites()->create(email: 'a@b.c');

    expect($invited['email'])->toBe('a@b.c');
    expect($transport->requests[0]->getUri()->getPath())->toBe('/v2/organizations/' . ORG . '/invites');
});

it('invites list hits v2 path and unwraps invites', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"invites":[{"email":"a@b.c"}]}'));
    $client = orgClient2($transport);

    $invites = $client->invites()->list();

    expect($invites[0]['email'])->toBe('a@b.c')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v2/organizations/' . ORG . '/invites');
});

it('invites delete uses v2 path and returns empty array on 200 No Content', function () {
    $transport = new FakeTransport(new Response(200, [], ''));
    $client = orgClient2($transport);

    $out = $client->invites()->delete('a@b.c');

    expect($out)->toBe([]);
    expect($transport->requests[0]->getMethod())->toBe('DELETE')
        ->and($transport->requests[0]->getUri()->getPath())->toBe('/v2/organizations/' . ORG . '/invites/a%40b.c');
});

it('auditLogs forwards page and page_size', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"audit_logs":[],"pagination":{"page":2,"page_size":10}}'));
    $client = orgClient2($transport);

    $logs = $client->auditLogs()->list(page: 2, pageSize: 10);

    expect($logs['pagination']['page'])->toBe(2);
    expect($transport->requests[0]->getUri()->getQuery())->toContain('page=2')->toContain('page_size=10');
    expect(fn () => $client->auditLogs()->list(page: 0))->toThrow(ValidationException::class);
});
