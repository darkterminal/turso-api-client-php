<?php

namespace Darkterminal\TursoPlatformAPI\Tests;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Test HTTP double — implements PSR-18 for deterministic client tests.
 */
final class FakeTransport implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    public function __construct(
        private readonly ResponseInterface $response,
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        return $this->response;
    }
}

/** Test double that always throws a network error. */
final class FailingTransport implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw new class('boom', $request) extends \RuntimeException implements \Psr\Http\Client\NetworkExceptionInterface {
            public function __construct(
                string $message,
                private readonly RequestInterface $innerRequest,
            ) {
                parent::__construct($message);
            }

            public function getRequest(): RequestInterface
            {
                return $this->innerRequest;
            }
        };
    }
}
