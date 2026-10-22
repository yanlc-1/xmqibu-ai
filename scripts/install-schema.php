<?php
require __DIR__.'/../src/database.php';
try{
 $config=require (isset($argv[1])?$argv[1]:__DIR__.'/../data/database.local.php');
 $db=openDatabase($config);
 if($db->query('SELECT DATABASE()')->fetchColumn()!=='xmqibu-ai')throw new RuntimeException('Refusing to install schema outside xmqibu-ai');
 foreach(explode(';',file_get_contents(__DIR__.'/schema.sql')) as $sql){
  $sql=trim($sql);
  if($sql!=='')$db->exec($sql);
 }
 echo "Schema installed in xmqibu-ai\n";
}catch(Throwable $error){fwrite(STDERR,'Schema installation failed: '.get_class($error).': '.$error->getMessage()."\n");exit(1);}
