<?php
/** Stage22B fail-closed source gate. No SQL reads/writes here. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(2);
$root = dirname(__DIR__, 2);
function s22require(bool $condition, string $why): void {
    if (!$condition) throw new RuntimeException($why);
}
try {
    s22require(basename($root) === 'kamus_tolour_index_v1', 'Wrong project folder; expected kamus_tolour_index_v1');
    $expected = [
        'services/SearchService.php' => 'bd92f47062730315e21939ca2f0eb3c94dd8e28b30a9db93e816d0f75f30e95f',
        'services/IndexStage2Materializer.php' => '4bfbeb5bb14b97330000bee12cacc9517cf585f7c1c816bde8bb50392c489caa',
        'services/IndexStage3IncrementalMaintainer.php' => 'b2c00ba1e8f34d4ad0b14311702d05b83ff6c92678254a36beef49447269a02f',
        'models/Dictionary.php' => '016fc74ce2f560757302a757b5041d4ee3c773f3fe7c59fa3d95d070aba64c98',
        'tests/index_comparator_abc.php' => '3defe1d359cbd4ba8daea95c329e71748c8beb4609bab3c0fa75662d4521dd19',
    ];
    foreach ($expected as $relative => $sha) {
        $file = $root . '/' . $relative;
        s22require(is_file($file), 'Missing original file: ' . $relative);
        $actual = hash_file('sha256', $file);
        s22require(is_string($actual) && hash_equals($sha, $actual), 'Frozen SHA-256 mismatch: ' . $relative);
    }
    s22require(extension_loaded('pdo_mysql') && extension_loaded('mbstring'), 'Requires pdo_mysql and mbstring');
    echo "[PASS] Source frozen SHA-256 dan ekstensi PHP cocok.\n";
    echo "[SAFE] Runner asli memiliki whitelist dan berhenti jika work database sudah ada.\n";
} catch (Throwable $error) {
    fwrite(STDERR, '[STOP] ' . $error->getMessage() . "\n");
    exit(1);
}
