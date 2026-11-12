<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Exceptions;

/** Low-level transport failure (DNS, TCP, TLS, timeouts) — the request never reached a response. */
class TransportException extends PlatformException {}
