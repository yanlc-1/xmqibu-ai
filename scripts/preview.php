<?php
require __DIR__.'/../src/validate.php';require __DIR__.'/../src/render.php';
$b=validateBundle(json_decode(file_get_contents($argv[1]),true));
renderSite($b['items'],array(array('edition_date'=>$b['date'],'collected_at'=>$b['collectedAt'])),$argv[2]);echo "Preview generated\n";
