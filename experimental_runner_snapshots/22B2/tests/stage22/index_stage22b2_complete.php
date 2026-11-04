<?php
/**
 * Stage 22B2 - controlled publish-pending COMMIT parity and explicit ROLLBACK parity.
 * Safeguards: immutable V16 hashes, fixed database names, lock, disposable database only.
 * No DDL/DML on source or reference; never overwrites an existing work database.
 * Original ABC runner and services are NOT changed.
 * Requires Windows XAMPP PHP 8.2 and MariaDB; NOT run on live user DB by author.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
set_time_limit(0);ini_set('memory_limit','2048M');date_default_timezone_set('Asia/Makassar');
const S22_SOURCE = 'kamus_tolour_index_v2';
const S22_REFERENCE = 'kamus_tolour_index_stage21_s1_sandbox';
const S22_WORK = 'kamus_tolour_index_stage22b2_work_sandbox';
const S22_CORE_SHA = '8676384ea28e4cc98fa8e5e50c8f221cb5a2746e91259a28f8b7ebe3085c39d4';
const S22_ABC_SHA = '3defe1d359cbd4ba8daea95c329e71748c8beb4609bab3c0fa75662d4521dd19';
const S22_FILES = [
 'services/SearchService.php'=>'bd92f47062730315e21939ca2f0eb3c94dd8e28b30a9db93e816d0f75f30e95f',
 'services/IndexStage2Materializer.php'=>'4bfbeb5bb14b97330000bee12cacc9517cf585f7c1c816bde8bb50392c489caa',
 'services/IndexStage3IncrementalMaintainer.php'=>'b2c00ba1e8f34d4ad0b14311702d05b83ff6c92678254a36beef49447269a02f',
 'models/Dictionary.php'=>'016fc74ce2f560757302a757b5041d4ee3c773f3fe7c59fa3d95d070aba64c98',
];
$root=dirname(__DIR__,2);
if (basename($root)!=='kamus_tolour_index_v1' || count($argv)!==1) { fwrite(STDERR,"Run from kamus_tolour_index_v1 via BAT, no arguments.\n");exit(2); }
require_once $root.'/services/IndexStage3IncrementalMaintainer.php';
function s21Ensure(bool $ok,string $msg):void {if(!$ok)throw new RuntimeException('STOP: '.$msg);}
function s22Pdo(string $db):PDO {
 s21Ensure(in_array($db,[S22_SOURCE,S22_REFERENCE,S22_WORK],true),'Non-allowlisted DB name');
 $pdo=new PDO('mysql:host=127.0.0.1;dbname='.$db.';charset=utf8mb4','root','',[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>15]);
 s21Ensure($pdo->query('SELECT DATABASE()')->fetchColumn()===$db,'DB identity mismatch');
 $pdo->exec("SET time_zone='+08:00'");return $pdo;
}
function s22Admin():PDO {return new PDO('mysql:host=127.0.0.1;charset=utf8mb4','root','',[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>15]);}
function s22Exists(PDO $admin):bool {
 $q=$admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$q->execute([S22_WORK]);
 return ((int)$q->fetchColumn())>0;
}
function s22Counts(PDO $db):array {return [
 'dictionary'=>(int)$db->query('SELECT COUNT(*) FROM dictionary')->fetchColumn(),
 'approved'=>(int)$db->query("SELECT COUNT(*) FROM dictionary WHERE status='disetujui'")->fetchColumn(),
 'entries'=>(int)$db->query('SELECT COUNT(*) FROM idx_stage2_entries')->fetchColumn(),
 'terms'=>(int)$db->query('SELECT COUNT(*) FROM idx_stage2_terms')->fetchColumn()];}
function s22CoreHash(PDO $db):array {
 $keys=['id','category_id','contributor_id','status','tolour_word','indonesian_meaning','example_sentence','description','is_edit','original_id'];
 $hash=hash_init('sha256');$count=0;$approved=0;
 foreach($db->query('SELECT * FROM dictionary WHERE id < 1000000 ORDER BY id', PDO::FETCH_ASSOC) as $r){
  $count++;if($r['status']==='disetujui')$approved++;
  $vals=[];foreach($keys as $key)$vals[]=$r[$key]??null;
  hash_update($hash,json_encode($vals,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)."\n");
 }
 return ['total'=>$count,'approved'=>$approved,'sha256'=>hash_final($hash)];
}
function s22IndexDigest(PDO $db):string {
 $h=hash_init('sha256');
 foreach([
 'SELECT dictionary_id,category_id,normalized_word,normalized_meaning,content_sha256 FROM idx_stage2_entries ORDER BY dictionary_id',
 'SELECT dictionary_id,kind,term_sha256,normalized_term FROM idx_stage2_terms ORDER BY dictionary_id,kind,term_sha256',
 'SELECT id,schema_version,baseline_dictionary_sha256,materialized_entries,materialized_terms FROM idx_stage2_meta ORDER BY id'
 ] as $i=>$sql) {
  hash_update($h,"table".$i."\n");
  foreach($db->query($sql,PDO::FETCH_ASSOC) as $r)
   hash_update($h,json_encode(array_values($r),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
 }
 return hash_final($h);
}
function s22Process(array $command,?string $stdinFile=null):void {
 $out=tempnam(sys_get_temp_dir(),'s22b2o_');$err=tempnam(sys_get_temp_dir(),'s22b2e_');
 s21Ensure($out!==false&&$err!==false,'Cannot create temp process logs');
 $descriptors=[0=>$stdinFile!==null?['file',$stdinFile,'r']:['pipe','r'],1=>['file',$out,'w'],2=>['file',$err,'w']];
 try {
  $proc=proc_open($command,$descriptors,$pipes,null,null,['bypass_shell'=>true]);
  s21Ensure(is_resource($proc),'Cannot execute local MySQL tool');
  if($stdinFile===null&&isset($pipes[0]))fclose($pipes[0]);
  $exit=proc_close($proc);
  if($exit!==0)throw new RuntimeException('MySQL utility failed ('.$exit.'): '.substr((string)file_get_contents($err),0,1000));
 }finally{ @unlink($out);@unlink($err); }
}
function s22Snapshot(string $dump):array {
 $path=tempnam(sys_get_temp_dir(),'s22b2_');s21Ensure($path!==false,'Cannot create temp SQL snapshot');
 try {
  s22Process([$dump,'-u','root','--host=127.0.0.1','--single-transaction','--quick',
   '--default-character-set=utf8mb4','--result-file='.$path,S22_REFERENCE]);
  $size=filesize($path);s21Ensure($size!==false&&$size>100000,'Snapshot empty/small');
  $f=fopen($path,'rb');s21Ensure($f!==false,'Snapshot unreadable');
  try { while(($line=fgets($f))!==false) {
   if(preg_match('/^\s*(?:USE\s+|CREATE\s+DATABASE\b|DROP\s+DATABASE\b|\\connect\s+)/i',$line))
     throw new RuntimeException('Forbidden database switch in SQL snapshot');
  }}finally{fclose($f);}
  return ['file'=>$path,'sha256'=>hash_file('sha256',$path),'bytes'=>$size];
 }catch(Throwable $e){@unlink($path);throw $e;}
}
function s22CreateWork(PDO $admin,string $mysql,string $snapshot):PDO {
 s21Ensure(!s22Exists($admin),'Work database already exists; NOT overwriting');
 $admin->exec('CREATE DATABASE `'.S22_WORK.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
 // On failure leave work DB for diagnosis; never auto-drop a failed import.
 s22Process([$mysql,'-u','root','--host=127.0.0.1','--default-character-set=utf8mb4',S22_WORK],$snapshot);
 return s22Pdo(S22_WORK);
}
function s22DropWork(PDO $admin):void {
 s21Ensure(s22Exists($admin),'Expected work database missing');
 $admin->exec('DROP DATABASE `'.S22_WORK.'`');
 s21Ensure(!s22Exists($admin),'Could not remove work DB');
}
function s22GetRow(PDO $db,int $id):array {
 $q=$db->prepare('SELECT * FROM dictionary WHERE id=?');$q->execute([$id]);$row=$q->fetch();$q->closeCursor();
 s21Ensure(is_array($row),'Selected dictionary row missing');return $row;
}
function s22Apply(PDO $db,string $arm,int $id,array $row):array {
 if($arm==='differential')return IndexStage3IncrementalMaintainer::apply($db,$id,$row);
 if($arm==='selective_refresh')return abcSelectiveRefresh($db,[['id'=>$id,'row'=>$row]]);
 throw new RuntimeException('Unknown arm');
}

// The following function is copied verbatim from frozen tests/index_comparator_abc.php,
// whose SHA-256 is checked before any connection to the database.
function abcSelectiveRefresh(PDO $db,array $after):array {
 s21Ensure($db->inTransaction(),'Selective refresh requires transaction');
 $meta=$db->query('SELECT id,schema_version FROM idx_stage2_meta WHERE id=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
 s21Ensure($meta && $meta['schema_version']===IndexStage2Materializer::SCHEMA_VERSION,'Missing/incompatible index meta');
 $getEntry=$db->prepare('SELECT dictionary_id FROM idx_stage2_entries WHERE dictionary_id=? FOR UPDATE');
 $getTerms=$db->prepare('SELECT COUNT(*) FROM idx_stage2_terms WHERE dictionary_id=?');
 $delEntry=$db->prepare('DELETE FROM idx_stage2_entries WHERE dictionary_id=?');
 $updEntry=$db->prepare('UPDATE idx_stage2_entries SET category_id=?,normalized_word=?,normalized_meaning=?,content_sha256=? WHERE dictionary_id=?');
 $insEntry=$db->prepare('INSERT INTO idx_stage2_entries (dictionary_id,category_id,normalized_word,normalized_meaning,content_sha256) VALUES (?,?,?,?,?)');
 $delTerms=$db->prepare('DELETE FROM idx_stage2_terms WHERE dictionary_id=?');
 $insTerms=$db->prepare('INSERT INTO idx_stage2_terms (dictionary_id,kind,term_sha256,normalized_term) VALUES (?,?,?,?)');
 $deltaEntries=0;$deltaTerms=0;$deletedPostings=0;$insertedPostings=0;$deletedEntries=0;$updatedEntries=0;
 foreach($after as $a){
  $id=(int)$a['id'];$row=$a['row'];
  $getEntry->execute([$id]);$before=$getEntry->fetch(PDO::FETCH_ASSOC);$getEntry->closeCursor();
  $getTerms->execute([$id]);$oldN=(int)$getTerms->fetchColumn();$getTerms->closeCursor();
  if($before===false && $oldN>0) throw new RuntimeException('Orphan postings under selective refresh');
  if($row===null){
   if($before!==false){$delEntry->execute([$id]);s21Ensure($delEntry->rowCount()===1,'Cannot delete index entry');$deletedEntries++;}
   $newN=0;$newEntry=0;
  }else{
   $p=IndexStage2Materializer::makeProjection($row);
   s21Ensure((int)$p['dictionary_id']===$id,'Projection id mismatch');
   if($before===false){
    $insEntry->execute([$id,$p['category_id'],$p['normalized_word'],$p['normalized_meaning'],$p['content_sha256']]);
   }else{
    $updEntry->execute([$p['category_id'],$p['normalized_word'],$p['normalized_meaning'],$p['content_sha256'],$id]);
   }
   $updatedEntries++;
   $delTerms->execute([$id]);
   s21Ensure($delTerms->rowCount()===$oldN,'Selective refresh deleted posting count mismatch');
   foreach($p['postings'] as [$kind,$term])$insTerms->execute([$id,$kind,hash('sha256',$term),$term]);
   $newN=count($p['postings']);$newEntry=1;$insertedPostings+=$newN;
  }
  $deletedPostings+=$oldN;
  $deltaEntries+=($newEntry-($before===false?0:1));
  $deltaTerms+=$newN-$oldN;
 }
 if($deltaEntries!==0||$deltaTerms!==0){
  $st=$db->prepare('UPDATE idx_stage2_meta SET materialized_entries=materialized_entries+?,materialized_terms=materialized_terms+? WHERE id=1');
  $st->execute([$deltaEntries,$deltaTerms]);
 }
 return ['changed_rows'=>count($after),'postings_deleted'=>$deletedPostings,
  'postings_inserted'=>$insertedPostings,'entries_deleted'=>$deletedEntries,'entries_written'=>$updatedEntries,
  'delta_entries'=>$deltaEntries,'delta_terms'=>$deltaTerms,
  'baseline_policy'=>'refresh all postings per changed entry, even unchanged postings; single aggregate metadata update'];
}


$report=['stage'=>'22B2','status'=>'INCOMPLETE','timestamp_wita'=>date('c'),
 'scope'=>'publish-pending COMMIT B/C parity and explicit ROLLBACK B/C parity, original 1x database',
 'source_db'=>S22_SOURCE,'reference_db'=>S22_REFERENCE,'only_ddl_target'=>S22_WORK,
 'checks'=>[],'warnings'=>['Not a performance benchmark','No concurrency/isolation proof']];
$main=null;$reference=null;$admin=null;$work=null;$snapshot=null;$locked=false;$lock='toulour_stage21_s1_exclusive';
$start=microtime(true);$success=false;
try {
 s21Ensure(extension_loaded('pdo_mysql')&&extension_loaded('mbstring'),'Required PHP extensions unavailable');
 foreach(S22_FILES as $relative=>$expected){
  $file=$root.'/'.$relative;s21Ensure(is_file($file)&&hash_equals($expected,hash_file('sha256',$file)),'Frozen file differs: '.$relative);
 }
 s21Ensure(is_file($root.'/tests/index_comparator_abc.php')&&hash_equals(S22_ABC_SHA,hash_file('sha256',$root.'/tests/index_comparator_abc.php')),'Original ABC runner changed');
 $main=s22Pdo(S22_SOURCE);$reference=s22Pdo(S22_REFERENCE);$admin=s22Admin();
 s21Ensure(!s22Exists($admin),'Stage22B2 work DB preexists. Stop; do not drop it.');
 $q=$reference->prepare('SELECT GET_LOCK(?,0)');$q->execute([$lock]);
 s21Ensure((int)$q->fetchColumn()===1,'Stage21/ABC lock in use');$locked=true;
 $counts=['dictionary'=>5687,'approved'=>4587,'entries'=>4587,'terms'=>29546];
 s21Ensure(s22Counts($main)===$counts&&s22Counts($reference)===$counts,'Unexpected source/reference counts');
 foreach([$main,$reference] as $db){
  $core=s22CoreHash($db);
  s21Ensure($core['total']===5687&&$core['approved']===4587&&hash_equals(S22_CORE_SHA,$core['sha256']),'Source/reference SHA changed');
 }
 $baseDigest=s22IndexDigest($reference);
 $report['baseline_index_sha256']=$baseDigest;$report['baseline_core_sha256']=S22_CORE_SHA;
 // Eligible pending record with valid lexical fields and NO current projection.
 $pending=$reference->query("SELECT d.id FROM dictionary d LEFT JOIN idx_stage2_entries e ON e.dictionary_id=d.id
  WHERE d.id<1000000 AND d.status='menunggu' AND e.dictionary_id IS NULL
  AND d.category_id IS NOT NULL AND TRIM(d.tolour_word)<>'' AND TRIM(d.indonesian_meaning)<>'' ORDER BY d.id LIMIT 1")->fetchColumn();
 s21Ensure($pending!==false,'No valid status=menunggu record for publish-pending');$pending=(int)$pending;
 $report['fixture']=['pending_id'=>$pending,'initial_status'=>'menunggu','new_status'=>'disetujui'];
 $bin='C:/xampp/mysql/bin/';$dump=$bin.'mysqldump.exe';$mysql=$bin.'mysql.exe';
 s21Ensure(is_file($dump)&&is_file($mysql),'XAMPP mysql.exe/mysqldump.exe not found');
 $snapshot=s22Snapshot($dump);$report['snapshot_sha256']=$snapshot['sha256'];
 $methods=['selective_refresh','differential'];$committed=[];
 foreach($methods as $i=>$arm){
  echo '['.($i+1).'/4] publish-pending COMMIT '.$arm.' (elapsed '.round(microtime(true)-$start).'s)'.PHP_EOL;
  $work=s22CreateWork($admin,$mysql,$snapshot['file']);
  s21Ensure(s22Counts($work)===$counts&&s22IndexDigest($work)===$baseDigest,'Work baseline mismatch');
  $work->beginTransaction();
  try {
   $q=$work->prepare("UPDATE dictionary SET status='disetujui' WHERE id=? AND status='menunggu'");$q->execute([$pending]);
   s21Ensure($q->rowCount()===1,'publish-pending mutation did not affect one row');
   $row=s22GetRow($work,$pending);s21Ensure($row['status']==='disetujui','New source status incorrect');
   $ops=s22Apply($work,$arm,$pending,$row);
   IndexStage3IncrementalMaintainer::assertProjection($work,$pending,$row);
   $expected=$counts;$expected['approved']++;$expected['entries']++;
   $post=s22Counts($work);
   s21Ensure($post['dictionary']===$expected['dictionary']&&$post['approved']===$expected['approved']&&$post['entries']===$expected['entries'],'publish count mismatch');
   $digest=s22IndexDigest($work);
   $work->commit();
   s21Ensure(s22IndexDigest($work)===$digest,'Committed digest changed');
   s21Ensure(s22Counts($work)===$post,'Committed counts changed');
   $committed[$arm]=['status'=>'PASS','digest_sha256'=>$digest,'counts_after'=>$post,'operations'=>$ops];
  }finally{if($work->inTransaction())$work->rollBack();}
  $work=null;s22DropWork($admin);s21Ensure(s22IndexDigest($reference)===$baseDigest,'Reference changed');
 }
 s21Ensure(hash_equals($committed['selective_refresh']['digest_sha256'],$committed['differential']['digest_sha256']),'B/C publish-pending digest parity FAILED');
 $report['checks']['publish_pending_commit']=['status'=>'PASS','B_C_digest_identical'=>true,'methods'=>$committed];
 foreach($methods as $i=>$arm){
  echo '['.($i+3).'/4] explicit ROLLBACK '.$arm.' (elapsed '.round(microtime(true)-$start).'s)'.PHP_EOL;
  $work=s22CreateWork($admin,$mysql,$snapshot['file']);
  s21Ensure(s22Counts($work)===$counts&&s22IndexDigest($work)===$baseDigest,'Work baseline mismatch');
  $work->beginTransaction();
  try {
   $q=$work->prepare("UPDATE dictionary SET status='disetujui' WHERE id=? AND status='menunggu'");$q->execute([$pending]);
   s21Ensure($q->rowCount()===1,'Rollback fixture mutation failed');
   $row=s22GetRow($work,$pending);
   $ops=s22Apply($work,$arm,$pending,$row);
   IndexStage3IncrementalMaintainer::assertProjection($work,$pending,$row);
   $during=s22IndexDigest($work);
   s21Ensure(!hash_equals($during,$baseDigest),'Inside transaction index did not change');
   $work->rollBack();
   s21Ensure(s22IndexDigest($work)===$baseDigest,'Rollback did NOT restore index');
   s21Ensure(s22Counts($work)===$counts,'Rollback did NOT restore counts');
   s21Ensure(s22CoreHash($work)['sha256']===S22_CORE_SHA,'Rollback did NOT restore dictionary');
   $after=s22GetRow($work,$pending);s21Ensure($after['status']==='menunggu','Rollback status not restored');
   IndexStage3IncrementalMaintainer::assertProjection($work,$pending,null);
   $report['checks']['explicit_rollback'][$arm]=['status'=>'PASS','index_digest_restored'=>true,'dictionary_core_restored'=>true,'source_status_restored'=>true,'operations_before_rollback'=>$ops];
  }finally{if($work->inTransaction())$work->rollBack();}
  $work=null;s22DropWork($admin);
  s21Ensure(s22IndexDigest($reference)===$baseDigest,'Reference index changed');
 }
 s21Ensure(s22Counts($main)===$counts&&s22Counts($reference)===$counts,'Main or reference counts changed');
 s21Ensure(s22CoreHash($main)['sha256']===S22_CORE_SHA&&s22CoreHash($reference)['sha256']===S22_CORE_SHA,'Main/ref source changed');
 s21Ensure(s22IndexDigest($reference)===$baseDigest,'Reference digest changed after test');
 s21Ensure(!s22Exists($admin),'Work DB left behind');
 $report['source_reference_unchanged']=true;$report['work_db_disposed']=true;
 $report['status']='PASS';$success=true;
}catch(Throwable $e){
 $report['status']='FAIL';$report['error']=$e->getMessage();
 $report['work_db_exists_after_failure']=$admin instanceof PDO?s22Exists($admin):null;
 fwrite(STDERR,'STAGE22B2 FAIL: '.$e->getMessage().PHP_EOL);
}finally{
 if($work instanceof PDO&&$work->inTransaction())$work->rollBack();
 if($snapshot!==null&&isset($snapshot['file']))@unlink($snapshot['file']);
 if($locked&&$reference instanceof PDO){try{$q=$reference->prepare('SELECT RELEASE_LOCK(?)');$q->execute([$lock]);}catch(Throwable $ignore){}}
}
$report['elapsed_seconds']=round(microtime(true)-$start,2);
$dest=__DIR__.'/reports';if(!is_dir($dest))@mkdir($dest,0775,true);
$file=$dest.'/STAGE22B2_PUBLISH_ROLLBACK_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.json';
try{file_put_contents($file,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
 echo 'Stage22B2: '.$report['status'].PHP_EOL.'JSON: '.$file.PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,'Cannot save JSON: '.$e->getMessage().PHP_EOL);exit(1);}
if(!$success)exit(1);
