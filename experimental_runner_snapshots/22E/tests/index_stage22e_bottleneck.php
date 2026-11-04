<?php
/**
 * STAGE22E_FORENSIC_AND_SQL_READONLY_2026, PHP 8.2 CLI.
 * Offline: read six FINAL 22C/22D JSONs, calculate paired/per-phase descriptive evidence.
 * SQL: read-only EXPLAIN, indexes, a few SELECT timings. NO DDL, DML, COMMIT, LOCK, or SELECT FOR UPDATE.
 * Does not execute the 22D benchmark or rewrite the historical code.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
date_default_timezone_set('Asia/Makassar');
ini_set('memory_limit','512M');
$root = dirname(__DIR__);
$mode = $argv[1] ?? '';
const E_PROTOCOL='STAGE22E_FORENSIC_SQL_READONLY_2026_V1';
const E_HISTORICAL='3defe1d359cbd4ba8daea95c329e71748c8beb4609bab3c0fa75662d4521dd19';
const E_FROZEN=[
 'services/SearchService.php'=>'bd92f47062730315e21939ca2f0eb3c94dd8e28b30a9db93e816d0f75f30e95f',
 'services/IndexStage2Materializer.php'=>'4bfbeb5bb14b97330000bee12cacc9517cf585f7c1c816bde8bb50392c489caa',
 'services/IndexStage3IncrementalMaintainer.php'=>'b2c00ba1e8f34d4ad0b14311702d05b83ff6c92678254a36beef49447269a02f',
 'models/Dictionary.php'=>'016fc74ce2f560757302a757b5041d4ee3c773f3fe7c59fa3d95d070aba64c98'
];
const E_MAIN='kamus_tolour_index_v2';
const E_REF='kamus_tolour_index_stage21_s1_sandbox';
const E_BASE_HASH='8676384ea28e4cc98fa8e5e50c8f221cb5a2746e91259a28f8b7ebe3085c39d4';
const E_INDEX_HASH='dbbd4272c1c135f263746a706b412b1b55a06f1f9db2aaf35761027aa13425f0';
const E_PHASES=['meta_lock_ms','entry_postings_read_lock_ms','project_and_diff_ms','entry_write_ms','postings_write_ms','meta_update_ms'];
function e_assert(bool $ok,string $why):void { if(!$ok)throw new RuntimeException($why); }
function e_json($v):string {return json_encode($v,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)."\n";}
function e_file(string $path,string $body):void { e_assert(file_put_contents($path,$body,LOCK_EX)!==false,'Tidak bisa menyimpan: '.$path); }
function e_stats(array $values):array {
 e_assert(count($values)>0,'Statistik kosong');
 $v=array_map('floatval',$values);sort($v,SORT_NUMERIC);$n=count($v);$mean=array_sum($v)/$n;
 $m=($n%2)?$v[intdiv($n,2)]:($v[$n/2-1]+$v[$n/2])/2;
 $variance=array_sum(array_map(static fn($x)=>($x-$mean)**2,$v))/max(1,$n-1);
 $trim=intdiv($n,10);$trimmed=array_slice($v,$trim,$n-2*$trim);
 return ['n'=>$n,'mean_ms'=>round($mean,4),'median_ms'=>round($m,4),'p95_nearest_rank_ms'=>round($v[(int)ceil(.95*$n)-1],4),
  'min_ms'=>round($v[0],4),'max_ms'=>round($v[$n-1],4),'sd_sample_ms'=>round(sqrt($variance),4),
  'trimmed_mean_10pct_ms'=>round(array_sum($trimmed)/count($trimmed),4)];
}
function e_assertFrozen(string $root):array {
 e_assert(basename($root)==='kamus_tolour_index_v1','STOP: paket harus diekstrak langsung ke folder kamus_tolour_index_v1');
 $original=$root.'/tests/index_comparator_abc.php';
 e_assert(is_file($original)&&hash_equals(E_HISTORICAL,hash_file('sha256',$original)),'STOP: hash comparator historis berbeda atau file hilang');
 $seen=['tests/index_comparator_abc.php'=>E_HISTORICAL];
 foreach(E_FROZEN as $relative=>$sha){$file=$root.'/'.$relative;e_assert(is_file($file),'File frozen hilang: '.$relative);$real=hash_file('sha256',$file);e_assert(hash_equals($sha,$real),'Hash source berbeda: '.$relative);$seen[$relative]=$real;}
 return $seen;
}
function e_selectFinal(string $root,string $stage,int $batch):array {
 $dir=$root.'/tests/stage22/reports';
 $files=glob($dir.'/STAGE'.$stage.'_B'.$batch.'_30PAIRS_*.json')?:[];
 e_assert(count($files)>0,'File FINAL STAGE'.$stage.' B'.$batch.' 30PAIRS tidak ditemukan dalam tests/stage22/reports. Salin JSON yang sudah PASS ke sana.');
 // Select the last valid PASS report by its embedded timestamp, tie-breaking by filename.
 $valid=[];
 foreach($files as $file){
  try{$d=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);}catch(Throwable $ignored){continue;}
  if(!is_array($d)||($d['stage']??null)!==$stage||($d['batch']??null)!==$batch||($d['reps']??null)!==30||($d['status']??null)!=='PASS')continue;
  if(count($d['benchmark']['runs']??[])!==30)continue;
  $valid[]=['file'=>$file,'data'=>$d,'sort'=>($d['timestamp_wita']??'').'|'.basename($file)];
 }
 e_assert(count($valid)>0,'Tidak ada laporan FINAL PASS STAGE'.$stage.' Batch '.$batch.' dengan 30 pasangan lengkap');
 usort($valid,static fn($a,$b)=>strcmp($a['sort'],$b['sort']));
 $chosen=end($valid);return ['file'=>$chosen['file'],'data'=>$chosen['data'],'candidates'=>array_map('basename',$files)];
}
function e_arm(array $run,string $name):array { $a=$run['methods'][$name]??null;e_assert(is_array($a),'Arm hilang '.$name);return $a; }
function e_writeCount(array $arm,string $name):int {
 $ops=$arm['index_operations']??[];
 if($name==='B2_refresh_matched'||$name==='C2_delta_matched')return (int)($ops['postings_total_writes']??-1);
 if($name==='selective_refresh')return (int)($ops['postings_deleted']??-1)+(int)($ops['postings_inserted']??-1);
 return (int)($ops['write_counts']['postings_deleted']??-1)+(int)($ops['write_counts']['postings_inserted']??-1);
}
function e_round($n,int $digits=3):float {return round((float)$n,$digits);}
function e_offline(string $root,string $outDir,string $runId):array {
 $summary=['scope'=>'Six completed Stage22C/22D FINAL 30-pair JSONs, paired by fixture within each protocol. NO new SQL experiments.',
  'warnings'=>['Phase timings are wall-clock per-operation time and may include server stalls; not causal profiling.',
   'Physical lock waits not measured; counts of locking statements only.',
   'Original B/C and controlled B2/C2 are separate protocols: do not pool latency.',
   'All Stage22D controls intentionally add full-posting reads to B2; not production throughput.',
   'One host, one 1x real corpus; no independent workload/hardware replication.'], 'batches'=>[],'inputs'=>[]];
 $csv=[];$head=['batch','pair','fixture_sha256','parity_22C','parity_22D','B_22C_ms','C_22C_ms','B2_22D_ms','C2_22D_ms','B2_minus_C2_ms','B2_post_writes','C2_post_writes'];
 foreach(E_PHASES as $phase){$head[]='B2_'.$phase;$head[]='C2_'.$phase;}$csv[]=$head;
 foreach([1,10,100] as $batch){
  $rawC=e_selectFinal($root,'22C',$batch);$rawD=e_selectFinal($root,'22D',$batch);
  $rC=$rawC['data']['benchmark']['runs'];$rD=$rawD['data']['benchmark']['runs'];
  $summary['inputs'][]=['stage'=>'22C','batch'=>$batch,'file'=>basename($rawC['file']),'sha256'=>hash_file('sha256',$rawC['file']),'other_candidates'=>$rawC['candidates']];
  $summary['inputs'][]=['stage'=>'22D','batch'=>$batch,'file'=>basename($rawD['file']),'sha256'=>hash_file('sha256',$rawD['file']),'other_candidates'=>$rawD['candidates']];
  e_assert(($rawC['data']['historical_abc_runner_sha256']??'')===E_HISTORICAL,'22C historical runner hash mismatch');
  e_assert(($rawD['data']['historical_abc_runner_sha256']??'')===E_HISTORICAL,'22D historical runner hash mismatch');
  e_assert(($rawC['data']['initial_index_digest_sha256']??'')===E_INDEX_HASH && ($rawD['data']['initial_index_digest_sha256']??'')===E_INDEX_HASH,'Baseline digest berbeda pada B'.$batch);
  $vals=['B'=>[],'C'=>[],'B2'=>[],'C2'=>[],'delta'=>[]];$writes=['B'=>0,'C'=>0,'B2'=>0,'C2'=>0];
  $wins=['B'=>0,'C'=>0,'B2'=>0,'C2'=>0];$phaseData=[];$phaseTotals=[];
  foreach(E_PHASES as $phase){$phaseData[$phase]=['B2'=>[],'C2'=>[]];$phaseTotals[$phase]=['B2'=>0,'C2'=>0];}
  $controls=['metadata_lock_B2'=>0,'metadata_lock_C2'=>0,'entry_lock_B2'=>0,'entry_lock_C2'=>0,'posting_lock_B2'=>0,'posting_lock_C2'=>0,'metadata_update_B2'=>0,'metadata_update_C2'=>0];
  for($i=0;$i<30;$i++){
   $x=$rC[$i];$y=$rD[$i];e_assert(($x['rep']??null)===$i+1&&($y['rep']??null)===$i+1,'Urutan rep berubah');
   e_assert(($x['mutation_id_sha256']??'')!==''&&$x['mutation_id_sha256']===$y['mutation_id_sha256'],'Fixture tidak identik antara 22C/22D batch='.$batch.' rep='.($i+1));
   e_assert(($x['parity']??'')==='PASS'&&($y['parity']??'')==='PASS','Parity FAIL rep='.($i+1));
   $B=e_arm($x,'selective_refresh');$C=e_arm($x,'differential');$B2=e_arm($y,'B2_refresh_matched');$C2=e_arm($y,'C2_delta_matched');
   $digest=$B['index_digest_sha256']??'';
   e_assert($digest!==''&&$digest===$C['index_digest_sha256']&&$digest===$B2['index_digest_sha256']&&$digest===$C2['index_digest_sha256'],'Cross-protocol digest mismatch batch='.$batch.' rep='.($i+1));
   foreach(['B'=>$B,'C'=>$C,'B2'=>$B2,'C2'=>$C2] as $key=>$arm){$m=(float)($arm['maintenance_ms']??-1);e_assert($m>=0,'Maintenance invalid');$vals[$key][]=$m;
     $w=e_writeCount($arm,['B'=>'selective_refresh','C'=>'differential','B2'=>'B2_refresh_matched','C2'=>'C2_delta_matched'][$key]);e_assert($w>=0,'Posting writes missing');$writes[$key]+=$w;}
   if($B['maintenance_ms']<$C['maintenance_ms'])$wins['B']++;elseif($B['maintenance_ms']>$C['maintenance_ms'])$wins['C']++;
   if($B2['maintenance_ms']<$C2['maintenance_ms'])$wins['B2']++;elseif($B2['maintenance_ms']>$C2['maintenance_ms'])$wins['C2']++;
   $delta=(float)$B2['maintenance_ms']-(float)$C2['maintenance_ms'];$vals['delta'][]=$delta;
   $opsB=$B2['index_operations'];$opsC=$C2['index_operations'];
   foreach(['metadata_lock_statements'=>'metadata_lock','entry_lock_queries'=>'entry_lock','posting_lock_queries'=>'posting_lock','metadata_update_statements'=>'metadata_update'] as $field=>$label){
     $vB=(int)($opsB[$field]??-1);$vC=(int)($opsC[$field]??-1);
     e_assert($vB===$vC && $vB>=0,'Kontrol '.$field.' mismatch pada rep='.($i+1));
     $controls[$label.'_B2']+=$vB;$controls[$label.'_C2']+=$vC;
   }
   e_assert((int)$opsB['entry_lock_queries']===$batch && (int)$opsB['posting_lock_queries']===$batch && (int)$opsB['metadata_lock_statements']===1,'Jumlah lock unexpected');
   $row=[$batch,$i+1,$x['mutation_id_sha256'],'PASS','PASS',$B['maintenance_ms'],$C['maintenance_ms'],$B2['maintenance_ms'],$C2['maintenance_ms'],e_round($delta),e_writeCount($B2,'B2_refresh_matched'),e_writeCount($C2,'C2_delta_matched')];
   foreach(E_PHASES as $phase){$pb=(float)($opsB['phase_ms'][$phase]??-1);$pc=(float)($opsC['phase_ms'][$phase]??-1);e_assert($pb>=0&&$pc>=0,'Phase missing '.$phase);
     $phaseData[$phase]['B2'][]=$pb;$phaseData[$phase]['C2'][]=$pc;
     $phaseTotals[$phase]['B2']+=$pb;$phaseTotals[$phase]['C2']+=$pc;$row[]=e_round($pb,4);$row[]=e_round($pc,4);}
   $csv[]=$row;
  }
  $summary['batches'][(string)$batch]=['pairs'=>30,'all_cross_protocol_digest_equal'=>true,'controlled_query_counts_matched'=>true,
   'winner_counts_22C'=>$wins['B']+$wins['C']===30?['B'=>$wins['B'],'C'=>$wins['C']]:$wins,
   'winner_counts_22D'=>['B2'=>$wins['B2'],'C2'=>$wins['C2'],'ties'=>30-$wins['B2']-$wins['C2']],
   'maintenance_stats'=>['B'=>e_stats($vals['B']),'C'=>e_stats($vals['C']),'B2'=>e_stats($vals['B2']),'C2'=>e_stats($vals['C2']), 'paired_B2_minus_C2'=>e_stats($vals['delta'])],
   'posting_writes'=>$writes,'posting_reduction_C2_pct'=>e_round(100*(1-$writes['C2']/$writes['B2']),4),
   'control_counts'=>$controls,'phase_sums_ms'=>[],'phase_median_ms'=>[],'phase_p95_ms'=>[]];
  foreach(E_PHASES as $phase){$summary['batches'][(string)$batch]['phase_sums_ms'][$phase]=['B2'=>e_round($phaseTotals[$phase]['B2']),'C2'=>e_round($phaseTotals[$phase]['C2'])];
    $sb=e_stats($phaseData[$phase]['B2']);$sc=e_stats($phaseData[$phase]['C2']);$summary['batches'][(string)$batch]['phase_median_ms'][$phase]=['B2'=>$sb['median_ms'],'C2'=>$sc['median_ms']];$summary['batches'][(string)$batch]['phase_p95_ms'][$phase]=['B2'=>$sb['p95_nearest_rank_ms'],'C2'=>$sc['p95_nearest_rank_ms']];}
  echo '[OFFLINE] Batch '.$batch.' : parity 30/30 PASS | kontrol cocok | C2 write reduction '.$summary['batches'][(string)$batch]['posting_reduction_C2_pct'].'%' . "\n";
 }
 $csvPath=$outDir.'/STAGE22E_PAIRED_PHASES_'.$runId.'.csv';$h=fopen($csvPath,'wb');e_assert((bool)$h,'Cannot create CSV');foreach($csv as $r)fputcsv($h,$r);fclose($h);
 $summary['paired_csv']=basename($csvPath);
 $text="# Stage 22E — Audit fase dan bottleneck (offline)\n\n";
 $text.="Status: **PASS** untuk verifikasi enam laporan dan penghitungan 90 pasangan. Tidak ada SQL dijalankan.\n\n";
 $text.="| Batch | Median B2 (ms) | Median C2 (ms) | B2 menang | C2 menang | Pengurangan writes C2 |\n|---|---:|---:|---:|---:|---:|\n";
 foreach([1,10,100] as $b){$s=$summary['batches'][(string)$b];$text.="| $b | {$s['maintenance_stats']['B2']['median_ms']} | {$s['maintenance_stats']['C2']['median_ms']} | {$s['winner_counts_22D']['B2']} | {$s['winner_counts_22D']['C2']} | {$s['posting_reduction_C2_pct']}% |\n";}
 $text.="\n### Akumulasi fase Stage22D (30 pasangan per batch; satuan ms)\n\n";
 foreach([1,10,100] as $b){$text.="**Batch $b**\n\n| Fase | B2 | C2 | C2 - B2 |\n|---|---:|---:|---:|\n";
  foreach(E_PHASES as $p){$x=$summary['batches'][(string)$b]['phase_sums_ms'][$p];$diff=e_round($x['C2']-$x['B2']);$text.="| `$p` | {$x['B2']} | {$x['C2']} | $diff |\n";}$text.="\n";}
 $text.="**Interpretasi:** penghematan writes menunjukkan biaya logical DML lebih rendah; fase wall-clock dapat memuat jitter/lock waits/IO. " .
  "Kesetaraan jumlah query dan kebijakan metadata tidak menyetarakan cost fisik. Tidak boleh menyatakan sebab-akibat tanpa bukti query-level tambahan. ".
  "Tidak menggabungkan sampel latency Stage22C dan Stage22D.\n\n";
 $mdPath=$outDir.'/STAGE22E_INTERPRETASI_'.$runId.'.md';e_file($mdPath,$text);
 $summary['interpretation_file']=basename($mdPath);
 return $summary;
}
function e_openDb(string $database):PDO {
 e_assert(in_array($database,[E_MAIN,E_REF],true),'DB tidak di-allowlist');
 $pdo=new PDO('mysql:host=127.0.0.1;dbname='.$database.';charset=utf8mb4','root','',[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>10]);
 e_assert($pdo->query('SELECT DATABASE()')->fetchColumn()===$database,'Salah koneksi database');
 return $pdo;
}
function e_counts(PDO $p):array {
 return ['dictionary'=>(int)$p->query('SELECT COUNT(*) FROM dictionary')->fetchColumn(),
  'approved'=>(int)$p->query("SELECT COUNT(*) FROM dictionary WHERE status='disetujui'")->fetchColumn(),
  'entries'=>(int)$p->query('SELECT COUNT(*) FROM idx_stage2_entries')->fetchColumn(),
  'terms'=>(int)$p->query('SELECT COUNT(*) FROM idx_stage2_terms')->fetchColumn()];
}
function e_hashCore(PDO $db):array {
 $keys=['id','category_id','contributor_id','status','tolour_word','indonesian_meaning','example_sentence','description','is_edit','original_id'];
 $hash=hash_init('sha256');$count=0;$approved=0;
 foreach($db->query('SELECT * FROM dictionary WHERE id < 1000000 ORDER BY id',PDO::FETCH_ASSOC) as $r){$count++;if($r['status']==='disetujui')$approved++;
  $vals=[];foreach($keys as $k)$vals[]=$r[$k]??null;
  hash_update($hash,json_encode($vals,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)."\n");
 }
 return ['total'=>$count,'approved'=>$approved,'sha256'=>hash_final($hash)];
}
function e_indexDigest(PDO $db):string {
 $hash=hash_init('sha256');
 foreach([
   'SELECT dictionary_id,category_id,normalized_word,normalized_meaning,content_sha256 FROM idx_stage2_entries ORDER BY dictionary_id',
   'SELECT dictionary_id,kind,term_sha256,normalized_term FROM idx_stage2_terms ORDER BY dictionary_id,kind,term_sha256',
   'SELECT id,schema_version,baseline_dictionary_sha256,materialized_entries,materialized_terms FROM idx_stage2_meta ORDER BY id'
 ] as $i=>$sql){
  hash_update($hash,"table".$i."\n");
  foreach($db->query($sql,PDO::FETCH_ASSOC) as $row){hash_update($hash,json_encode(array_values($row),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");}
 }
 return hash_final($hash);
}
function e_sqlProfile(string $root):array {
 e_assert(extension_loaded('pdo_mysql'),'pdo_mysql belum aktif. Gunakan C:\\xampp\\php\\php.exe');
 $main=e_openDb(E_MAIN);$ref=e_openDb(E_REF);
 // A paused/interrupted Stage22C/D run may leave a work DB behind; never profile in that state.
 $active=$ref->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME IN ('kamus_tolour_index_stage22d_work_sandbox','kamus_tolour_index_abc_s1_work_sandbox')")->fetchAll(PDO::FETCH_COLUMN);
 e_assert($active===[],'STOP: database kerja sementara masih ada ('.implode(',',$active).'). Periksa status run sebelumnya; jangan hapus manual.');
 $expected=['dictionary'=>5687,'approved'=>4587,'entries'=>4587,'terms'=>29546];
 $before=[];
 foreach(['source'=>$main,'reference'=>$ref] as $key=>$db){$counts=e_counts($db);e_assert($counts===$expected,'STOP: baseline counts '.$key.' berbeda. Tidak menjalankan diagnostik.');
  $hash=e_hashCore($db);e_assert($hash['sha256']===E_BASE_HASH && $hash['approved']===4587,'STOP: source core hash mismatch '.$key);
  $before[$key]=['counts'=>$counts,'core_hash'=>$hash];}
 $refDigest=e_indexDigest($ref);e_assert(hash_equals(E_INDEX_HASH,$refDigest),'STOP: reference index digest mismatch');
 $server=['version'=>(string)$ref->query('SELECT VERSION()')->fetchColumn(),'schema'=>(string)$ref->query('SELECT DATABASE()')->fetchColumn(),
  'global_status'=>(array)$ref->query("SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_running','Threads_connected','Innodb_row_lock_waits','Innodb_row_lock_time','Innodb_buffer_pool_reads','Innodb_buffer_pool_read_requests')")->fetchAll(PDO::FETCH_KEY_PAIR),
  'variables'=>(array)$ref->query("SHOW VARIABLES WHERE Variable_name IN ('innodb_buffer_pool_size','innodb_flush_log_at_trx_commit','sync_binlog','autocommit','transaction_isolation','tx_isolation','innodb_lock_wait_timeout')")->fetchAll(PDO::FETCH_KEY_PAIR)];
 $indexes=[];$engines=[];
 foreach(['dictionary','idx_stage2_entries','idx_stage2_terms','idx_stage2_meta'] as $table){
  $st=$ref->prepare('SELECT INDEX_NAME,SEQ_IN_INDEX,COLUMN_NAME,NON_UNIQUE,INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY INDEX_NAME,SEQ_IN_INDEX');$st->execute([E_REF,$table]);$indexes[$table]=$st->fetchAll();
  $st=$ref->prepare('SELECT ENGINE,TABLE_ROWS,DATA_LENGTH,INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');$st->execute([E_REF,$table]);$engines[$table]=$st->fetch();
  e_assert(($engines[$table]['ENGINE']??'')==='InnoDB','STOP: '.$table.' bukan InnoDB');
 }
 $id=(int)$ref->query("SELECT id FROM dictionary WHERE status='disetujui' AND id < 1000000 ORDER BY id LIMIT 1")->fetchColumn();e_assert($id>0,'Tidak ada fixture approved');
 // EXPLAIN SELECT proxy for exactly the entry/posting read keys in Stage22D. No FOR UPDATE and no write statement.
 $queries=[
   'metadata_point_lookup'=>'SELECT * FROM idx_stage2_meta WHERE id=1',
   'entry_point_lookup'=>'SELECT * FROM idx_stage2_entries WHERE dictionary_id='.$id,
   'posting_prefetch'=>'SELECT kind,term_sha256,normalized_term FROM idx_stage2_terms WHERE dictionary_id='.$id.' ORDER BY kind,term_sha256',
   'posting_exact_key'=>'SELECT normalized_term FROM idx_stage2_terms WHERE dictionary_id='.$id." AND kind='W' AND term_sha256='0000000000000000000000000000000000000000000000000000000000000000'"
 ];
 $plans=[];$timings=[];
 foreach($queries as $label=>$sql){$plans[$label]=$ref->query('EXPLAIN '.$sql)->fetchAll(PDO::FETCH_ASSOC);
  $ms=[];for($i=0;$i<15;$i++){$t=hrtime(true);$rows=$ref->query($sql)->fetchAll(PDO::FETCH_ASSOC);$ms[]=(hrtime(true)-$t)/1e6;}
  $timings[$label]=['stats'=>e_stats($ms),'repetitions'=>15,'rows_last_execution'=>count($rows)];
 }
 $after=[];
 foreach(['source'=>$main,'reference'=>$ref] as $key=>$db){$counts=e_counts($db);$hash=e_hashCore($db);
  e_assert($counts===$before[$key]['counts'] && $hash===$before[$key]['core_hash'],'STOP: baseline counts/core hash changed during read-only diagnostics');
  $after[$key]=['counts'=>$counts,'core_hash'=>$hash];}
 e_assert(e_indexDigest($ref)===$refDigest,'STOP: Reference digest changed during read-only diagnostics');
 return ['status'=>'PASS','guard'=>'Read-only SQL only; no CREATE/DROP/UPDATE/DELETE/INSERT/COMMIT/ROLLBACK/LOCK/SELECT FOR UPDATE.',
  'source_baseline_before'=>$before,'source_baseline_after'=>$after,'reference_digest_before_after'=>$refDigest,
  'server'=>$server,'tables'=>$engines,'indexes'=>$indexes,'sample_dictionary_id'=>$id,'explain_select_plans'=>$plans,
  'select_latency_ms'=>$timings,'limitations'=>['EXPLAIN/SELECT queries are read-only proxies for Stage22D lookup path; do not measure FOR UPDATE lock waits, DML writes, or transaction cost.',
   '15 repetitions of tiny SELECTs cannot explain all 22D high-tail outliers.',
   'No code or schema changes made; no service variables changed.']];
}
if(basename($root)!=='kamus_tolour_index_v1'||!in_array($mode,['--offline','--sql','--all'],true)){
 fwrite(STDERR,"Usage:\n php tests/index_stage22e_bottleneck.php --offline\n php tests/index_stage22e_bottleneck.php --sql\n php tests/index_stage22e_bottleneck.php --all\n");exit(2);
}
$outDir=$root.'/tests/stage22/reports';if(!is_dir($outDir)&&!mkdir($outDir,0775,true)){fwrite(STDERR,"Tidak bisa membuat folder reports\n");exit(2);}
$runId=date('Ymd_His').'_'.bin2hex(random_bytes(4));
$report=['stage'=>'22E','protocol'=>E_PROTOCOL,'run_id'=>$runId,'timestamp_wita'=>date('c'), 'mode'=>$mode,'status'=>'INCOMPLETE',
 'safety'=>'No database mutations. Stage22C/D historical reports untouched. SQL connection only to source V2 and reference Stage21 S1.','source_modified'=>false];
try{
 $report['frozen_files']=e_assertFrozen($root);
 if($mode==='--offline'||$mode==='--all'){$report['offline']=e_offline($root,$outDir,$runId);}
 if($mode==='--sql'||$mode==='--all'){$report['sql_readonly']=e_sqlProfile($root);}
 $report['status']='PASS';
}catch(Throwable $e){$report['status']='FAIL';$report['error']=$e->getMessage();fwrite(STDERR,'[STAGE22E FAIL] '.$e->getMessage()."\n");}
$file=$outDir.'/STAGE22E_'.($mode==='--offline'?'OFFLINE':($mode==='--sql'?'SQL_READONLY':'GABUNGAN')).'_'.$runId.'.json';
try{e_file($file,e_json($report));echo '[STAGE22E] '.$report['status']."\nLaporan: ".$file."\n";}
catch(Throwable $e){fwrite(STDERR,'GAGAL menyimpan laporan: '.$e->getMessage()."\n");exit(1);}
exit($report['status']==='PASS'?0:1);
