<?php
require __DIR__.'/../src/dedupe.php';
require __DIR__.'/../src/validate.php';
require __DIR__.'/../src/database.php';
try{
 need(isset($argv[1]),'Usage: ingest.php bundle.json [config.php]');
 $bundle=validateBundle(json_decode(file_get_contents($argv[1]),true));
 $config=require (isset($argv[2])?$argv[2]:__DIR__.'/../data/database.local.php');
 $db=openDatabase($config);
 $db->beginTransaction();
 $history=array_map(function($body){return json_decode($body,true);},$db->query('SELECT body FROM entries ORDER BY edition_date,id')->fetchAll(PDO::FETCH_COLUMN));
 $merged=mergeUnseenItems($bundle['items'],$history,$bundle['date']);
 $bundle['items']=$merged['items'];
 if(!count($bundle['items'])){$db->rollBack();echo json_encode(array('published'=>false,'reason'=>'all_items_previously_published','skipped'=>$merged['skipped']))."\n";exit(0);}
 $json=json_encode($bundle,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 $stmt=$db->prepare('INSERT INTO editions(edition_date,collected_at,bundle,bundle_hash) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE collected_at=VALUES(collected_at),bundle=VALUES(bundle),bundle_hash=VALUES(bundle_hash)');
 $stmt->execute(array($bundle['date'],$bundle['collectedAt'],$json,hash('sha256',$json)));
 $db->prepare('DELETE FROM entries WHERE edition_date=?')->execute(array($bundle['date']));
 $stmt=$db->prepare('INSERT INTO entries(id,kind,edition_date,url_hash,category,region,title,score,body) VALUES(?,?,?,?,?,?,?,?,?)');
 foreach($bundle['items'] as $item)$stmt->execute(array($item['id'],$item['kind'],$bundle['date'],hash('sha256',$item['url']),$item['category'],$item['region'],$item['title'],$item['score'],json_encode($item,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
 $db->prepare('INSERT INTO collection_runs(edition_date,status,detail) VALUES(?,?,?)')->execute(array($bundle['date'],'success',json_encode(array('count'=>count($bundle['items']),'duplicatesSkipped'=>$merged['skipped'],'warnings'=>isset($bundle['warnings'])?$bundle['warnings']:array()),JSON_UNESCAPED_UNICODE)));
 $db->commit();
 echo json_encode(array('published'=>true,'date'=>$bundle['date'],'count'=>count($bundle['items']),'duplicatesSkipped'=>$merged['skipped']),JSON_UNESCAPED_UNICODE)."\n";
}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();fwrite(STDERR,'Ingest failed: '.$e->getMessage()."\n");exit(1);}
