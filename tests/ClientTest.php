<?php

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Client\ClientBuilder;
use Darkterminal\TursoPlatformAPI\Client\Configuration;
use Darkterminal\TursoPlatformAPI\Exceptions\AuthenticationException;
use Darkterminal\TursoPlatformAPI\Exceptions\ConfigurationException;
use Darkterminal\TursoPlatformAPI\Exceptions\HttpException;
use Darkterminal\TursoPlatformAPI\Exceptions\NotFoundException;
use Darkterminal\TursoPlatformAPI\Exceptions\ServerException;
use Darkterminal\TursoPlatformAPI\Exceptions\TransportException;
use Darkterminal\TursoPlatformAPI\Tests\FakeTransport;
use Darkterminal\TursoPlatformAPI\Tests\FailingTransport;
use Nyholm\Psr7\Response;

it('requires a non-empty token', function () {
    Client::builder()->build();
})->throws(ConfigurationException::class);

it('builds a client with defaults', function () {
    $client = Client::builder()->withToken('token-123')->build();
    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->configuration()->baseUrl)->toBe('https://api.turso.tech')
        ->and($client->configuration()->token)->toBe('token-123');
});

it('sends Authorization Bearer and decodes JSON', function () {
    $transport = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"user":{"id":"u_1","username":"ada"}}'));
    $client = Client::builder()->withToken('secret-token')->withTransport($transport)->build();

    $result = $client->user()->get();

    expect($result['user']['username'])->toBe('ada')
        ->and($transport->requests)->toHaveCount(1);

    $request = $transport->requests[0];
    expect($request->getHeaderLine('Authorization'))->toBe('Bearer secret-token')
        ->and($request->getUri()->getPath())->toBe('/v1/user');
});

it('maps 401 to AuthenticationException', function () {
    $transport = new FakeTransport(new Response(401, ['Content-Type' => 'application/json'], '{"error":"invalid token","errorCode":"AUTH_INVALID"}'));
    $client = Client::builder()->withToken('bad')->withTransport($transport)->build();
    $client->user()->get();
})->throws(AuthenticationException::class);

it('maps 404 to NotFoundException', function () {
    $transport = new FakeTransport(new Response(404, ['Content-Type' => 'application/json'], '{"error":"nope"}'));
    $client = Client::builder()->withToken('bad')->withTransport($transport)->build();
    $client->user()->get();
})->throws(NotFoundException::class);

it('maps 500 to ServerException', function () {
    $transport = new FakeTransport(new Response(500, ['Content-Type' => 'application/json'], '{"error":"kaput"}'));
    $client = Client::builder()->withToken('t')->withTransport($transport)->build();
    $client->user()->get();
})->throws(ServerException::class);

it('maps non-2xx without an "error" key to a generic HttpException', function () {
    $transport = new FakeTransport(new Response(400, ['Content-Type' => 'application/json'], '{"msg":"weird"}'));
    $client = Client::builder()->withToken('t')->withTransport($transport)->build();
    $client->user()->get();
})->throws(HttpException::class);

it('maps transport-level failures to TransportException', function () {
    $client = Client::builder()->withToken('secret-token')->withTransport(new FailingTransport())->build();
    $client->user()->get();
})->throws(TransportException::class);

it('never leaks the token in exception messages', function () {
    $transport = new FakeTransport(new Response(401, ['Content-Type' => 'application/json'], '{"error":"invalid token"}'));
    $client = Client::builder()->withToken('super-secret-leaked-token')->withTransport($transport)->build();

    try {
        $client->user()->get();
        $this->fail('expected exception');
    } catch (\Throwable $e) {
        expect(str_contains($e->getMessage(), 'super-secret-leaked-token'))->toBeFalse();
    }
});
