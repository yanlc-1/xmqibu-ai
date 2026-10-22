<?php
// Run on the old server. Only published content is exported; credentials stay in config.php.
require __DIR__.'/../src/database.php';
try{
 if(!isset($argv[1]))throw new RuntimeException('Usage: export-legacy.php old-config.php');
 $config=require $argv[1];
 $host=getenv('XMQIBU_AI_LEGACY_HOST');
 if($host!==false&&$host!==''){
  if(!filter_var($host,FILTER_VALIDATE_IP))throw new RuntimeException('Invalid legacy database host');
  $config['dsn']=preg_replace('/host=[^;]+/','host='.$host,$config['dsn']);
 }
 $db=openDatabase($config);
 if($db->query('SELECT DATABASE()')->fetchColumn()!=='xmqibu_ai')throw new RuntimeException('Unexpected legacy database');
 $tables=array('editions','entries','collection_runs');$rows=array();
 foreach($tables as $table)$rows[$table]=$db->query('SELECT * FROM '.$table.' ORDER BY '.($table==='collection_runs'?'id':($table==='editions'?'edition_date':'edition_date,id')))->fetchAll(PDO::FETCH_ASSOC);
 $json=json_encode(array('sourceDatabase'=>'xmqibu_ai','exportedAt'=>gmdate('c'),'tables'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 if($json===false)throw new RuntimeException('Legacy export encoding failed');
 echo $json."\n";
}catch(Throwable $error){fwrite(STDERR,'Legacy export failed: '.get_class($error).': '.$error->getMessage()."\n");exit(1);}
