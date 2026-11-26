<?php
require_once __DIR__.'/brief.php';
function webResponse($body,$status=200,$contentType='text/html; charset=utf-8'){return array('body'=>$body,'status'=>$status,'contentType'=>$contentType);}
function latestEdition($db){return $db->query('SELECT edition_date,collected_at FROM editions ORDER BY edition_date DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);}
function editionItems($db,$date,$kind=null){
 $sql='SELECT body FROM entries WHERE edition_date=?'.($kind===null?'':' AND kind=?').' ORDER BY score DESC,id';
 $q=$db->prepare($sql);$q->execute($kind===null?array($date):array($date,$kind));
 return array_map(function($body){return json_decode($body,true);},$q->fetchAll(PDO::FETCH_COLUMN));
}
function webHome($db, $edition, $path='/') {
    $date = $edition['edition_date'];
    $start = (new DateTimeImmutable($date))->modify('-6 days')->format('Y-m-d');
    $q = $db->prepare("SELECT body FROM entries WHERE kind='news' AND edition_date BETWEEN ? AND ? ORDER BY edition_date DESC,score DESC,id");
    $q->execute(array($start,$date));
    $recent = array_map(function ($body) { return json_decode($body,true); }, $q->fetchAll(PDO::FETCH_COLUMN));
    $neighbors = $db->prepare('SELECT (SELECT MAX(edition_date) FROM editions WHERE edition_date < ?) AS prev, (SELECT MIN(edition_date) FROM editions WHERE edition_date > ?) AS next_date');
    $neighbors->execute(array($date,$date));
    $days = $neighbors->fetch(PDO::FETCH_ASSOC);
    return briefFront($edition,$recent,$days['prev'],$days['next_date'],$path);
}
function webListing($db,$edition,$kind){
 $list=editionItems($db,$edition['edition_date'],$kind);$repo=$kind==='repo';
 if($repo)usort($list,function($a,$b){return $b['weeklyStars']<=>$a['weeklyStars'];});
 $title=$repo?'GitHub 开源周榜':'每日 AI 热点';$path=$repo?'/repos/':'/news/';
 $body='<section class="page-heading"><span class="eyebrow">'.($repo?'OPEN SOURCE RADAR':'THE AI BRIEF').'</span><h1>'.$title.'</h1><p>'.($repo?'近 7 天 Star 增长，寻找值得深入了解的开源项目。':'国内外 AI 新动态，附详细介绍与原始报道。').'</p><p class="method">本期 '.h($edition['edition_date']).' · '.($repo?'按已采集候选项目的周增量排序。':'只收录 AI 相关、日期与来源可核验的报道。').'</p></section>'.filters($list,!$repo).'<div class="cards filter-items">';
 foreach($list as $index=>$item)$body.=card($item,$repo?$index+1:null);
 $body.='</div><p class="empty no-results" hidden>没有匹配内容，试试其他关键词或分类。</p>';
 return shellPage($title,$body,$repo?'repos':'news','',$path);
}
function webArticle($item, $from=null){
 if($item['kind']==='news')return briefArticle($item,$from);
 $repo=$item['kind']==='repo';$body='<div class="article-layout"><article class="article"><a class="back" href="/'.($repo?'repos':'news').'/">← 返回'.($repo?'开源周榜':'AI 热点').'</a><div class="eyebrow">'.h($item['category']).' / '.h($item['region']).'</div><h1>'.h($item['title']).'</h1><div class="article-meta">'.h($item['source']).' · '.h($item['editionDate']).' · AI 辅助整理</div><p class="lead">'.h($item['intro']).'</p>';
 if($repo)$body.='<div class="repo-metrics"><div><b>+'.number_format($item['weeklyStars']).'</b>近 7 天 Star</div><div><b>'.number_format($item['totalStars']).'</b>总 Star</div></div>';
 foreach($item['sections'] as $section)$body.='<section><h2>'.h($section['heading']).'</h2><p>'.nl2br(h($section['text'])).'</p></section>';
 $body.='<a class="button" href="'.h($item['url']).'" target="_blank" rel="noopener noreferrer">'.($repo?'前往 GitHub 查看项目':'阅读完整原文').' ↗</a></article><aside class="article-aside"><span class="eyebrow">SOURCE NOTES</span><h3>从原始信息出发</h3><p>本文为中文整理与解读，功能、数据和使用条件以原文为准。</p><ul>';
 foreach($item['evidence'] as $e)$body.='<li><a href="'.h($e['url']).'" target="_blank" rel="noopener noreferrer">'.h($e['name']).' ↗</a></li>';
 if($repo)$body.='<li><a href="'.h($item['rankingSource']).'" target="_blank" rel="noopener noreferrer">GitHub 周趋势数据 ↗</a></li>';
 $body.='</ul></aside></div>';
 return shellPage($item['title'],$body,$repo?'repos':'news',$item['intro'],'/read/'.$item['id'].'/');
}
function webArchive($db){
 $rows=$db->query('SELECT edition_date,COUNT(*) AS item_count FROM entries GROUP BY edition_date ORDER BY edition_date DESC')->fetchAll(PDO::FETCH_ASSOC);
 $body='<section class="page-heading"><span class="eyebrow">THE ARCHIVE</span><h1>每一步，都有迹可循。</h1><p>按日期回看 AI 热点与开源项目。</p></section><div class="archive-list">';
 foreach($rows as $row)$body.='<a href="/archive/'.h($row['edition_date']).'/"><b>'.h($row['edition_date']).'</b><span>'.(int)$row['item_count'].' 条内容 ↗</span></a>';
 return shellPage('往期归档',$body.'</div>','archive','','/archive/');
}
function webAbout(){
 $body='<article class="article about"><span class="eyebrow">ABOUT QIBU AI</span><h1>让技术信息，更有价值。</h1><p class="lead">启步 AI 面向 AI 爱好者和开发者，每日整理国内外 AI 进展及快速成长的开源项目。</p><h2>内容与更新</h2><p>每天北京时间 12:00 开始采集，完成核验后更新。新闻只收录以 AI 为核心、原始发布时间属于该北京自然日的内容。已发布事件及同一事件的转载不重复收录。</p><h2>开源榜单范围</h2><p>采集 GitHub 全站及 Python、TypeScript、Rust、Go 周趋势候选，去重后按 GitHub 展示的 stars this week 排序。这是已采集候选范围的增长榜，并非 GitHub 全站严格排名。已经推荐过的项目不再跨天重复推荐。</p><h2>阅读与来源</h2><p>介绍由 AI 辅助整理，保留来源链接。请以原文、官方文档及项目许可为准。本站不转载原文全文。</p></article>';
 return shellPage('关于本站与榜单规则',$body,'','','/about/');
}
function renderRequest($db,$path,$from=null){
 if(!is_string($from)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$from))$from=null;
 $path=rtrim($path,'/');if($path==='')$path='/';
 if($path==='/robots.txt')return webResponse("User-agent: *\nAllow: /\nSitemap: https://xmqibu.com/sitemap.xml\n",200,'text/plain; charset=utf-8');
 if($path==='/sitemap.xml'){
  $xml='<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  foreach(array('/','/news/','/repos/','/archive/','/about/') as $url)$xml.='<url><loc>https://xmqibu.com'.$url.'</loc></url>';
  foreach($db->query('SELECT id FROM entries') as $row)$xml.='<url><loc>https://xmqibu.com/read/'.h($row['id']).'/</loc></url>';
  return webResponse($xml.'</urlset>',200,'application/xml; charset=utf-8');
 }
 $edition=latestEdition($db);if(!$edition)return webResponse('No published edition',503,'text/plain; charset=utf-8');
 if($path==='/health.json'){
  $q=$db->prepare('SELECT kind,COUNT(*) AS count FROM entries WHERE edition_date=? GROUP BY kind');$q->execute(array($edition['edition_date']));$counts=array('news'=>0,'repo'=>0);foreach($q as $row)$counts[$row['kind']]=(int)$row['count'];
  return webResponse(json_encode(array('edition'=>$edition['edition_date'],'collectedAt'=>$edition['collected_at'],'news'=>$counts['news'],'repos'=>$counts['repo']),JSON_UNESCAPED_UNICODE),200,'application/json; charset=utf-8');
 }
 if($path==='/')return webResponse(webHome($db,$edition));
 if(preg_match('#^/brief/(\d{4}-\d{2}-\d{2})$#',$path,$match)){
    $q=$db->prepare('SELECT edition_date,collected_at FROM editions WHERE edition_date=?');
    $q->execute(array($match[1]));
    $issue=$q->fetch(PDO::FETCH_ASSOC);
    if($issue)return webResponse(webHome($db,$issue,briefEditionUrl($match[1])));
 }
 if($path==='/news')return webResponse(webListing($db,$edition,'news'));
 if($path==='/repos')return webResponse(webListing($db,$edition,'repo'));
 if($path==='/archive')return webResponse(webArchive($db));
 if($path==='/about')return webResponse(webAbout());
 if(preg_match('#^/archive/(\d{4}-\d{2}-\d{2})$#',$path,$match)){
  $items=editionItems($db,$match[1]);if(!$items)return webResponse(shellPage('页面未找到','<section class="page-heading"><h1>页面未找到</h1></section>'),404);
  $body='<section class="page-heading"><span class="eyebrow">PAST EDITION</span><h1>'.h($match[1]).'</h1><p>本期资讯与项目快照</p><a href="'.h(briefEditionUrl($match[1])).'">阅读本期午报 →</a></section><div class="cards">';foreach($items as $item)$body.=card($item);
  return webResponse(shellPage($match[1].' 归档',$body.'</div>','archive','当日资讯与项目快照','/archive/'.$match[1].'/'));
 }
 if(preg_match('#^/read/([a-zA-Z0-9_-]{1,24})$#',$path,$match)){
  $q=$db->prepare('SELECT body FROM entries WHERE id=?');$q->execute(array($match[1]));$body=$q->fetchColumn();
  if($body)return webResponse(webArticle(json_decode($body,true),$from));
 }
 return webResponse(shellPage('页面未找到','<section class="page-heading"><span class="eyebrow">404</span><h1>这一页，暂时走丢了。</h1><a class="button" href="/">返回首页 ↗</a></section>'),404);
}
