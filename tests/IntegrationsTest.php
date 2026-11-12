<?php

use Darkterminal\TursoPlatformAPI\Integrations\DatabaseUploader;
use Darkterminal\TursoPlatformAPI\Tests\FakeTransport;
use Nyholm\Psr7\Response;

/**
 * Create a unique temp file path per test invocation so tests are
 * hermetic on any host (CI runners lack /tmp/opencode).
 */
function testDumpPath(string $name): string
{
    return sys_get_temp_dir() . '/pu_test_' . $name . '_' . bin2hex(random_bytes(4)) . '.db';
}

function unlinkTestDump(string $path): void
{
    foreach ([$path, $path . '-wal', $path . '-shm', $path . '-journal'] as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

function writeTestDump(string $path, string $journalMode = 'wal', int $pageSize = 4096, int $autoVacuum = 0, string $encoding = "UTF-8"): void
{
    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($pageSize !== 4096) {
        $db->exec('PRAGMA page_size=' . $pageSize);
    }
    // journal_mode query syntax varies; use direct exec after open
    if ($journalMode !== 'wal') {
        // Cannot set WAL->DELETE easily on fresh db, leave default journal_mode=delete.
    }
    if ($autoVacuum !== 0) {
        $db->exec('PRAGMA auto_vacuum=' . $autoVacuum);
    }

    $db->exec('PRAGMA journal_mode=' . strtoupper($journalMode));
    $db->exec('DROP TABLE IF EXISTS t');
    $db->exec('CREATE TABLE t (id INTEGER PRIMARY KEY, name TEXT)');
    $db->exec("INSERT INTO t (name) VALUES ('alpha')");

    $db = null;
}

it('rejects unreadable/missing dump file', function () {
    expect(fn () => (new DatabaseUploader('example.turso.io', 'tok'))->uploadDump('/no/such/file.db'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
});

it('preflight rejects journal_mode != wal', function () {
    $path = testDumpPath('bad_journal');
    writeTestDump($path, 'delete', 4096, 0, 'UTF-8');

    try {
        expect(fn () => (new DatabaseUploader('example.turso.io', 'tok'))->uploadDump($path))
            ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
    } finally {
        unlinkTestDump($path);
    }
});

it('preflight rejects wrong page_size', function () {
    $path = testDumpPath('bad_page');
    writeTestDump($path, 'wal', 8192, 0, 'UTF-8');

    try {
        expect(fn () => (new DatabaseUploader('example.turso.io', 'tok'))->uploadDump($path))
            ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
    } finally {
        unlinkTestDump($path);
    }
});

it('uploads valid file and returns raw body via PSR-18 transport', function () {
    $path = testDumpPath('ok');
    writeTestDump($path, 'wal', 4096, 0, 'UTF-8');

    $fake = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"msg":"Database is uploaded."}'));

    try {
        $body = (new DatabaseUploader('example.turso.io', 'db-token-123', 120, $fake))->uploadDump($path);

        expect($body)->toContain('uploaded');

        $req = $fake->requests[0];
        expect($req->getMethod())->toBe('POST');
        expect($req->getUri()->getPath())->toBe('/v1/upload');
        expect($req->getHeaderLine('Authorization'))->toBe('Bearer db-token-123');
        expect($req->getHeaderLine('Content-Type'))->toBe('application/octet-stream');
        expect((int) $req->getHeaderLine('Content-Length'))->toBe(filesize($path));
        // upload must not leak platform token — only the DB token is present
        expect($req->getHeaderLine('Authorization'))->not->toContain('platform-token-xxx');
    } finally {
        unlinkTestDump($path);
    }
});

it('encryption headers are forwarded when encryption provided', function () {
    $path = testDumpPath('enc');
    writeTestDump($path, 'wal', 4096, 0, 'UTF-8');

    $fake = new FakeTransport(new Response(200, [], '{}'));

    try {
        (new DatabaseUploader('example.turso.io', 'db-token', 120, $fake))->uploadDump($path,
            ['key' => 'base64key', 'cipher' => 'aes256gcm']);

        $req = $fake->requests[0];
        expect($req->getHeaderLine('x-turso-encryption-key'))->toBe('base64key');
        expect($req->getHeaderLine('x-turso-encryption-cipher'))->toBe('aes256gcm');
    } finally {
        unlinkTestDump($path);
    }
});

it('refuses non-https hostname before attaching token', function () {
    expect(fn () => new DatabaseUploader('http://example.turso.io', 'db-token'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
    expect(fn () => new DatabaseUploader('', 'db-token'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
});

it('maps 400 to ValidationException', function () {
    $path = testDumpPath('bad');
    writeTestDump($path, 'wal');
    $fake = new FakeTransport(new Response(400, ['Content-Type' => 'application/json'], '{"error":"bad dump"}'));

    try {
        expect(fn () => (new DatabaseUploader('example.turso.io', 't', 120, $fake))->uploadDump($path))
            ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
    } finally {
        unlinkTestDump($path);
    }
});

it('maps 401 to AuthenticationException', function () {
    $path = testDumpPath('badauth');
    writeTestDump($path, 'wal');
    $fake = new FakeTransport(new Response(401, ['Content-Type' => 'application/json'], '{"error":"unauthorized"}'));

    try {
        expect(fn () => (new DatabaseUploader('example.turso.io', 't', 120, $fake))->uploadDump($path))
            ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\AuthenticationException::class);
    } finally {
        unlinkTestDump($path);
    }
});

it('maps 500 to ServerException', function () {
    $path = testDumpPath('500');
    writeTestDump($path, 'wal');
    $fake = new FakeTransport(new Response(500, ['Content-Type' => 'application/json'], '{"error":"boom"}'));

    try {
        expect(fn () => (new DatabaseUploader('example.turso.io', 't', 120, $fake))->uploadDump($path))
            ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ServerException::class);
    } finally {
        unlinkTestDump($path);
    }
});

it('maps transport failure to TransportException', function () {
    $path = testDumpPath('cxerr');
    writeTestDump($path, 'wal');

    try {
        expect(fn () => (new DatabaseUploader('example.turso.io', 't', 120, new \Darkterminal\TursoPlatformAPI\Tests\FailingTransport()))->uploadDump($path))
            ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\TransportException::class);
    } finally {
        unlinkTestDump($path);
    }
});
