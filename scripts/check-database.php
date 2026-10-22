<?php
require __DIR__.'/../src/database.php';
try{
 $config=require (isset($argv[1])?$argv[1]:__DIR__.'/../data/database.local.php');
 $db=openDatabase($config);
 $name=$db->query('SELECT DATABASE()')->fetchColumn();
 if($name!=='xmqibu-ai')throw new RuntimeException('Connected to unexpected database');
 $names=$db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('editions','entries','collection_runs')")->fetchAll(PDO::FETCH_COLUMN);
 sort($names);
 echo json_encode(array('connected'=>true,'database'=>$name,'tables'=>$names),JSON_UNESCAPED_UNICODE)."\n";
}catch(Throwable $error){fwrite(STDERR,'Database check failed: '.get_class($error)."\n");exit(1);}
