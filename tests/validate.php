<?php
require __DIR__.'/../src/validate.php'; require __DIR__.'/../src/render.php';
$b=json_decode(file_get_contents(__DIR__.'/fixtures/edition.json'),true);$v=validateBundle($b);need(count($v['items'])===12,'fixture');
function rejects($b,$label){try{validateBundle($b);}catch(Throwable $e){echo "PASS $label\n";return;}throw new Exception('Not rejected: '.$label);}
$x=$b;$x['items'][6]['publishedAt']='2026-10-20T15:59:59Z';rejects($x,'previous Beijing day');
$x=$b;$x['items'][6]['publishedAt']='2026-10-22T00:00:00Z';rejects($x,'future news');
$x=$b;$x['items'][0]['url']='javascript:alert(1)';rejects($x,'unsafe URL');
$x=$b;$x['items'][]=$x['items'][0];rejects($x,'duplicate URL');
$x=$b;$x['items'][0]['weeklyStars']=$x['items'][0]['totalStars']+1;rejects($x,'invalid growth');
$x=$b;$x['items'][0]['sections']=array(array('heading'=>'x','text'=>'too short'));rejects($x,'insufficient detail');
$x=$b;$x['items'][6]['evidence']=array(array('name'=>'x','url'=>'https://example.com'));rejects($x,'missing primary evidence');
need(h('<script>alert(1)</script>')==='&lt;script&gt;alert(1)&lt;/script&gt;','HTML escape');echo "PASS HTML escaping\n";
$first=$v['items'][0]['id'];need(validateBundle($b)['items'][0]['id']===$first,'stable id');echo "PASS stable identifiers\n";
