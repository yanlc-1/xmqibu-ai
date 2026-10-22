<?php
function need($ok,$message){if(!$ok)throw new RuntimeException($message);}
function validUrl($u){return is_string($u)&&filter_var($u,FILTER_VALIDATE_URL)&&parse_url($u,PHP_URL_SCHEME)==='https'&&!parse_url($u,PHP_URL_USER)&&!parse_url($u,PHP_URL_PASS);}
function textLength($s){return preg_match_all('/./us',$s);}
function validateBundle($b){
 need(is_array($b)&&isset($b['date'],$b['collectedAt'],$b['items']), 'Missing bundle fields');
 need(preg_match('/^\d{4}-\d{2}-\d{2}$/',$b['date'])&&date('Y-m-d',strtotime($b['date']))===$b['date'],'Invalid date');
 $tz=new DateTimeZone('Asia/Shanghai');$now=new DateTimeImmutable('now',$tz);
 need($b['date']<=$now->format('Y-m-d'),'Future edition');
 need(strtotime($b['collectedAt'])!==false&&strtotime($b['collectedAt'])<=time()+300,'Invalid collection time');
 need(is_array($b['items'])&&count($b['items'])>0&&count($b['items'])<=80,'Invalid item count');
 $cats=array('模型与研究','AI 编程','Agent 与自动化','图像与视频','语音与音频','产品与产业','开发工具','其他开源');$seen=array();
 foreach($b['items'] as &$i){
 foreach(array('kind','title','category','region','url','source','publishedAt','intro','sections','evidence') as $k)need(isset($i[$k]),'Missing '.$k);
 need(in_array($i['kind'],array('news','repo'),true),'Invalid kind');need(in_array($i['category'],$cats,true),'Invalid category');
 need(in_array($i['region'],array('国内','海外','全球'),true),'Invalid region');
 foreach(array('title','source','intro') as $k)need(is_string($i[$k])&&textLength($i[$k])>=2&&textLength($i[$k])<=1000,'Invalid '.$k);
 need(textLength($i['title'])<=160,'Title too long');need(validUrl($i['url']),'Invalid URL');
 need(preg_match('/T.*(?:Z|[+-]\d{2}:\d{2})$/',$i['publishedAt'])&&strtotime($i['publishedAt'])!==false,'Timestamp needs timezone');
 need(is_array($i['sections'])&&count($i['sections'])>=2&&count($i['sections'])<=8,'Invalid sections');$len=0;
 foreach($i['sections'] as $s){need(isset($s['heading'],$s['text'])&&is_string($s['heading'])&&is_string($s['text']),'Invalid section');$len+=textLength($s['text']);need(textLength($s['text'])<2200,'Section too long');}need($len>=160&&$len<=6000,'Detailed introduction needs 160–6000 characters');
 need(is_array($i['evidence'])&&count($i['evidence'])>=1&&count($i['evidence'])<=12,'Missing evidence');$domains=array();$primary=false;
 foreach($i['evidence'] as $e){need(isset($e['url'],$e['name'])&&validUrl($e['url'])&&is_string($e['name']),'Invalid evidence');$domains[parse_url($e['url'],PHP_URL_HOST)]=true;if($e['url']===$i['url'])$primary=true;}need($primary,'Primary source missing from evidence');
 if($i['kind']==='repo'){
 need(parse_url($i['url'],PHP_URL_HOST)==='github.com'&&preg_match('~^https://github.com/[\w.-]+/[\w.-]+/?$~',$i['url']),'Invalid repo URL');
 need(isset($i['weeklyStars'],$i['totalStars'],$i['rankingSource'])&&is_int($i['weeklyStars'])&&$i['weeklyStars']>=0&&is_int($i['totalStars'])&&$i['totalStars']>=$i['weeklyStars'],'Invalid Star counts');
 need(preg_match('~^https://github.com/trending(?:/[a-z-]*)?\?since=weekly$~',$i['rankingSource']),'Missing weekly source');$i['score']=$i['weeklyStars'];
 }else{
 need(!in_array($i['category'],array('开发工具','其他开源'),true),'News category must be AI related');
 need(preg_match('/\bAI\b|人工智能|大模型|智能体|GPT|Claude|Gemini|DeepSeek|Qwen|OpenAI|Anthropic|MiniMax|机器学习|生成式|LLM|\bAgent\b/iu',$i['title'].' '.$i['intro']),'News must have an explicit AI focus');
 if(isset($i['eventKey']))need(is_string($i['eventKey'])&&textLength($i['eventKey'])>=4&&textLength($i['eventKey'])<=200,'Invalid event key');
 $dt=new DateTimeImmutable($i['publishedAt']);need($dt->setTimezone($tz)->format('Y-m-d')===$b['date'],'News is not from edition day');
 need(strtotime($i['publishedAt'])<=strtotime($b['collectedAt']),'News newer than collection');
 $hours=max(0,(strtotime($b['collectedAt'])-strtotime($i['publishedAt']))/3600);$i['score']=min(5,count($domains))*20+(int)max(0,10-floor($hours/2.4));
 }
 $key=$i['kind'].'|'.$i['url'];need(!isset($seen[$key]),'Duplicate URL');$seen[$key]=true;
 $i['id']=substr(hash('sha256',$b['date'].'|'.$key),0,24);$i['editionDate']=$b['date'];
 }unset($i);return $b;
}
