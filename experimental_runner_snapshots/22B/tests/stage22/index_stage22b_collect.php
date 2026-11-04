<?php
/** Audits and archives a fresh Stage22B pilot report. Never connects to database. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(2);
$root = dirname(__DIR__, 2);
$dest = __DIR__ . '/reports';
function must(bool $value, string $message): void { if (!$value) throw new RuntimeException($message); }
try {
    must(basename($root) === 'kamus_tolour_index_v1', 'Unexpected project name');
    must(count($argv) === 2 && str_starts_with($argv[1], '--log='), 'Expected --log=PATH');
    $log = realpath(substr($argv[1], 6));
    must($log !== false && is_file($log), 'Log not found');
    $base = realpath($dest);
    must($base !== false && str_starts_with(str_replace('\\', '/', $log), str_replace('\\', '/', $base) . '/'), 'Log outside Stage22 reports');
    $out = (string) file_get_contents($log);
    must(str_contains($out, 'ABC --ABC-BENCHMARK: PASS'), 'Runner log lacks PASS marker');
    must((bool) preg_match('/^JSON:\s*(.+?\.json)\s*$/m', $out, $match), 'Runner output does not identify JSON');
    $reportPath = realpath(trim($match[1]));
    $historicalReports = realpath($root . '/tests/reports');
    must($historicalReports !== false && $reportPath !== false, 'Runner JSON missing');
    must(str_starts_with(str_replace('\\','/',$reportPath),str_replace('\\','/',$historicalReports).'/'), 'Runner JSON outside historical reports directory');
    must(str_starts_with(basename($reportPath), 'INDEX_ABC_BENCHMARK_B1_TARGETED_'), 'Unexpected original report filename');
    $data = json_decode((string) file_get_contents($reportPath), true, 512, JSON_THROW_ON_ERROR);
    must(is_array($data), 'Report not an object');
    foreach (['mode'=>'--abc-benchmark','batch'=>1,'reps'=>4,'arms'=>'targeted','scale'=>1,'status'=>'PASS','source_modified'=>false] as $key=>$expected) {
        must(($data[$key] ?? null) === $expected, 'Report mismatch at '.$key);
    }
    must(($data['main_database'] ?? '') === 'kamus_tolour_index_v2', 'Unexpected main database');
    must(($data['prepared_reference_database'] ?? '') === 'kamus_tolour_index_stage21_s1_sandbox', 'Unexpected reference database');
    must(($data['disposable_work_database'] ?? '') === 'kamus_tolour_index_abc_s1_work_sandbox', 'Unexpected disposable database');
    must(($data['benchmark']['all_pair_digests_identical'] ?? false) === true, 'Pair parity report false');
    must(($data['benchmark']['all_work_databases_disposed'] ?? false) === true, 'Work database disposal report false');
    $runs=$data['benchmark']['runs'] ?? null;
    must(is_array($runs) && count($runs) === 4, 'Expected exactly four paired cases');
    $expectedFamilies=['word','meaning','category','unpublish'];
    $families=[];
    foreach ($runs as $index=>$run) {
        must(($run['rep'] ?? null) === $index+1, 'Run numbering mismatch');
        must(($run['parity'] ?? '') === 'PASS', 'Pair parity failed');
        must(($run['batch_size'] ?? null) === 1, 'Unexpected batch size');
        $mutationMix=$run['mutation_mix'] ?? null;
        must(is_array($mutationMix) && count($mutationMix) === 1, 'Expected one mutation family per run');
        $family=array_key_first($mutationMix);
        must(($mutationMix[$family] ?? null) === 1, 'Expected one mutation');
        $families[]=$family;
        $methods = $run['methods'] ?? null;
        must(is_array($methods) && count($methods) === 2, 'Expected B and C only');
        $digests=[];
        foreach (['selective_refresh','differential'] as $arm) {
            $method=$methods[$arm] ?? null;
            must(is_array($method), 'Missing method: '.$arm);
            must(($method['transaction'] ?? '') === 'COMMITTED_ON_DISPOSABLE_WORK_DB_ONLY', 'Unexpected transaction result');
            $hash=$method['index_digest_sha256'] ?? '';
            must(is_string($hash) && preg_match('/^[0-9a-f]{64}$/', $hash)===1, 'Invalid index digest');
            $digests[]=$hash;
        }
        must(hash_equals($digests[0],$digests[1]), 'Digest mismatch for pair '.($index+1));
    }
    sort($families);sort($expectedFamilies);
    must($families === $expectedFamilies, 'Missing mutation families: '.implode(',',$families));
    must(($data['initial']['source']['sha256'] ?? '') === '8676384ea28e4cc98fa8e5e50c8f221cb5a2746e91259a28f8b7ebe3085c39d4', 'Wrong source baseline');
    must(($data['initial']['source'] ?? []) === ($data['final']['source'] ?? null), 'Source baseline changed');
    must(($data['initial']['sandbox'] ?? []) === ($data['final']['sandbox'] ?? null), 'Sandbox count drift');
    must(($data['initial_index_digest_sha256'] ?? '') !== '', 'Missing baseline digest');
    $stamp=date('Ymd_His');
    $suffix=bin2hex(random_bytes(3));
    $rawName='STAGE22B_RAW_B1_4PAIRS_'.$stamp.'_'.$suffix.'.json';
    must(copy($reportPath, $dest.'/'.$rawName), 'Failed to copy original JSON');
    $summary=[
        'stage'=>'22B', 'substage'=>'pilot_commit_parity', 'status'=>'PASS',
        'timestamp_wita'=>date('c'),
        'scope'=>'4 paired B/C cases (word, meaning, category, unpublish), 1x baseline, batch 1',
        'families_tested'=>$families,
        'pairs'=>count($runs),'identical_index_digests'=>true,
        'committed_on_disposable_db_only'=>true,
        'work_db_reported_disposed'=>true,
        'original_source_sha256'=>$data['initial']['source']['sha256'],
        'original_runner_sha256'=>hash_file('sha256',$root.'/tests/index_comparator_abc.php'),
        'source_source_and_reference_unchanged_according_to_original_runner'=>true,
        'raw_report_file'=>$rawName,
        'raw_report_sha256'=>hash_file('sha256', $reportPath),
        'not_yet_tested'=>['publish-pending fresh mutation', 'explicit ROLLBACK parity', 'concurrent writer schedules'],
        'scientific_boundary'=>'Preliminary Stage22B correctness pilot; do not call this a 5-family or rollback-proof validation.'
    ];
    $summaryName='STAGE22B_SUMMARY_'.$stamp.'_'.$suffix.'.json';
    $saved=file_put_contents($dest.'/'.$summaryName,json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
    must($saved !== false, 'Cannot save summary');
    echo '[PASS] 4/4 families and paired digest parity; commit only in disposable work database'.PHP_EOL;
    echo 'Summary: '.$dest.'/'.$summaryName.PHP_EOL;
    echo 'Raw: '.$dest.'/'.$rawName.PHP_EOL;
    echo 'Remaining: publish-pending and ROLLBACK checks (not part of this pilot).'.PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, '[STAGE22B FAIL] '.$error->getMessage().PHP_EOL);
    exit(1);
}
