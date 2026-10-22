<?php
require __DIR__.'/../src/validate.php';require __DIR__.'/../src/dedupe.php';
$b=validateBundle(json_decode(file_get_contents(__DIR__.'/fixtures/edition.json'),true));$r=$b['items'][0];$n=$b['items'][6];
$next=$r;$next['editionDate']='2026-10-22';$next['url']=str_replace('morluto/rea','MORLUTO/REA/',$r['url']);$next['weeklyStars']++;
$m=mergeUnseenItems(array($next),array($r),'2026-10-22');need(count($m['items'])===0&&$m['skipped']===1,'Historical repo');echo "PASS historical repo despite new stars/case/trailing slash\n";
$old=$n;$old['url']='https://example.com/story?utm_source=one';$old['evidence']=array(array('name'=>'source','url'=>$old['url']));$next=$old;$next['url']='https://example.com/story?utm_source=two';$next['title']='Changed title';$next['evidence']=array(array('name'=>'source','url'=>$next['url']));
need(mergeUnseenItems(array($next),array($old),'2026-10-22')['skipped']===1,'Tracking');echo "PASS tracking URLs\n";
$next['url']='https://other.com/report';$next['evidence']=array(array('name'=>'other','url'=>$next['url']));$old['eventKey']=$next['eventKey']='vendor-model-v1-release';need(mergeUnseenItems(array($next),array($old),'2026-10-22')['skipped']===1,'Syndicated event');echo "PASS same event across different publishers\n";
unset($next['eventKey']);$next['evidence'][]=array('name'=>'original','url'=>$old['url']);need(mergeUnseenItems(array($next),array($old),'2026-10-22')['skipped']===1,'Evidence overlap');echo "PASS shared source evidence\n";
$next=$r;$next['weeklyStars']++;$m=mergeUnseenItems(array($next),array($r,$n),$b['date']);need(count($m['items'])===2&&$m['items'][0]['weeklyStars']===$next['weeklyStars'],'Partial replay');echo "PASS same-day update preserves other entries\n";
$next=$n;$next['eventKey']='new-model-v2-release';$next['title']='全新版本模型发布';$next['url']='https://example.com/new-release';$next['evidence']=array(array('name'=>'source','url'=>$next['url']));need(count(mergeUnseenItems(array($next),array($n),'2026-10-22')['items'])===1,'New development');echo "PASS genuinely new event\n";
$x=$b;$x['items'][6]['title']='苹果电脑发布新配色';$x['items'][6]['intro']='这是普通硬件产品更新，与模型和智能应用无关。';try{validateBundle($x);throw new Exception('Non-AI accepted');}catch(RuntimeException $e){need($e->getMessage()==='News must have an explicit AI focus','Unexpected failure');echo "PASS non-AI news rejected\n";}
