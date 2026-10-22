<?php
require __DIR__.'/../src/validate.php';
require __DIR__.'/../src/render.php';
$configPath=isset($argv[1])?$argv[1]:'/www/xmqibu-ai-private/config.php';
try{
 $c=require $configPath;$root=$c['output'];
 $lock=fopen($root.'/.publish.lock','c');need($lock&&flock($lock,LOCK_EX|LOCK_NB),'Another publication is running');
 $p=new PDO($c['dsn'],$c['user'],$c['password'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
 $rows=$p->query('SELECT body FROM entries ORDER BY edition_date DESC,score DESC')->fetchAll(PDO::FETCH_COLUMN);
 $items=array_map(function($s){return json_decode($s,true);},$rows);
 $editions=$p->query('SELECT edition_date,collected_at FROM editions ORDER BY edition_date DESC')->fetchAll(PDO::FETCH_ASSOC);
 need(count($editions)>0,'No published edition to rebuild');
 $release=$root.'/releases/'.gmdate('YmdHis').'-'.bin2hex(random_bytes(3));need(mkdir($release,0755,true),'Cannot create release');
 renderSite($items,$editions,$release);
 $tmp=$root.'/current-next-'.bin2hex(random_bytes(3));need(symlink('releases/'.basename($release),$tmp)&&rename($tmp,$root.'/current'),'Cannot switch release');
 echo json_encode(array('rebuilt'=>true,'release'=>basename($release),'entries'=>count($items)),JSON_UNESCAPED_UNICODE)."\n";
}catch(Throwable $e){fwrite(STDERR,'Rebuild failed: '.$e->getMessage()."\n");exit(1);}
