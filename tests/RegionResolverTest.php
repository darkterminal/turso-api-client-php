<?php

use Darkterminal\TursoPlatformAPI\Exceptions\HttpException;
use Darkterminal\TursoPlatformAPI\Exceptions\TransportException;
use Darkterminal\TursoPlatformAPI\Exceptions\UnexpectedResponseException;
use Darkterminal\TursoPlatformAPI\Integrations\RegionResolver;
use Darkterminal\TursoPlatformAPI\Tests\FakeTransport;
use Darkterminal\TursoPlatformAPI\Tests\FailingTransport;
use Nyholm\Psr7\Response;

it('returns server and client codes on 200', function () {
    $resolver = new RegionResolver(baseUrl: 'https://resolver.invalid', transport: new FakeTransport(
        new Response(200, ['Content-Type' => 'application/json'], '{"server":"aws-us-west-2","client":"lax"}'),
    ));

    expect($resolver->closest())->toBe(['server' => 'aws-us-west-2', 'client' => 'lax']);
});

it('uses default base URL when none provided', function () {
    $resolver = new RegionResolver();
    $prop = new ReflectionProperty(RegionResolver::class, 'baseUrl');
    $prop->setAccessible(true);
    expect($prop->getValue($resolver))->toBe('https://region.turso.io');
});

it('throws on non-200 response', function () {
    $resolver = new RegionResolver(baseUrl: 'https://resolver.invalid', transport: new FakeTransport(
        new Response(503, [], 'upstream unavailable'),
    ));

    expect(fn () => $resolver->closest())->toThrow(HttpException::class);
});

it('throws on missing required fields', function () {
    $resolver = new RegionResolver(baseUrl: 'https://resolver.invalid', transport: new FakeTransport(
        new Response(200, ['Content-Type' => 'application/json'], '{"server":"x"}'),
    ));

    expect(fn () => $resolver->closest())->toThrow(UnexpectedResponseException::class);
});

it('throws on transport failure', function () {
    $resolver = new RegionResolver(baseUrl: 'https://resolver.invalid', transport: new FailingTransport());

    expect(fn () => $resolver->closest())->toThrow(TransportException::class);
});
