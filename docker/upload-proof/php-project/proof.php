<?php

declare(strict_types=1);

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Integrations\DatabaseUploader;

require __DIR__ . '/vendor/autoload.php';

$checks = [];
$ok = function (string $name, bool $cond) use (&$checks): void {
    $checks[$name] = $cond;
    echo ($cond ? '  PASS ' : '  FAIL ') . $name . PHP_EOL;
};

$token = getenv('TURSO_PLATFORM_API_TOKEN') ?: '';

if ($token === '') {
    echo 'UPLOAD-PROOF: {"skipped":"missing TURSO_PLATFORM_API_TOKEN — see docker/upload-proof/proof.sh"}' . PHP_EOL;
    exit(2);
}

$orgSlug = getenv('TURSO_PLATFORM_ORGANIZATION') ?: 'softwarefreestylengineer';
$client = Client::builder()
    ->withToken($token)
    ->withOrganization($orgSlug)
    ->build();

try {
    // 1) list organization to confirm token works
    $orgs = $client->organizations()->list();
    $ok('organizations_list_works', is_array($orgs));
} catch (Throwable $e) {
    $ok('organizations_list_works', false);
    $ok('fatal', false);
    printReport($checks);
    exit(1);
}

// 2) pick a group (first available)
try {
    $groups = $client->groups()->list();
    $groupName = $groups[0]['name'] ?? 'development';
    $ok('groups_list_works', is_string($groupName) && $groupName !== '');
} catch (Throwable $e) {
    printReport($checks + ['fatal' => false]);
    exit(1);
}

// 3) create scratch database with database_upload seed
$name = 'uploadproof-' . bin2hex(random_bytes(4));
$scratch = null;
try {
    $scratch = $client->databases()->create($name, $groupName, seed: ['type' => 'database_upload']);
    $hostname = $scratch['Hostname'] ?? '';
    $ok('scratch_db_created', is_string($hostname) && $hostname !== '');
    $dbId = $scratch['DbId'] ?? null;
} catch (Throwable $e) {
    $ok('scratch_db_created', false);
    $ok('fatal', false);
    printReport($checks);
    exit(1);
}

// 4) mint a database-scoped token
$dbToken = null;
try {
    $dbToken = $client->databases()->createToken($name, 'never', \Darkterminal\TursoPlatformAPI\Enums\Authorization::FULL_ACCESS);
    $ok('db_token_minted', is_string($dbToken) && $dbToken !== '');
} catch (Throwable $e) {
    $ok('db_token_minted', false);
}

// 5) create a small SQLite dump meeting upload requirements
$dump = '/tmp/upload-proof-dump.db';
@unlink($dump);
@unlink($dump . '-wal');
@unlink($dump . '-shm');
$pdo = new PDO('sqlite:' . $dump);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA journal_mode=WAL");
$pdo->exec("PRAGMA page_size=4096");
$pdo->exec("PRAGMA auto_vacuum=0");
$pdo->exec("PRAGMA encoding='UTF-8'");
$pdo->exec("CREATE TABLE upload_smoke (id INTEGER PRIMARY KEY, name TEXT)");
$pdo->exec("INSERT INTO upload_smoke (name) VALUES ('pswf-upload-proof')");
$pdo = null;
$ok('dump_db_built', file_exists($dump) && filesize($dump) > 0);

// 6) upload the dump
$uploader = new DatabaseUploader($hostname, $dbToken);
$body = null;
try {
    $body = (string) $uploader->uploadDump($dump);
    $ok('upload_succeeded', str_contains($body, 'uploaded'));
} catch (Throwable $e) {
    $ok('upload_succeeded', false);
    // try to delete scratch DB on failure so it doesn't linger
    try { $client->databases()->delete($name); } catch (Throwable $ignored) {}
    printReport($checks);
    exit(1);
}

// 7) verify by querying the uploaded DB via Hrana /v2/pipeline
$hranaBody = json_encode([
    'requests' => [
        ['type' => 'execute', 'stmt' => ['sql' => "SELECT name FROM upload_smoke"]],
        ['type' => 'close'],
    ],
]);
$ch = curl_init("https://{$hostname}/v2/pipeline");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $hranaBody,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $dbToken, 'Content-Type: application/json'],
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

$rows = null;
if (is_string($response)) {
    $json = json_decode($response, true);
    $rows = $json['results'][0]['response']['result']['rows'] ?? null;
}
$found = is_array($rows) && count($rows) === 1 && ($rows[0][0]['value'] ?? null) === 'pswf-upload-proof';
$ok('uploaded_data_readable', $found);

// 8) cleanup scratch database
try {
    $client->databases()->delete($name);
    $ok('scratch_db_deleted', true);
} catch (Throwable $e) {
    echo '  DELETE error (' . get_class($e) . '): ' . $e->getMessage() . PHP_EOL;
    $ok('scratch_db_deleted', false);
}

printReport($checks);
exit((count(array_filter($checks, fn ($v) => $v === false)) === 0) ? 0 : 1);

function printReport(array $checks): void
{
    $pass = count(array_filter($checks));
    $fail = count($checks) - $pass;
    echo 'UPLOAD-PROOF: ' . json_encode(['ok' => $pass, 'fail' => $fail, 'all_ok' => $fail === 0, 'checks' => $checks]) . PHP_EOL;
}
