<?php

use Darkterminal\TursoPlatformAPI\Integrations\DatabaseUploader;
use Darkterminal\TursoPlatformAPI\Tests\FakeTransport;
use Nyholm\Psr7\Response;

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
}

it('rejects unreadable/missing dump file', function () {
    expect(fn () => (new DatabaseUploader('example.turso.io', 'tok'))->uploadDump('/no/such/file.db'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
});

it('preflight rejects journal_mode != wal', function () {
    writeTestDump('/tmp/opencode/pu_test_bad_journal.db', 'delete', 4096, 0, 'UTF-8');

    expect(fn () => (new DatabaseUploader('example.turso.io', 'tok'))->uploadDump('/tmp/opencode/pu_test_bad_journal.db'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
});

it('preflight rejects wrong page_size', function () {
    writeTestDump('/tmp/opencode/pu_test_bad_page.db', 'wal', 8192, 0, 'UTF-8');

    expect(fn () => (new DatabaseUploader('example.turso.io', 'tok'))->uploadDump('/tmp/opencode/pu_test_bad_page.db'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
});

it('uploads valid file and returns raw body via PSR-18 transport', function () {
    writeTestDump('/tmp/opencode/pu_test_ok.db', 'wal', 4096, 0, 'UTF-8');

    $fake = new FakeTransport(new Response(200, ['Content-Type' => 'application/json'], '{"msg":"Database is uploaded."}'));

    $body = (new DatabaseUploader('example.turso.io', 'db-token-123', 120, $fake))->uploadDump('/tmp/opencode/pu_test_ok.db');

    expect($body)->toContain('uploaded');

    $req = $fake->requests[0];
    expect($req->getMethod())->toBe('POST');
    expect($req->getUri()->getPath())->toBe('/v1/upload');
    expect($req->getHeaderLine('Authorization'))->toBe('Bearer db-token-123');
    expect($req->getHeaderLine('Content-Type'))->toBe('application/octet-stream');
    expect((int) $req->getHeaderLine('Content-Length'))->toBe(filesize('/tmp/opencode/pu_test_ok.db'));
    // upload must not leak platform token — only the DB token is present
    expect($req->getHeaderLine('Authorization'))->not->toContain('platform-token-xxx');
});

it('encryption headers are forwarded when encryption provided', function () {
    writeTestDump('/tmp/opencode/pu_test_enc.db', 'wal', 4096, 0, 'UTF-8');

    $fake = new FakeTransport(new Response(200, [], '{}'));

    (new DatabaseUploader('example.turso.io', 'db-token', 120, $fake))->uploadDump('/tmp/opencode/pu_test_enc.db',
        ['key' => 'base64key', 'cipher' => 'aes256gcm']);

    $req = $fake->requests[0];
    expect($req->getHeaderLine('x-turso-encryption-key'))->toBe('base64key');
    expect($req->getHeaderLine('x-turso-encryption-cipher'))->toBe('aes256gcm');
});

it('refuses non-https hostname before attaching token', function () {
    expect(fn () => new DatabaseUploader('http://example.turso.io', 'db-token'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
    expect(fn () => new DatabaseUploader('', 'db-token'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
});

it('maps 400 to ValidationException', function () {
    writeTestDump('/tmp/opencode/pu_test_bad.db', 'wal');
    $fake = new FakeTransport(new Response(400, ['Content-Type' => 'application/json'], '{"error":"bad dump"}'));

    expect(fn () => (new DatabaseUploader('example.turso.io', 't', 120, $fake))->uploadDump('/tmp/opencode/pu_test_bad.db'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ValidationException::class);
});

it('maps 401 to AuthenticationException', function () {
    writeTestDump('/tmp/opencode/pu_test_badauth.db', 'wal');
    $fake = new FakeTransport(new Response(401, ['Content-Type' => 'application/json'], '{"error":"unauthorized"}'));

    expect(fn () => (new DatabaseUploader('example.turso.io', 't', 120, $fake))->uploadDump('/tmp/opencode/pu_test_badauth.db'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\AuthenticationException::class);
});

it('maps 500 to ServerException', function () {
    writeTestDump('/tmp/opencode/pu_test_500.db', 'wal');
    $fake = new FakeTransport(new Response(500, ['Content-Type' => 'application/json'], '{"error":"boom"}'));

    expect(fn () => (new DatabaseUploader('example.turso.io', 't', 120, $fake))->uploadDump('/tmp/opencode/pu_test_500.db'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\ServerException::class);
});

it('maps transport failure to TransportException', function () {
    writeTestDump('/tmp/opencode/pu_test_cxerr.db', 'wal');

    expect(fn () => (new DatabaseUploader('example.turso.io', 't', 120, new \Darkterminal\TursoPlatformAPI\Tests\FailingTransport()))->uploadDump('/tmp/opencode/pu_test_cxerr.db'))
        ->toThrow(\Darkterminal\TursoPlatformAPI\Exceptions\TransportException::class);
});
