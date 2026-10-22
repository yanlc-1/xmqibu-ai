<?php
function canonicalSource($url){
 $p=parse_url($url);$host=strtolower(isset($p['host'])?$p['host']:'');$host=preg_replace('/^www\./','',$host);
 $path=rtrim(isset($p['path'])?$p['path']:'','/');
 if($host==='github.com')return 'github.com'.strtolower(preg_replace('/\.git$/i','',$path));
 $q=array();if(isset($p['query']))parse_str($p['query'],$q);
 foreach(array_keys($q) as $k)if(preg_match('/^(utm_|fbclid$|gclid$|spm$|from$|ref$)/i',$k))unset($q[$k]);ksort($q);
 return $host.$path.($q?'?'.http_build_query($q):'');
}
function normalizedTopic($text){return preg_replace('/[\p{P}\p{Z}\s]+/u','',strtolower($text));}
function entryAliases($i){
 $prefix=$i['kind'].':';$keys=array($prefix.'url:'.canonicalSource($i['url']));
 if($i['kind']==='news'){
  $keys[]=$prefix.'title:'.normalizedTopic($i['title']);
  if(!empty($i['eventKey']))$keys[]=$prefix.'event:'.normalizedTopic($i['eventKey']);
  foreach($i['evidence'] as $e)if(trim((string)parse_url($e['url'],PHP_URL_PATH),'/')!=='')$keys[]=$prefix.'url:'.canonicalSource($e['url']);
 }return array_unique($keys);
}
function mergeUnseenItems($incoming,$history,$date){
 $past=array();$today=array();$currentAliases=array();$skipped=0;
 foreach($history as $old){if($old['editionDate']!==$date){foreach(entryAliases($old) as $k)$past[$k]=true;}else{$index=count($today);$today[]=$old;foreach(entryAliases($old) as $k)$currentAliases[$k]=$index;}}
 foreach($incoming as $i){$keys=entryAliases($i);$duplicate=false;foreach($keys as $k)if(isset($past[$k])){$duplicate=true;break;}if($duplicate){$skipped++;continue;}
  $index=null;foreach($keys as $k)if(isset($currentAliases[$k])){$index=$currentAliases[$k];break;}
  if($index!==null){$i['id']=$today[$index]['id'];$today[$index]=$i;}else{$index=count($today);$today[]=$i;}
  foreach($keys as $k)$currentAliases[$k]=$index;
 }
 return array('items'=>$today,'skipped'=>$skipped);
}
