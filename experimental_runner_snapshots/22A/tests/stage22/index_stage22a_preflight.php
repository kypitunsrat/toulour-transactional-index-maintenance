<?php
/**
 * RESTI indexing Stage 1: READ-ONLY preflight for source and database.
 * Not a benchmark. Does not call DDL/DML, START TRANSACTION, or source gateway.
 * No row contents, credentials, or full file paths are written into its report.
 * Usage:
 *   php resti_stage1_preflight.php --project=C:\xampp\htdocs\kamus_tolour_index_v1 --offline
 *   php resti_stage1_preflight.php --project=C:\xampp\htdocs\kamus_tolour_index_v1 --db-check
 * Environment (db-check): RESTI_DB_HOST, RESTI_DB_PORT, RESTI_DB_USER, RESTI_DB_PASS
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit(2); }

const SOURCE_DB = 'kamus_tolour_index_v2';
const SANDBOX_DB = 'kamus_tolour_index_stage21_s1_sandbox';
const TEMP_WORK_DB = 'kamus_tolour_index_abc_s1_work_sandbox';
const EXPECTED_SOURCE_HASH = '8676384ea28e4cc98fa8e5e50c8f221cb5a2746e91259a28f8b7ebe3085c39d4';
const EXPECTED_FILES = [
 'services/SearchService.php' => 'bd92f47062730315e21939ca2f0eb3c94dd8e28b30a9db93e816d0f75f30e95f',
 'services/IndexStage2Materializer.php' => '4bfbeb5bb14b97330000bee12cacc9517cf585f7c1c816bde8bb50392c489caa',
 'services/IndexStage3IncrementalMaintainer.php' => 'b2c00ba1e8f34d4ad0b14311702d05b83ff6c92678254a36beef49447269a02f',
 'models/Dictionary.php' => '016fc74ce2f560757302a757b5041d4ee3c773f3fe7c59fa3d95d070aba64c98'
];
const REPORTS = [
 1 => 'INDEX_ABC_BENCHMARK_B1_TARGETED_20261101_230231_14cadb.json',
 10 => 'INDEX_ABC_BENCHMARK_B10_TARGETED_20261101_235207_b9df03.json',
 100 => 'INDEX_ABC_BENCHMARK_B100_TARGETED_20261102_002246_affdba.json'
];
const DB_COUNTS = ['dictionary'=>5687, 'approved'=>4587, 'entries'=>4587, 'terms'=>29546];

function requireCheck(bool $ok, string $message): void {
 if (!$ok) throw new RuntimeException($message);
}
function optArgs(array $argv):array {
 $opts=['offline'=>false,'db-check'=>false,'project'=>null];
 foreach (array_slice($argv,1) as $arg) {
  if ($arg==='--offline') $opts['offline']=true;
  elseif ($arg==='--db-check') $opts['db-check']=true;
  elseif (str_starts_with($arg,'--project=')) $opts['project']=substr($arg,10);
  else throw new RuntimeException('Unknown argument: '.$arg);
 }
 requireCheck($opts['offline'] xor $opts['db-check'],'Choose exactly one: --offline OR --db-check');
 requireCheck(is_string($opts['project']) && $opts['project']!=='','Pass --project=PATH_TO_kamus_tolour_index_v1');
 $path=realpath($opts['project']);
 requireCheck($path!==false && is_dir($path) && basename($path)==='kamus_tolour_index_v1',
  'Project folder does not exist or is not named kamus_tolour_index_v1');
 $opts['project']=$path;
 return $opts;
}
function checkFiles(string $root):array {
 $results=[];
 foreach (EXPECTED_FILES as $relative=>$sha) {
  $p=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);
  requireCheck(is_file($p),'Missing frozen source: '.$relative);
  $actual=hash_file('sha256',$p);
  requireCheck(is_string($actual) && hash_equals($sha,$actual),'Frozen source differs: '.$relative);
  $results[$relative]=['sha256'=>$actual,'status'=>'PASS'];
 }
 $runner=$root.'/tests/index_comparator_abc.php';
 requireCheck(is_file($runner),'Missing original ABC runner');
 $runnerText=(string)file_get_contents($runner);
 requireCheck(str_contains($runnerText,"const S21_MAIN = '".SOURCE_DB."'"),'Unexpected source database in runner');
 requireCheck(str_contains($runnerText,'--abc-benchmark'),'Original ABC runner mode missing');
 requireCheck(str_contains($runnerText,'function abcSelectiveRefresh('),'Original B implementation missing');
 requireCheck(str_contains($runnerText,"'differential'"),'Original C arm missing');
 return ['frozen_files'=>$results,'historical_abc_runner_sha256'=>hash_file('sha256',$runner),
  'historical_abc_runner_present'=>true];
}
function checkReports(string $root):array {
 $summary=[];$total=0;
 foreach (REPORTS as $batch=>$file) {
  $path=$root.'/tests/reports/'.$file;
  requireCheck(is_file($path),'Missing historical JSON: '.$file);
  $data=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
  requireCheck(($data['status']??null)==='PASS','Historical report does not state PASS: B'.$batch);
  requireCheck(($data['batch']??null)===$batch,'Historical report batch mismatch: B'.$batch);
  requireCheck(($data['arms']??null)==='targeted','Expected targeted historical B/C: B'.$batch);
  $runs=$data['benchmark']['runs']??[];
  $expectedN=$batch===1?10:8;
  requireCheck(is_array($runs) && count($runs)===$expectedN,'Historical report repetitions mismatch: B'.$batch);
  foreach ($runs as $run) {
   requireCheck(($run['parity']??'')==='PASS','Historical parity FAIL: B'.$batch);
   requireCheck(isset($run['methods']['selective_refresh'],$run['methods']['differential']),
    'Missing B or C method in B'.$batch);
  }
  $summary[(string)$batch]=['pairs'=>count($runs),'all_pairs_parity_pass'=>true,
   'evidence_sha256'=>hash_file('sha256',$path)];
  $total+=count($runs);
 }
 return ['batches'=>$summary,'total_pairs'=>$total,'historical_status'=>'REPORTED_PASS_NOT_RERUN'];
}
function connection(string $name):PDO {
 requireCheck(in_array($name,[SOURCE_DB,SANDBOX_DB],true),'Disallowed database name');
 requireCheck(extension_loaded('pdo_mysql'),'PHP extension pdo_mysql missing');
 $host=getenv('RESTI_DB_HOST')?:'127.0.0.1';
 $port=getenv('RESTI_DB_PORT')?:'3306';
 requireCheck(ctype_digit($port) && (int)$port>=1 && (int)$port<=65535,'Invalid database port');
 requireCheck(preg_match('/^[a-zA-Z0-9_.:-]+$/',$host)===1,'Invalid database host');
 $user=getenv('RESTI_DB_USER'); if($user===false) $user='root';
 $pass=getenv('RESTI_DB_PASS'); if($pass===false) $pass='';
 $db=new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",$user,$pass,[
   PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
   PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
   PDO::ATTR_EMULATE_PREPARES=>false,
   PDO::ATTR_TIMEOUT=>15
 ]);
 requireCheck((string)$db->query('SELECT DATABASE()')->fetchColumn()===$name,'Wrong database context');
 return $db;
}
function counts(PDO $db):array {
 return ['dictionary'=>(int)$db->query('SELECT COUNT(*) FROM dictionary')->fetchColumn(),
  'approved'=>(int)$db->query("SELECT COUNT(*) FROM dictionary WHERE status='disetujui'")->fetchColumn(),
  'entries'=>(int)$db->query('SELECT COUNT(*) FROM idx_stage2_entries')->fetchColumn(),
  'terms'=>(int)$db->query('SELECT COUNT(*) FROM idx_stage2_terms')->fetchColumn()];
}
function coreFingerprint(PDO $db):array {
 $keys=['id','category_id','contributor_id','status','tolour_word','indonesian_meaning',
        'example_sentence','description','is_edit','original_id'];
 $hash=hash_init('sha256');$count=0;$approved=0;
 foreach($db->query('SELECT * FROM dictionary WHERE id < 1000000 ORDER BY id', PDO::FETCH_ASSOC) as $r) {
  ++$count;if($r['status']==='disetujui')++$approved;
  $v=[];foreach($keys as $k)$v[]=$r[$k]??null;
  hash_update($hash,json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)."\n");
 }
 return ['total'=>$count,'approved'=>$approved,'sha256'=>hash_final($hash)];
}
function checkDBs():array {
 $source=connection(SOURCE_DB);
 $sandbox=connection(SANDBOX_DB);
 $sourceCounts=counts($source);$sandboxCounts=counts($sandbox);
 requireCheck($sourceCounts===DB_COUNTS,'Source V2 count mismatch');
 requireCheck($sandboxCounts===DB_COUNTS,'Stage21 S1 sandbox count mismatch');
 $sourceHash=coreFingerprint($source);$sandboxHash=coreFingerprint($sandbox);
 requireCheck($sourceHash['total']===5687 && $sourceHash['approved']===4587 &&
  hash_equals(EXPECTED_SOURCE_HASH,$sourceHash['sha256']),'Source V2 fingerprint differs');
 requireCheck($sourceHash===$sandboxHash,'Stage21 S1 source differs from V2 source');
 $meta=$sandbox->query('SELECT schema_version,baseline_dictionary_sha256,materialized_entries,materialized_terms FROM idx_stage2_meta WHERE id=1')->fetch();
 requireCheck($meta!==false,'Missing index metadata row');
 requireCheck($meta['schema_version']==='index_materialization_stage2_v1' &&
  hash_equals(EXPECTED_SOURCE_HASH,(string)$meta['baseline_dictionary_sha256']) &&
  (int)$meta['materialized_entries']===4587 && (int)$meta['materialized_terms']===29546,
  'Stage21 S1 index metadata mismatch');
 $engines=$sandbox->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('dictionary','idx_stage2_entries','idx_stage2_terms','idx_stage2_meta')")->fetchAll();
 requireCheck(count($engines)===4,'Missing required tables');
 foreach($engines as $e)requireCheck(strtoupper((string)$e['ENGINE'])==='INNODB','Expected InnoDB: '.$e['TABLE_NAME']);
 $triggers=(int)$sandbox->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE='dictionary'")->fetchColumn();
 requireCheck($triggers===0,'Unexpected dictionary trigger');
 $existing=(int)$sandbox->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".TEMP_WORK_DB."'")->fetchColumn();
 requireCheck($existing===0,'Old ABC disposable work database still exists; STOP and inspect manually');
 return ['source_database'=>SOURCE_DB,'sandbox_database'=>SANDBOX_DB,
 'counts_source'=>$sourceCounts,'counts_sandbox'=>$sandboxCounts,
 'source_core_sha256'=>$sourceHash['sha256'],'index_metadata_valid'=>true,
 'tables_innodb'=>true,'dictionary_triggers'=>0,'old_abc_work_database_exists'=>false];
}

$result=['tool'=>'RESTI Stage 1 Preflight (no DDL/DML)','timestamp'=>date('c'),
 'checks'=>[],'mode'=>null,'status'=>'FAIL'];
try {
 $opts=optArgs($argv);
 $result['mode']=$opts['db-check']?'db-check':'offline';
 $result['checks']['source']=checkFiles($opts['project']);
 $result['checks']['historical_evidence']=checkReports($opts['project']);
 if($opts['db-check'])$result['checks']['live_readonly_database_queries']=checkDBs();
 $result['status']='PASS';
} catch(Throwable $e) {
 // Never echo credentials or SQL row values; errors can be placed in local logs only.
 $result['error']=$e->getMessage();
}
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
exit($result['status']==='PASS'?0:1);
