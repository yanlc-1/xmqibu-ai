<?php
// Import a one-time export into the new, empty xmqibu-ai schema.
require __DIR__.'/../src/database.php';
try{
 if(!isset($argv[1]))throw new RuntimeException('Usage: import-legacy.php export.json [target-config.php]');
 $payload=json_decode(file_get_contents($argv[1]),true);
 if(!is_array($payload)||!isset($payload['sourceDatabase'],$payload['tables'])||$payload['sourceDatabase']!=='xmqibu_ai')throw new RuntimeException('Invalid legacy export');
 $config=require (isset($argv[2])?$argv[2]:__DIR__.'/../data/database.local.php');
 $db=openDatabase($config);
 if($db->query('SELECT DATABASE()')->fetchColumn()!=='xmqibu-ai')throw new RuntimeException('Refusing to import outside xmqibu-ai');
 $columns=array(
  'editions'=>array('edition_date','collected_at','bundle','bundle_hash','updated_at'),
  'entries'=>array('id','kind','edition_date','url_hash','category','region','title','score','body'),
  'collection_runs'=>array('id','edition_date','status','detail','created_at'),
 );
 $db->beginTransaction();
 foreach($columns as $table=>$fields){
  if(!isset($payload['tables'][$table])||!is_array($payload['tables'][$table]))throw new RuntimeException('Missing export table '.$table);
  if((int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==0)throw new RuntimeException('Target table is not empty: '.$table);
  $sql='INSERT INTO '.$table.' ('.implode(',',$fields).') VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';
  $insert=$db->prepare($sql);
  foreach($payload['tables'][$table] as $row){
   $values=array();foreach($fields as $field){if(!array_key_exists($field,$row))throw new RuntimeException('Missing field '.$field);$values[]=$row[$field];}
   $insert->execute($values);
  }
  if((int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==count($payload['tables'][$table]))throw new RuntimeException('Imported count mismatch: '.$table);
 }
 $db->commit();
 echo json_encode(array('imported'=>true,'editions'=>count($payload['tables']['editions']),'entries'=>count($payload['tables']['entries']),'collectionRuns'=>count($payload['tables']['collection_runs'])),JSON_UNESCAPED_UNICODE)."\n";
}catch(Throwable $error){if(isset($db)&&$db->inTransaction())$db->rollBack();fwrite(STDERR,'Legacy import failed: '.get_class($error).': '.$error->getMessage()."\n");exit(1);}
