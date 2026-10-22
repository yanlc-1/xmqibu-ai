<?php
require __DIR__.'/../src/dedupe.php';
require __DIR__.'/../src/database.php';
$c=require (isset($argv[2])?$argv[2]:'/secrets/config.php');$p=openDatabase($c);
if(isset($argv[1])&&$argv[1]==='--history'){
 $items=array();foreach($p->query('SELECT body FROM entries ORDER BY edition_date DESC') as $r){$i=json_decode($r['body'],true);$items[]=array('kind'=>$i['kind'],'url'=>$i['url'],'title'=>$i['title'],'editionDate'=>$i['editionDate'],'eventKey'=>isset($i['eventKey'])?$i['eventKey']:null,'aliases'=>entryAliases($i));}
 echo json_encode(array('exportedAt'=>gmdate('c'),'items'=>$items),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}
$q=$p->prepare('SELECT bundle FROM editions WHERE edition_date=?');$q->execute(array($argv[1]));$v=$q->fetchColumn();echo $v?$v:'null';
