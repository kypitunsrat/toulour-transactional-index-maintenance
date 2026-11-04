<?php
/**
 * Stage 22D: controlled B2/C2 metadata- and lock-matched fairness experiment.
 * This is a separate copy: DO NOT overwrite the historical tests/index_comparator_abc.php.
 * New experimental implementations: SAME prefetch/row locks, SAME single metadata batch update, different posting write policy. This is not the frozen production C.
 * Separate SAFE companion using frozen Stage21 source and deterministic scale-1 fixtures. All timed work
 * commits ONLY to a throwaway work DB recreated from the prepared sandbox. Source code and source DB are read-only; no global config changes.
 * Real 1x approved dictionary, no synthetic replicas.
 * PHP 8.2 / XAMPP MariaDB 10.4. Not production/app-level latency.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
set_time_limit(0);
ini_set('memory_limit', '2048M');
date_default_timezone_set('Asia/Makassar');
const S21_MAIN = 'kamus_tolour_index_v2';
const S22_HISTORICAL_SHA = '3defe1d359cbd4ba8daea95c329e71748c8beb4609bab3c0fa75662d4521dd19';
const S22_PROTOCOL = 'STAGE22D_B2_C2_LOCK_META_MATCHED_1X_2026';
const S21_SEED = 'toulour-stage21-controlled-batches-2026-10-v1';
const S21_SOURCE_SHA = '8676384ea28e4cc98fa8e5e50c8f221cb5a2746e91259a28f8b7ebe3085c39d4';
const S21_OFFSET = 1000000;
const S21_SOURCE_COUNT = 5687;
const S21_APPROVED = 4587;
const S21_TERMS = 29546;
const S21_SRC = [
 'services/SearchService.php'=>'bd92f47062730315e21939ca2f0eb3c94dd8e28b30a9db93e816d0f75f30e95f',
 'services/IndexStage2Materializer.php'=>'4bfbeb5bb14b97330000bee12cacc9517cf585f7c1c816bde8bb50392c489caa',
 'services/IndexStage3IncrementalMaintainer.php'=>'b2c00ba1e8f34d4ad0b14311702d05b83ff6c92678254a36beef49447269a02f',
 'models/Dictionary.php'=>'016fc74ce2f560757302a757b5041d4ee3c773f3fe7c59fa3d95d070aba64c98'
];
$root = dirname(__DIR__);
$mode = $argv[1] ?? '';
$scale = 1;
$batch = 1;
$reps = ($mode === '--fair-check') ? 1 : 30;
$arms = 'targeted';
$allowedModes = ['--fair-check','--fair-run'];
$usage = "Usage: php tests/index_stage22d_fairness.php --fair-check --batch=1 --reps=1 --arms=targeted\n".
 "       php tests/index_stage22d_fairness.php --fair-run --batch=1|10|100 --reps=2|30 --arms=targeted\n";
if (basename($root)!=='kamus_tolour_index_v1' || !in_array($mode,$allowedModes,true)) {fwrite(STDERR,$usage);exit(2);}
foreach(array_slice($argv,2) as $arg) {
 if(str_starts_with($arg,'--batch=')) {$v=substr($arg,8);if(!ctype_digit($v)){fwrite(STDERR,$usage);exit(2);}$batch=(int)$v;}
 elseif(str_starts_with($arg,'--reps=')) {$v=substr($arg,7);if(!ctype_digit($v)){fwrite(STDERR,$usage);exit(2);}$reps=(int)$v;}
 elseif(str_starts_with($arg,'--arms=')) {$arms=substr($arg,7);}
 else {fwrite(STDERR,'Unknown argument '.$arg."\n");exit(2);}
}
if(!in_array($batch,[1,10,100],true)||$arms!=='targeted'||
 ($mode==='--fair-check'&&$reps!==1)||
 ($mode==='--fair-run'&&!in_array($reps,[2,30],true))){
 fwrite(STDERR,$usage);exit(2);
}
// Verify the original runner was not replaced or edited before running the separate Stage22D copy.
$historical=$root.'/tests/index_comparator_abc.php';
if(!is_file($historical)||!hash_equals(S22_HISTORICAL_SHA,hash_file('sha256',$historical))){
 fwrite(STDERR,"STOP: historical ABC runner missing or modified; Stage22D refuses to run.\n");exit(2);
}
require_once $root.'/services/IndexStage3IncrementalMaintainer.php';

function s21Ensure(bool $condition,string $message):void {if(!$condition)throw new RuntimeException('STOP: '.$message);}
function s21Sbx(int $scale):string {return 'kamus_tolour_index_stage21_s'.$scale.'_sandbox';}
function s21Work(int $scale):string {return 'kamus_tolour_index_stage22d_work_sandbox';}
function s21Connect(string $dbname,int $scale):PDO {
 s21Ensure(in_array($dbname,[S21_MAIN,s21Sbx($scale),s21Work($scale)],true),'Database name not allowlisted');
 $db=new PDO('mysql:host=127.0.0.1;dbname='.$dbname.';charset=utf8mb4','root','',[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>15]);
 s21Ensure((string)$db->query('SELECT DATABASE()')->fetchColumn()===$dbname,'DB identity mismatch');
 $db->exec("SET time_zone='+08:00'"); return $db;
}
function s21CoreHash(PDO $db):array {
 // Hash only original rows (synthetic rows begin at id=1000000); identical to Stage17 protocol.
 $keys=['id','category_id','contributor_id','status','tolour_word','indonesian_meaning','example_sentence','description','is_edit','original_id'];
 $hash=hash_init('sha256');$count=0;$approved=0;
 foreach($db->query('SELECT * FROM dictionary WHERE id < 1000000 ORDER BY id',PDO::FETCH_ASSOC) as $r) {
  ++$count;if($r['status']==='disetujui')++$approved;
  $vals=[];foreach($keys as $k)$vals[]=$r[$k]??null;
  hash_update($hash,json_encode($vals,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)."\n");
 }
 return ['total'=>$count,'approved'=>$approved,'sha256'=>hash_final($hash)];
}
function s21Counts(PDO $db):array {
 return ['dictionary'=>(int)$db->query('SELECT COUNT(*) FROM dictionary')->fetchColumn(),
  'approved'=>(int)$db->query("SELECT COUNT(*) FROM dictionary WHERE status='disetujui'")->fetchColumn(),
  'entries'=>(int)$db->query('SELECT COUNT(*) FROM idx_stage2_entries')->fetchColumn(),
  'terms'=>(int)$db->query('SELECT COUNT(*) FROM idx_stage2_terms')->fetchColumn()];
}
function s21GuardSource(string $root):array {
 s21Ensure(extension_loaded('pdo_mysql')&&extension_loaded('mbstring'),'pdo_mysql/mbstring unavailable');
 $actual=[];
 foreach(S21_SRC as $file=>$expected) {
  $path=$root.'/'.$file;s21Ensure(is_file($path),'Missing frozen source: '.$file);
  $sha=hash_file('sha256',$path);s21Ensure(hash_equals($expected,$sha),'Frozen source mismatch: '.$file.' '.$sha);
  $actual[$file]=$sha;
 } return $actual;
}
function s21Validate(PDO $main,PDO $sbx,int $scale,bool $requirePrepared):array {
 $base=s21CoreHash($main);s21Ensure($base['total']===S21_SOURCE_COUNT && $base['approved']===S21_APPROVED &&
  hash_equals(S21_SOURCE_SHA,$base['sha256']),'Source baseline V2 differs. Refuse run.');
 $mainCount=s21Counts($main);
 s21Ensure($mainCount===['dictionary'=>5687,'approved'=>4587,'entries'=>4587,'terms'=>29546],
  'Main database counts differ from baseline');
 s21Ensure(s21CoreHash($sbx)===$base,'Sandbox original source differs');
 $actual=s21Counts($sbx);
 $baseline=['dictionary'=>5687,'approved'=>4587,'entries'=>4587,'terms'=>29546];
 $scaled=['dictionary'=>5687+($scale-1)*4587,'approved'=>$scale*4587,
   'entries'=>$scale*4587,'terms'=>$scale*29546];
 s21Ensure($actual===$baseline || $actual===$scaled,'Sandbox is neither clean nor expected scale: '.json_encode($actual));
 $prepared=($actual===$scaled);
 s21Ensure(!$requirePrepared || $prepared,'Sandbox not prepared: use --prepare first for scale '.$scale);
 $meta=$sbx->query('SELECT * FROM idx_stage2_meta WHERE id=1')->fetch(PDO::FETCH_ASSOC);
 s21Ensure($meta && $meta['schema_version']===IndexStage2Materializer::SCHEMA_VERSION &&
  $meta['baseline_dictionary_sha256']===S21_SOURCE_SHA &&
  (int)$meta['materialized_entries']===$actual['entries'] &&
  (int)$meta['materialized_terms']===$actual['terms'],'Metadata mismatch');
 $tables=$sbx->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('dictionary','idx_stage2_entries','idx_stage2_terms','idx_stage2_meta')")->fetchAll();
 s21Ensure(count($tables)===4,'Some sandbox tables missing');
 foreach($tables as $t)s21Ensure(strtoupper($t['ENGINE'])==='INNODB','Non-InnoDB table: '.$t['TABLE_NAME']);
 $triggers=(int)$sbx->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE='dictionary'")->fetchColumn();
 s21Ensure($triggers===0,'Unexpected dictionary triggers');
 if($prepared && $scale>1) {
  for($layer=1;$layer<$scale;$layer++) {
   $from=$layer*S21_OFFSET;$to=($layer+1)*S21_OFFSET;
   $n=(int)$sbx->query("SELECT COUNT(*) FROM dictionary WHERE id >= $from AND id < $to AND status='disetujui'")->fetchColumn();
   s21Ensure($n===S21_APPROVED,'Replica layer count mismatch: '.$layer);
  }
 }
 // Cheap representative checks; full projection is verified during --prepare and each tested arm uses digest parity.
 $ids=[1,12,220,1003,5000];
 foreach($ids as $id) {
  $q=$sbx->prepare("SELECT id,category_id,status,tolour_word,indonesian_meaning FROM dictionary WHERE id=?");
  $q->execute([$id]);$r=$q->fetch();$q->closeCursor();
  if(!$r || $r['status']!=='disetujui')continue;
  IndexStage3IncrementalMaintainer::assertProjection($sbx,$id,$r);
  if($prepared && $scale>1) {
   $cloneId=S21_OFFSET+$id;$r['id']=$cloneId;
   IndexStage3IncrementalMaintainer::assertProjection($sbx,$cloneId,$r);
  }
 }
 return ['source'=>$base,'main'=>$mainCount,'sandbox'=>$actual,'scale'=>$scale,
   'synthetic_approved_replicas'=>($scale-1)*4587,'prepared'=>$prepared,
   'replica_semantics'=>'exact copies of real lexical entries, ID-offset only; artificial collisions and duplicate term distribution'];
}
// Stage21 cross-scale fixture lock: original approved rows only, no replica IDs.
function s21Pool(PDO $sbx):array {
 $a=[];
 foreach($sbx->query("SELECT id,category_id,tolour_word,indonesian_meaning FROM dictionary WHERE id < 1000000 AND status='disetujui' AND CHAR_LENGTH(tolour_word)<=130 AND TRIM(tolour_word)<>'' AND TRIM(indonesian_meaning)<>'' ORDER BY id") as $row) {
  $a[]=['id'=>(int)$row['id'],'category_id'=>(int)$row['category_id'],'tolour_word'=>(string)$row['tolour_word'],'indonesian_meaning'=>(string)$row['indonesian_meaning']];
 }
 s21Ensure(count($a)>=500,'Insufficient eligible approved fixtures');return $a;
}
// Pin scale=1 deterministic ordering at every scale, preserving already-audited S1 data.
function s21Plan(array $pool,int $scale,int $batch,int $rep,array $categories):array {
 $rows=$pool;
 usort($rows,static function($a,$b) use ($batch,$rep) {
  $x=hash('sha256',S21_SEED.'|1|'.$batch.'|'.$rep.'|'.$a['id']);
  $y=hash('sha256',S21_SEED.'|1|'.$batch.'|'.$rep.'|'.$b['id']);
  return strcmp($x,$y);
 });
 $actions=[];$types=['word','meaning','category','unpublish'];
 for($i=0;$i<$batch;$i++) {
  $r=$rows[$i];$id=$r['id'];$type=$types[($i+$rep)%4];$oldcat=$r['category_id'];
  if($type==='word'){$column='tolour_word';$value=$r['tolour_word'].' stage21x';}
  elseif($type==='meaning'){$column='indonesian_meaning';$value=$r['indonesian_meaning'].' stage21x';}
  elseif($type==='category') {
   $column='category_id';$value=null;
   foreach($categories as $cat)if((int)$cat!==$oldcat){$value=(int)$cat;break;}
   s21Ensure($value!==null,'No alternate category available');
  } else {$column='status';$value='menunggu';}
  $actions[]=['id'=>$id,'type'=>$type,'column'=>$column,'value'=>$value];
 }
 return $actions;
}
/**
 * Stage22D controlled B2/C2 comparator. NOT a replacement for frozen Stage3.
 * Both arms acquire the SAME shared meta lock once per batch, fetch+lock SAME full
 * old entry/postings per changed row, calculate SAME projection, use SAME metadata
 * update policy once per batch. Only posting write policy is different:
 * B2 refreshes all surviving postings, C2 executes set-difference writes.
 * Caller owns transaction; this function does not commit.
 */
function s22dControlledMaintain(PDO $db,array $after,string $arm):array {
 s21Ensure($db->inTransaction(),'Controlled fairness maintenance requires transaction');
 s21Ensure(in_array($arm,['B2_refresh_matched','C2_delta_matched'],true),'Unknown controlled policy');
 $phase=['meta_lock_ms'=>0.0,'entry_postings_read_lock_ms'=>0.0,'project_and_diff_ms'=>0.0,'entry_write_ms'=>0.0,'postings_write_ms'=>0.0,'meta_update_ms'=>0.0];
 $t=hrtime(true);
 $meta=$db->query('SELECT * FROM idx_stage2_meta WHERE id=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
 s21Ensure($meta && $meta['schema_version']===IndexStage2Materializer::SCHEMA_VERSION,'Metadata schema mismatch');
 $phase['meta_lock_ms']+=(hrtime(true)-$t)/1e6;
 $getEntry=$db->prepare('SELECT * FROM idx_stage2_entries WHERE dictionary_id=? FOR UPDATE');
 $getTerms=$db->prepare('SELECT kind,term_sha256,normalized_term FROM idx_stage2_terms WHERE dictionary_id=? ORDER BY kind,term_sha256 FOR UPDATE');
 $delEntry=$db->prepare('DELETE FROM idx_stage2_entries WHERE dictionary_id=?');
 $updEntry=$db->prepare('UPDATE idx_stage2_entries SET category_id=?,normalized_word=?,normalized_meaning=?,content_sha256=? WHERE dictionary_id=?');
 $insEntry=$db->prepare('INSERT INTO idx_stage2_entries (dictionary_id,category_id,normalized_word,normalized_meaning,content_sha256) VALUES (?,?,?,?,?)');
 $delAll=$db->prepare('DELETE FROM idx_stage2_terms WHERE dictionary_id=?');
 $delOne=$db->prepare('DELETE FROM idx_stage2_terms WHERE dictionary_id=? AND kind=? AND term_sha256=?');
 $insTerm=$db->prepare('INSERT INTO idx_stage2_terms (dictionary_id,kind,term_sha256,normalized_term) VALUES (?,?,?,?)');
 $stats=['changed_rows'=>count($after),'entry_writes'=>0,'postings_deleted'=>0,'postings_inserted'=>0,'entry_delta'=>0,'posting_delta'=>0,'noop_entries'=>0];
 foreach($after as $a){
  $id=(int)$a['id'];$row=$a['row'];
  $t=hrtime(true);
  $getEntry->execute([$id]);$before=$getEntry->fetch(PDO::FETCH_ASSOC)?:null;$getEntry->closeCursor();
  $getTerms->execute([$id]);$old=[];
  foreach($getTerms->fetchAll(PDO::FETCH_ASSOC) as $term){
   $key=(string)$term['kind'].'|'.(string)$term['term_sha256'];
   s21Ensure(!array_key_exists($key,$old),'Duplicate posting key');
   $old[$key]=(string)$term['normalized_term'];
  }
  $getTerms->closeCursor();
  $phase['entry_postings_read_lock_ms']+=(hrtime(true)-$t)/1e6;
  s21Ensure($before!==null||$old===[],'Orphan postings before mutation');
  $t=hrtime(true);
  $p=$row===null?null:IndexStage2Materializer::makeProjection($row);
  if($p!==null)s21Ensure((int)$p['dictionary_id']===$id,'Projection ID mismatch');
  $new=[];
  if($p!==null){foreach($p['postings'] as $key=>[$kind,$term]){$new[$key]=(string)$term;}}
  foreach(array_intersect_key($old,$new) as $key=>$value)
   s21Ensure(hash_equals($value,$new[$key]),'Digest collision/text mismatch');
  $toDelete=array_diff_key($old,$new);$toInsert=array_diff_key($new,$old);
  $deltaE=($p===null?0:1)-($before===null?0:1);
  $deltaT=count($new)-count($old);
  $stats['entry_delta']+=$deltaE;$stats['posting_delta']+=$deltaT;
  $phase['project_and_diff_ms']+=(hrtime(true)-$t)/1e6;
  $t=hrtime(true);
  $changed=true;
  if($p===null){
   if($before!==null){
    $delEntry->execute([$id]);s21Ensure($delEntry->rowCount()===1,'Delete entry mismatch');$stats['entry_writes']++;
   }
  } elseif($before===null){
   $insEntry->execute([$id,$p['category_id'],$p['normalized_word'],$p['normalized_meaning'],$p['content_sha256']]);
   s21Ensure($insEntry->rowCount()===1,'Insert entry mismatch');$stats['entry_writes']++;
  } else {
   $changed=(int)$before['category_id']!==(int)$p['category_id']||
     (string)$before['normalized_word']!==(string)$p['normalized_word']||
     (string)$before['normalized_meaning']!==(string)$p['normalized_meaning']||
     (string)$before['content_sha256']!==(string)$p['content_sha256'];
   if($changed){
    $updEntry->execute([$p['category_id'],$p['normalized_word'],$p['normalized_meaning'],$p['content_sha256'],$id]);
    s21Ensure($updEntry->rowCount()===1,'Update entry mismatch');$stats['entry_writes']++;
   }
  }
  $phase['entry_write_ms']+=(hrtime(true)-$t)/1e6;
  $t=hrtime(true);
  if($p===null){
   // Only entry deletion, FK cascade removes postings; shared for B2/C2.
   $stats['postings_deleted']+=count($old);
  } elseif($arm==='B2_refresh_matched'){
   $delAll->execute([$id]);s21Ensure($delAll->rowCount()===count($old),'Full refresh deleted count mismatch');
   $stats['postings_deleted']+=count($old);
   foreach($p['postings'] as [$kind,$term]){
    $insTerm->execute([$id,$kind,hash('sha256',$term),$term]);
    s21Ensure($insTerm->rowCount()===1,'B2 insertion mismatch');$stats['postings_inserted']++;
   }
  } else {
   foreach($toDelete as $key=>$_){
    [$kind,$sha]=explode('|',$key,2);
    $delOne->execute([$id,$kind,$sha]);s21Ensure($delOne->rowCount()===1,'C2 deletion mismatch');$stats['postings_deleted']++;
   }
   foreach($toInsert as $key=>$term){
    [$kind,$sha]=explode('|',$key,2);
    s21Ensure(hash_equals(hash('sha256',$term),$sha),'C2 term hash mismatch');
    $insTerm->execute([$id,$kind,$sha,$term]);s21Ensure($insTerm->rowCount()===1,'C2 insertion mismatch');$stats['postings_inserted']++;
   }
  }
  $phase['postings_write_ms']+=(hrtime(true)-$t)/1e6;
  if($before!==null && $p!==null && !$changed && $toDelete===[] && $toInsert===[])$stats['noop_entries']++;
 }
 $t=hrtime(true);
 if($stats['entry_delta']!==0||$stats['posting_delta']!==0){
  $st=$db->prepare('UPDATE idx_stage2_meta SET materialized_entries=materialized_entries+?,materialized_terms=materialized_terms+? WHERE id=1');
  $st->execute([$stats['entry_delta'],$stats['posting_delta']]);
  s21Ensure($st->rowCount()===1,'Batch metadata update mismatch');
  $stats['metadata_update_statements']=1;
 } else {$stats['metadata_update_statements']=0;}
 $phase['meta_update_ms']+=(hrtime(true)-$t)/1e6;
 $stats['postings_total_writes']=$stats['postings_deleted']+$stats['postings_inserted'];
 $stats['metadata_lock_statements']=1;
 $stats['entry_lock_queries']=count($after);
 $stats['posting_lock_queries']=count($after);
 $stats['controlled_shared_policy']='full old-posting prefetch FOR UPDATE per row, one metadata lock and at most one metadata UPDATE per batch, same projection';
 foreach($phase as $name=>$duration)$phase[$name]=round($duration,5);
 $stats['phase_ms']=$phase;
 return $stats;
}

function s21IndexDigest(PDO $db):string {
 $h=hash_init('sha256');
 foreach([
  'SELECT dictionary_id,category_id,normalized_word,normalized_meaning,content_sha256 FROM idx_stage2_entries ORDER BY dictionary_id',
  'SELECT dictionary_id,kind,term_sha256,normalized_term FROM idx_stage2_terms ORDER BY dictionary_id,kind,term_sha256',
  'SELECT id,schema_version,baseline_dictionary_sha256,materialized_entries,materialized_terms FROM idx_stage2_meta ORDER BY id'
 ] as $i=>$sql){
  hash_update($h,"table".$i."\n");
  foreach($db->query($sql,PDO::FETCH_ASSOC) as $r)
   hash_update($h,json_encode(array_values($r),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
 }
 return hash_final($h);
}
function s21OneArmCommitted(PDO $sbx,string $arm,array $actions,array $baselineCounts):array {
 $sbx->beginTransaction();
 try {
  $mutStart=hrtime(true);$after=[];
  foreach($actions as $action){
   $col=$action['column'];s21Ensure(in_array($col,['tolour_word','indonesian_meaning','category_id','status'],true),'Invalid mutation column');
   $st=$sbx->prepare('UPDATE dictionary SET '.$col.'=? WHERE id=? AND status=\'disetujui\'');
   $st->execute([$action['value'],$action['id']]);
   s21Ensure($st->rowCount()===1,'Expected exactly one mutated dictionary row: '.$action['id']);
   $q=$sbx->prepare('SELECT * FROM dictionary WHERE id=?');$q->execute([$action['id']]);
   $row=$q->fetch(PDO::FETCH_ASSOC);$q->closeCursor();
   s21Ensure($row!==false,'Updated row missing');
   $after[]=['id'=>$action['id'],'row'=>$row['status']==='disetujui'?$row:null];
  }
  $mutEnd=hrtime(true);
  $detail=s22dControlledMaintain($sbx,$after,$arm);
  $maintEnd=hrtime(true);
  // All correctness assertions remain OUTSIDE mutation/maintenance segments.
  $verificationStart=hrtime(true);
  $newCounts=s21Counts($sbx);$expected=$baselineCounts;
  $countMeta=$sbx->query('SELECT materialized_entries,materialized_terms FROM idx_stage2_meta WHERE id=1')->fetch(PDO::FETCH_ASSOC);
  s21Ensure($countMeta && (int)$countMeta['materialized_entries']===$newCounts['entries'] && (int)$countMeta['materialized_terms']===$newCounts['terms'],'Metadata counters drifted from indexed tables');
  $unpublished=count(array_filter($actions,static fn($x)=>$x['type']==='unpublish'));
  $expected['approved']-=$unpublished;$expected['entries']-=$unpublished;
  s21Ensure($newCounts['dictionary']===$expected['dictionary']&&$newCounts['approved']===$expected['approved']&&$newCounts['entries']===$expected['entries'],'Counts incorrect');
  foreach($after as $a)IndexStage3IncrementalMaintainer::assertProjection($sbx,(int)$a['id'],$a['row']);
  $digest=s21IndexDigest($sbx);
  $verificationEnd=hrtime(true);
  $result=['arm'=>$arm,'mutation_ms'=>round(($mutEnd-$mutStart)/1e6,5),
   'maintenance_ms'=>round(($maintEnd-$mutEnd)/1e6,5),
   'mutation_plus_maintenance_ms'=>round(($maintEnd-$mutStart)/1e6,5),
   'index_digest_sha256'=>$digest,'verification_excluded'=>true,
   'verification_ms_excluded'=>round(($verificationEnd-$verificationStart)/1e6,5),
   'index_operations'=>$detail,'counts_after_mutation'=>$newCounts];
  $commitStart=hrtime(true);$sbx->commit();$commitEnd=hrtime(true);
  $result['commit_ms']=round(($commitEnd-$commitStart)/1e6,5);
  $result['mutation_maintenance_commit_ms']=round((($maintEnd-$mutStart)+($commitEnd-$commitStart))/1e6,5);
  $result['wall_mutation_to_commit_including_verification_ms']=round(($commitEnd-$mutStart)/1e6,5);
  $result['timing_definition']='segmented mutation + maintenance + COMMIT, excluding verification/snapshot/restore/drop';
  $result['transaction']='COMMITTED_ON_DISPOSABLE_WORK_DB_ONLY';
  return $result;
 }finally{if($sbx->inTransaction())$sbx->rollBack();}
}

function s21Stats(array $vals):array {
 $n=count($vals);if(!$n)return ['n'=>0];sort($vals,SORT_NUMERIC);$mean=array_sum($vals)/$n;
 $v=0;foreach($vals as $x)$v+=($x-$mean)**2;
 return ['n'=>$n,'median'=>round($n%2?$vals[intdiv($n,2)]:($vals[$n/2-1]+$vals[intdiv($n,2)])/2,5),
  'mean'=>round($mean,5),'p95'=>round($vals[(int)ceil($n*.95)-1],5),
  'sample_sd'=>round(sqrt($n>1?$v/($n-1):0),5),'min'=>round($vals[0],5),'max'=>round($vals[$n-1],5)];
}

/** Execute a trusted local binary with no shell and fixed arguments. */
function s21Process(array $cmd, ?string $stdinFile=null):void {
 $tmp=sys_get_temp_dir();
 $out=tempnam($tmp,'s21out_');$err=tempnam($tmp,'s21err_');
 s21Ensure($out!==false && $err!==false,'Cannot create process diagnostics');
 $spec=[0=>$stdinFile!==null?['file',$stdinFile,'r']:['pipe','r'],
        1=>['file',$out,'w'],2=>['file',$err,'w']];
 try {
  $proc=proc_open($cmd,$spec,$pipes,dirname(__DIR__),null,['bypass_shell'=>true]);
  s21Ensure(is_resource($proc),'Cannot start local MariaDB utility '.basename($cmd[0]));
  if($stdinFile===null && isset($pipes[0]))fclose($pipes[0]);
  $exit=proc_close($proc);
  if($exit!==0){$message=(string)file_get_contents($err);
    throw new RuntimeException('MariaDB process '.basename($cmd[0]).' exited '.$exit.': '.substr(trim($message),0,1200));}
 } finally { @unlink($out);@unlink($err); }
}
function s21Admin():PDO {
 $db=new PDO('mysql:host=127.0.0.1;charset=utf8mb4','root','',[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>15]);
 return $db;
}
function s21WorkExists(PDO $admin,int $scale):bool {
 $q=$admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
 $q->execute([s21Work($scale)]);return (int)$q->fetchColumn()>0;
}
function s21Tools():array {
 // XAMPP Windows only. Refuse unsupported environments rather than running shell fallbacks.
 $bin='C:/xampp/mysql/bin/';$dump=$bin.'mysqldump.exe';$mysql=$bin.'mysql.exe';
 s21Ensure(is_file($dump) && is_file($mysql),'Missing C:/xampp/mysql/bin/mysqldump.exe or mysql.exe');
 return [$dump,$mysql];
}
function s21GuardSnapshotSql(string $file):void {
 $f=fopen($file,'rb');s21Ensure($f!==false,'Cannot inspect SQL snapshot');
 try {
  while(($line=fgets($f))!==false) {
   // Dump should use the DB supplied to mysql CLI. Never allow source-changing directives.
   if(preg_match('/^\\s*(?:USE\\s+|CREATE\\s+DATABASE\\b|DROP\\s+DATABASE\\b|\\\\connect\\s+)/i',$line))
    throw new RuntimeException('Snapshot has a database-switch/create/drop directive; REFUSE IMPORT');
  }
 } finally {fclose($f);}
}
function s21CreateSnapshot(string $dump,int $scale):array {
 $file=tempnam(sys_get_temp_dir(),'s21_snapshot_s'.$scale.'_');
 s21Ensure($file!==false,'Cannot allocate protected snapshot file');
 $dbname=s21Sbx($scale);
 try {
  // Dump includes table definitions, data, indexes and FK metadata, no CREATE DATABASE.
  s21Process([$dump,'-u','root','--host=127.0.0.1','--single-transaction','--quick',
    '--default-character-set=utf8mb4','--result-file='.$file,$dbname]);
  $size=filesize($file);s21Ensure($size!==false && $size>100000,'Snapshot dump empty or suspiciously small');
  s21GuardSnapshotSql($file);
  return ['file'=>$file,'sha256'=>hash_file('sha256',$file),'bytes'=>$size];
 } catch(Throwable $e){@unlink($file);throw $e;}
}
function s21CreateWork(PDO $admin,string $mysql,array $snapshot,int $scale):void {
 s21Ensure(!s21WorkExists($admin,$scale),'Disposable work database already exists: STOP without overwrite; inspect first');
 $name=s21Work($scale);
 // Fixed internally generated allowlist names only. No user supplied SQL identifiers.
 $admin->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
 try {
  s21Process([$mysql,'-u','root','--host=127.0.0.1','--default-character-set=utf8mb4',$name],$snapshot['file']);
 } catch(Throwable $e) {
  // Leave work database intact on any import failure for diagnosis.
  throw new RuntimeException('Work snapshot import failed; work database retained for inspection. '.$e->getMessage());
 }
}
function s21DropWork(PDO $admin,int $scale):void {
 $name=s21Work($scale);
 s21Ensure(s21WorkExists($admin,$scale),'Expected disposable work database missing');
 $admin->exec('DROP DATABASE `'.$name.'`');
 s21Ensure(!s21WorkExists($admin,$scale),'Work database cleanup incomplete');
}
/** Stage22D checkpointing only happens after each completed paired run; outside all phase timers. */
function s22SaveCheckpoint(string $path,array $obj):void {
 $json=json_encode($obj,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
 $tmp=$path.'.writing';
 s21Ensure(file_put_contents($tmp,$json,LOCK_EX)!==false,'Cannot write Stage22D checkpoint');
 if(is_file($path) && !unlink($path))throw new RuntimeException('Cannot replace old Stage22D checkpoint');
 s21Ensure(rename($tmp,$path),'Cannot finalize Stage22D checkpoint');
}
function s22Duration(float $seconds):string {
 $n=max(0,(int)round($seconds));$h=intdiv($n,3600);$m=intdiv($n%3600,60);$s=$n%60;
 return sprintf('%02d:%02d:%02d',$h,$m,$s);
}
function s22Progress(int $done,int $total,float $start):void {
 $elapsed=microtime(true)-$start;
 $eta=$done>0?($elapsed/$done)*($total-$done):0.0;
 echo sprintf("[STAGE22D] Pairs %d/%d (%.1f%%) | elapsed %s | ETA %s (approx.)\n",
   $done,$total,100.0*$done/$total,s22Duration($elapsed),s22Duration($eta));
}
function s21RunsSafe(PDO $main,PDO $base,PDO $admin,string $mysql,array $snapshot,int $scale,int $batch,int $reps,string $mode,string $arms,array &$events,string $checkpointPath,string $runId):array {
 $pool=s21Pool($base);
 $cats=array_map('intval',$base->query('SELECT id FROM categories ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
 s21Ensure(count($cats)>=2,'Need two or more categories');
 $baseCounts=s21Counts($base);$baseDigest=s21IndexDigest($base);
 $rot=['B2_refresh_matched','C2_delta_matched'];
 $obs=[];
 $progressStart=microtime(true);
 echo "STAGE22D benchmark start: batch=$batch, total_pairs=$reps, arms=B2/C2, scale=1\n";
 echo "ETA appears after the first completed pair, including clone/restore overhead.\n";
 for($rep=0;$rep<$reps;$rep++){
  $actions=s21Plan($pool,1,$batch,$rep,$cats);
  // Counterbalance B2/C2 ordering across the four-kind fixture rotation.
  $offset=($arms==='targeted') ? (($rep+intdiv($rep,4))%2) : ($rep%count($rot));
  $order=array_merge(array_slice($rot,$offset),array_slice($rot,0,$offset));
  $one=[];
  foreach($order as $arm){
   echo 'Comparator 22D '.$mode.' batch='.$batch.' rep='.($rep+1).'/'.$reps.' arm='.$arm."\n";
   $restoreStart=hrtime(true);
   s21CreateWork($admin,$mysql,$snapshot,1);
   $work=s21Connect(s21Work(1),1);
   $loaded=s21Validate($main,$work,1,true);
   s21Ensure($loaded['sandbox']===$baseCounts,'Snapshot count mismatch before arm');
   s21Ensure(s21IndexDigest($work)===$baseDigest,'Pre-arm digest differs from baseline');
   $restoreMs=round((hrtime(true)-$restoreStart)/1e6,3);
   $one[$arm]=s21OneArmCommitted($work,$arm,$actions,$baseCounts);
   $one[$arm]['restore_ms_excluded']=$restoreMs;
   s21Ensure($one[$arm]['counts_after_mutation']===s21Counts($work),'Postcommit counts differ');
   s21Ensure($one[$arm]['index_digest_sha256']===s21IndexDigest($work),'Postcommit index digest differs');
   $work=null;
   $cleanupStart=hrtime(true);s21DropWork($admin,1);
   $one[$arm]['drop_work_db_ms_excluded']=round((hrtime(true)-$cleanupStart)/1e6,3);
   $events[]=['rep'=>$rep+1,'arm'=>$arm,'work_db_disposed'=>true,'restore_ms_excluded'=>$restoreMs];
   s21Ensure(s21IndexDigest($base)===$baseDigest,'Reference sandbox index changed');
  }
  $digests=array_unique(array_map(static fn($r)=>$r['index_digest_sha256'],array_values($one)));
  s21Ensure(count($digests)===1,'POST-MUTATION INDEX PARITY FAILED ACROSS B/C');
  $obs[]=['rep'=>$rep+1,'order'=>$order,'order_scheme'=>($arms==='targeted'?'TARGETED_BLOCK4_COUNTERBALANCED_V2':'THREE_ARM_ROTATION_ORIGINAL'), 'batch_size'=>$batch,
   'mutation_mix'=>array_count_values(array_column($actions,'type')),
   'mutation_id_sha256'=>hash('sha256',implode(',',array_column($actions,'id'))),
   'first_mutation_ids'=>array_slice(array_column($actions,'id'),0,5),
   'parity'=>'PASS','methods'=>$one];
  // Do not place JSON or ETA operations inside the measured maintenance/COMMIT windows.
  s22SaveCheckpoint($checkpointPath,[
   'stage'=>'22D','status'=>'PARTIAL_CHECKPOINT','run_id'=>$runId,
   'protocol'=>S22_PROTOCOL,'batch'=>$batch,'planned_pairs'=>$reps,'completed_pairs'=>$rep+1,
   'historical_abc_runner_sha256'=>S22_HISTORICAL_SHA,
   'initial_reference_index_digest_sha256'=>$baseDigest,
   'runs'=>$obs,'events'=>$events,
   'note'=>'Partial in-order checkpoint evidence. Do not pool pilot 2 pairs with the 30-pair main run.'
  ]);
  s22Progress($rep+1,$reps,$progressStart);
 }
 $summary=['per_method'=>[],'paired_ratios'=>[]];
 foreach($rot as $arm){
  $ms=array_map(static fn($o)=>$o['methods'][$arm]['maintenance_ms'],$obs);
  $end=array_map(static fn($o)=>$o['methods'][$arm]['mutation_maintenance_commit_ms'],$obs);
  $summary['per_method'][$arm]=['maintenance_ms'=>s21Stats($ms),'mutation_maintenance_commit_ms'=>s21Stats($end)];
 }
 foreach([['B2_refresh_matched','C2_delta_matched']] as [$numerator,$denominator]){
  if(!in_array($numerator,$rot,true)||!in_array($denominator,$rot,true))continue;
  $ratios=[];foreach($obs as $o){$den=$o['methods'][$denominator]['maintenance_ms'];s21Ensure($den>0,'Nonpositive denominator');$ratios[]=$o['methods'][$numerator]['maintenance_ms']/$den;}
  $summary['paired_ratios'][$numerator.'_over_'.$denominator]=s21Stats($ratios);
 }
 $wins=['B2'=>0,'C2'=>0,'tie'=>0];$writes=['B2'=>0,'C2'=>0];
 foreach($obs as $o){
  $b=$o['methods']['B2_refresh_matched'];$c=$o['methods']['C2_delta_matched'];
  if($b['maintenance_ms']<$c['maintenance_ms'])$wins['B2']++;
  elseif($c['maintenance_ms']<$b['maintenance_ms'])$wins['C2']++;
  else $wins['tie']++;
  $writes['B2']+=$b['index_operations']['postings_total_writes'];
  $writes['C2']+=$c['index_operations']['postings_total_writes'];
  s21Ensure($b['index_operations']['metadata_lock_statements']===1 && $c['index_operations']['metadata_lock_statements']===1,'Metadata lock policy drift');
  s21Ensure($b['index_operations']['entry_lock_queries']===$batch && $c['index_operations']['entry_lock_queries']===$batch,'Entry lock policy drift');
  s21Ensure($b['index_operations']['posting_lock_queries']===$batch && $c['index_operations']['posting_lock_queries']===$batch,'Posting lock policy drift');
  s21Ensure($b['index_operations']['metadata_update_statements']===$c['index_operations']['metadata_update_statements'],'Metadata update frequency mismatch');
 }
 $summary['paired_wins_maintenance']=$wins;
 $summary['posting_writes_total']=$writes;
 $summary['posting_write_reduction_C2_percent']=$writes['B2']>0?round(100*(1-$writes['C2']/$writes['B2']),4):null;
 foreach(['B2_refresh_matched','C2_delta_matched'] as $arm){
  foreach(['meta_lock_ms','entry_postings_read_lock_ms','project_and_diff_ms','entry_write_ms','postings_write_ms','meta_update_ms'] as $phase){
   $summary['per_method'][$arm]['phase_ms'][$phase]=s21Stats(array_map(static fn($o)=>$o['methods'][$arm]['index_operations']['phase_ms'][$phase],$obs));
  }
 }
 return ['runs'=>$obs,'summary'=>$summary,'all_pair_digests_identical'=>true,
  'all_work_databases_disposed'=>true,'scope'=>'Stage22D matched-control experiment: B2/C2 BOTH use one FOR UPDATE metadata lock, one aggregate metadata UPDATE, full posting prefetch+FOR UPDATE per changed row. Only posting write policy differs. CLI transaction time, not production latency.'];
}
function abcWriteReport(array $report,string $root,string $mode,int $batch,string $arms,string $runId):string {
 $folder=$root.'/tests/stage22/reports';if(!is_dir($folder)&&!mkdir($folder,0775,true)&&!is_dir($folder))throw new RuntimeException('Cannot create reports');
 $name='STAGE22D_B'.$batch.'_'.(($report['reps']??0)===30?'30PAIRS':(($report['reps']??0)===2?'2PAIR_PILOT':'CHECK')).'_'.$runId.'.json';
 $path=$folder.'/'.$name;
 s21Ensure(file_put_contents($path,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n")!==false,'Evidence save failure');
 return $path;
}
$runId=date('Ymd_His').'_'.bin2hex(random_bytes(4));
$checkpointPath=$root.'/tests/stage22/reports/STAGE22D_CHECKPOINT_B'.$batch.'_'.$runId.'.json';
$report=['stage'=>'22D','protocol'=>S22_PROTOCOL,
 'historical_abc_runner_sha256'=>S22_HISTORICAL_SHA,'run_id'=>$runId,
 'checkpoint'=>basename($checkpointPath),
 'purpose'=>'matched_metadata_and_locks_controlled_B2_refresh_vs_C2_delta',
 'mode'=>$mode,'scale'=>1,'batch'=>$batch,'reps'=>$reps,'arms'=>$arms,
 'timestamp_wita'=>date('c'),'main_database'=>S21_MAIN,
 'prepared_reference_database'=>s21Sbx(1),'disposable_work_database'=>s21Work(1),
 'source_modified'=>false,'source_1x_original'=>true,'status'=>'INCOMPLETE',
 'note'=>'Same deterministic fixtures as Stage22C indices 0..N-1. Pilot and main are separate. This is an ablation, not a replacement of B/C production code.',
 'warnings'=>['Experimental B2/C2 implementations, NOT historical/production B/C.',
  'Metadata lock and metadata-update frequency matched; full posting read and entry/posting locks matched for both.',
  'B2 is intentionally made to perform the same full-row read even though original B does not require it.',
  'Same corpus and fixture plan as Stage22C but execution-time results are separate protocols.',
  'No concurrency/isolation proof and no production retrieval speedup is implied.']];
// HOTFIX01: initialize the by-reference event log as an array.
$report['events']=[];
$main=null;$sbx=null;$admin=null;$locked=false;$snapshot=null;
try{
 $report['frozen_files']=s21GuardSource($root);
 $main=s21Connect(S21_MAIN,1);$sbx=s21Connect(s21Sbx(1),1);
 $admin=s21Admin();
 s21Ensure(!s21WorkExists($admin,1),'Disposable Stage22D work database already exists: STOP. Never overwrite.');
 $lock='toulour_stage21_s1_exclusive';
 $q=$sbx->prepare('SELECT GET_LOCK(?,0)');$q->execute([$lock]);
 s21Ensure((int)$q->fetchColumn()===1,'Stage21 process currently using this source sandbox');$locked=true;
 $report['initial']=s21Validate($main,$sbx,1,true);
 $report['initial_index_digest_sha256']=s21IndexDigest($sbx);
 $pool=s21Pool($sbx);
 $report['fixture_counts']=['eligible'=>count($pool),'batch'=>$batch,'max_reps'=>$reps];
 if($mode!=='--fair-check'){
  [$dump,$mysql]=s21Tools();
  $t0=hrtime(true);$snapshot=s21CreateSnapshot($dump,1);
  $report['snapshot']=['sha256'=>$snapshot['sha256'],'bytes'=>$snapshot['bytes'],
   'creation_ms_excluded'=>round((hrtime(true)-$t0)/1e6,3)];
  $report['benchmark']=s21RunsSafe($main,$sbx,$admin,$mysql,$snapshot,1,$batch,$reps,$mode,$arms,$report['events'],$checkpointPath,$runId);
 }
 $report['final']=s21Validate($main,$sbx,1,true);
 s21Ensure(s21IndexDigest($sbx)===$report['initial_index_digest_sha256'],'REFERENCE SANDBOX CHANGED');
 s21Ensure(!s21WorkExists($admin,1),'Disposable work DB remains after nominal PASS');
 $report['status']='PASS';
}catch(Throwable $e){
 $report['status']='FAIL';$report['error']=$e->getMessage();
 $report['work_db_exists_after_failure']=$admin instanceof PDO?s21WorkExists($admin,1):null;
 fwrite(STDERR,'STAGE22D FAIL: '.$e->getMessage()."\n");
}finally{
 if($snapshot!==null && isset($snapshot['file']))@unlink($snapshot['file']);
 if($locked && $sbx instanceof PDO){try{$q=$sbx->prepare('SELECT RELEASE_LOCK(?)');$q->execute([$lock]);}catch(Throwable $ignored){}}
}
try{$path=abcWriteReport($report,$root,$mode,$batch,$arms,$runId);echo 'STAGE22D '.strtoupper($mode).': '.$report['status']."\nJSON: $path\n";}
catch(Throwable $e){fwrite(STDERR,'Cannot save JSON: '.$e->getMessage()."\n");exit(1);}
if($report['status']!=='PASS')exit(1);
