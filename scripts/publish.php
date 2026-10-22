<?php
require __DIR__.'/../src/dedupe.php';require __DIR__.'/../src/validate.php';require __DIR__.'/../src/render.php';
$configPath=isset($argv[2])?$argv[2]:'/www/xmqibu-ai-private/config.php';
try{
 need(isset($argv[1]),'Usage: publish.php bundle.json [config.php]');$b=validateBundle(json_decode(file_get_contents($argv[1]),true));
 $c=require $configPath;$root=$c['output'];$lock=fopen($root.'/.publish.lock','c');need($lock&&flock($lock,LOCK_EX|LOCK_NB),'Another publication is running');
 $p=new PDO($c['dsn'],$c['user'],$c['password'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false));
 foreach(explode(';',file_get_contents(__DIR__.'/schema.sql')) as $sql)if(trim($sql))$p->exec($sql);
 $history=array_map(function($s){return json_decode($s,true);},$p->query('SELECT body FROM entries ORDER BY edition_date,id')->fetchAll(PDO::FETCH_COLUMN));
 $merged=mergeUnseenItems($b['items'],$history,$b['date']);$b['items']=$merged['items'];
 if(!count($b['items'])){echo json_encode(array('published'=>false,'reason'=>'all_items_previously_published','skipped'=>$merged['skipped']))."\n";exit(0);}
 $json=json_encode($b,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$p->beginTransaction();
 $q=$p->prepare('INSERT INTO editions(edition_date,collected_at,bundle,bundle_hash) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE collected_at=VALUES(collected_at),bundle=VALUES(bundle),bundle_hash=VALUES(bundle_hash)');$q->execute(array($b['date'],$b['collectedAt'],$json,hash('sha256',$json)));
 $p->prepare('DELETE FROM entries WHERE edition_date=?')->execute(array($b['date']));
 $q=$p->prepare('INSERT INTO entries(id,kind,edition_date,url_hash,category,region,title,score,body) VALUES(?,?,?,?,?,?,?,?,?)');
 foreach($b['items'] as $i)$q->execute(array($i['id'],$i['kind'],$b['date'],hash('sha256',$i['url']),$i['category'],$i['region'],$i['title'],$i['score'],json_encode($i,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
 $rows=$p->query('SELECT body FROM entries ORDER BY edition_date DESC,score DESC')->fetchAll(PDO::FETCH_COLUMN);$items=array_map(function($s){return json_decode($s,true);},$rows);
 $editions=$p->query('SELECT edition_date,collected_at FROM editions ORDER BY edition_date DESC')->fetchAll(PDO::FETCH_ASSOC);
 $release=$root.'/releases/'.gmdate('YmdHis').'-'.bin2hex(random_bytes(3));need(mkdir($release,0755,true),'Cannot create release');
 renderSite($items,$editions,$release);
 $p->prepare('INSERT INTO collection_runs(edition_date,status,detail) VALUES(?,?,?)')->execute(array($b['date'],'success',json_encode(array('count'=>count($b['items']),'duplicatesSkipped'=>$merged['skipped'],'release'=>basename($release),'warnings'=>isset($b['warnings'])?$b['warnings']:array()),JSON_UNESCAPED_UNICODE)));
 $p->commit();$tmp=$root.'/current-next-'.bin2hex(random_bytes(3));need(symlink('releases/'.basename($release),$tmp)&&rename($tmp,$root.'/current'),'Cannot switch release');
 echo json_encode(array('published'=>true,'date'=>$b['date'],'count'=>count($b['items']),'duplicatesSkipped'=>$merged['skipped'],'release'=>basename($release)),JSON_UNESCAPED_UNICODE)."\n";
}catch(Throwable $e){if(isset($p)&&$p->inTransaction())$p->rollBack();fwrite(STDERR,'Publication failed: '.$e->getMessage()."\n");exit(1);}
