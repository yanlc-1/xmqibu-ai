<?php
require __DIR__.'/../src/validate.php';
require __DIR__.'/../src/render.php';
require __DIR__.'/../src/web.php';
$bundle=validateBundle(json_decode(file_get_contents(__DIR__.'/fixtures/edition.json'),true));
$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE editions (edition_date TEXT, collected_at TEXT)');
$db->exec('CREATE TABLE entries (id TEXT, kind TEXT, edition_date TEXT, score INTEGER, body TEXT)');
$db->prepare('INSERT INTO editions VALUES (?,?)')->execute(array($bundle['date'],$bundle['collectedAt']));
$insert=$db->prepare('INSERT INTO entries VALUES (?,?,?,?,?)');
foreach($bundle['items'] as $item)$insert->execute(array($item['id'],$item['kind'],$bundle['date'],isset($item['score'])?$item['score']:0,json_encode($item,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
$db->exec('PRAGMA query_only=ON');
$health=renderRequest($db,'/health.json');
if($health['status']!==200 || json_decode($health['body'],true)['edition']!==$bundle['date'])throw new Exception('health failed');
foreach(array('/','/news/','/repos/','/archive/','/archive/'.$bundle['date'].'/','/about/','/sitemap.xml','/read/'.$bundle['items'][0]['id'].'/') as $path){
 $page=renderRequest($db,$path);if($page['status']!==200 || $page['body']==='')throw new Exception('failed route '.$path);
}
if(renderRequest($db,'/read/not-found/')['status']!==404)throw new Exception('missing article not 404');
echo "PASS database-backed routes\n";
$news = array_values(array_filter($bundle['items'], function ($i) { return $i['kind']==='news'; }))[0];
$issuePath = briefEditionUrl($bundle['date']);
$front = renderRequest($db, $issuePath);
need($front['status'] === 200 && strpos($front['body'],'data-token="'.h($news['id']).'-') !== false, 'Missing real news on dated brief');
need(strpos($front['body'], 'data-token="'.h($bundle['items'][0]['id']).'-') === false, 'Repository entered reading progress');
need(renderRequest($db,'/brief/2026-01-01/')['status'] === 404, 'Unknown edition should return 404');
need(briefSource($news)['role'] === '报道来源', 'Legacy evidence labeled firsthand');
$news['evidence'][] = array('name'=>'官方说明','role'=>'官方说明','url'=>'https://example.org/original');
need(briefSource($news)['url'] === 'https://example.org/original', 'Direct evidence not selected');
need(strpos(briefArticle($news),'https://example.org/original') !== false, 'Missing original link');
$empty = briefFront(array('edition_date'=>$bundle['date'],'collected_at'=>$bundle['collectedAt']), array(), null, null, '/');
need(strpos($empty,'本期暂无已发布新闻') !== false, 'Missing empty state');
echo "PASS real editions, source attribution and reading tokens\n";
