<?php
// Historical entries have only a source name and URL. Use the neutral label
// until an editor has identified a specific source's relationship to the story.
function briefSource($item) {
    foreach ($item['evidence'] as $source) {
        if (in_array($source['role'] ?? '', array('官方公告','官方说明','原始研究','监管原文','直接采访','现场报道'), true)) return $source;
    }
    return array('name' => $item['source'], 'url' => $item['url'], 'role' => '报道来源');
}

function briefExternal($source, $class, $label) {
    return '<a target="_blank" rel="noopener noreferrer" class="'.h($class).'" href="'.h($source['url']).'">'.h($label).' ↗</a>';
}

function briefEditionUrl($date) { return '/brief/'.rawurlencode($date).'/'; }
function briefReadUrl($item, $date) { return '/read/'.rawurlencode($item['id']).'/?from='.rawurlencode($date); }

function briefShell($title, $description, $path, $body) {
    ob_start(); ?>
<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><link rel="icon" href="data:,"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> · 启步 AI</title><meta name="description" content="<?=h($description)?>"><meta property="og:title" content="<?=h($title)?>"><meta property="og:description" content="<?=h($description)?>"><link rel="canonical" href="https://xmqibu.com<?=h($path)?>"><link rel="stylesheet" href="/brief.css"><script defer src="/brief.js"></script></head>
<body><a class="skip" href="#main">跳到正文</a><div class="wrap"><header class="masthead"><a class="brand" href="/" aria-label="启步 AI 首页"><span class="identity"><span class="brand-mark" aria-hidden="true">↗</span><strong>启步 AI</strong></span></a><nav aria-label="主导航"><a href="/">午间简报</a><a href="/archive/">往期归档</a><a href="/repos/">开源周榜</a></nav></header>
<?=$body?>
<footer><strong>启步 AI</strong><a href="/news/">本期资讯</a><a href="/about/">关于本站</a><a href="https://beian.miit.gov.cn/" rel="noopener noreferrer" target="_blank">闽ICP备2022008558号-1</a></footer></div></body></html>
<?php return ob_get_clean();
}

function briefArticle($item, $from = null) {
    $source = briefSource($item);
    $return = briefEditionUrl($from ?: $item['editionDate']);
    $reported = (new DateTimeImmutable($item['publishedAt']))->setTimezone(new DateTimeZone('Asia/Shanghai'))->format('Y-m-d H:i');
    ob_start(); ?>
<main class="reader" id="main"><a class="back" href="<?=h($return)?>">← 返回午间简报</a>
<div class="detail-grid"><article class="story"><div class="kicker"><?=h($item['category'])?> ／ <?=h($item['region'])?></div>
<h1><?=h($item['title'])?></h1><p class="dek"><?=h($item['intro'])?></p>
<div class="meta"><?=h($item['source'])?> · 报道时间 <?=h($reported)?>（北京） · 本站 <?=h($item['editionDate'])?> 收录</div>
<div class="actions"><?=briefExternal($source,'primary','读'.$source['role'])?></div>
<div class="body-copy"><?php foreach ($item['sections'] as $section): ?><section><h2><?=h($section['heading'])?></h2><p><?=nl2br(h($section['text']))?></p></section><?php endforeach; ?></div>
<a class="back" href="<?=h($return)?>#timeline">继续读近期要闻 →</a></article>
<aside class="sources"><h2>来源手记</h2><?php foreach ($item['evidence'] as $e): ?><p><?=briefExternal($e,'',$e['name'])?></p><?php endforeach; ?><p class="note">本站中文整理 · AI 辅助编辑</p></aside></div></main>
<?php $body = ob_get_clean();
    return briefShell($item['title'],$item['intro'],'/read/'.$item['id'].'/',$body);
}

// $recent contains news in descending edition and editorial score order.
function briefFront($edition, $recent, $previous, $next, $path) {
    $date = $edition['edition_date'];
    $lead = null;
    foreach ($recent as $item) {
        if ($item['editionDate'] !== $date) break;
        if ($lead === null) $lead = $item;
        if (briefSource($item)['role'] !== '报道来源') { $lead = $item; break; }
    }
    $others = array_filter($recent, function ($item) use ($lead) { return !$lead || $item['id'] !== $lead['id']; });
    $categories = array_unique(array_column($recent, 'category'));
    $collected = (new DateTimeImmutable($edition['collected_at']))->setTimezone(new DateTimeZone('Asia/Shanghai'))->format('m-d H:i');
    ob_start(); ?>
<main id="main" data-edition="<?=h($date)?>" data-start="<?=h((new DateTimeImmutable($date))->modify('-6 days')->format('Y-m-d'))?>"><div class="issuebar"><h1>午间简报</h1><div class="dateline"><time datetime="<?=h($date)?>"><?=h($date)?></time><br>采集于 <?=h($collected)?>（北京）<a href="#timeline" data-catchup hidden></a></div></div>
<section class="front" aria-label="本期新闻"><?php if ($lead): $source = briefSource($lead); ?>
<article class="lead"><div class="kicker"><?=$source['role'] === '报道来源' ? '本期报道' : '本期头条'?> <span>／ <?=h($lead['category'])?></span></div>
<h2><a href="<?=h(briefReadUrl($lead,$date))?>"><?=h($lead['title'])?></a></h2><p class="dek"><?=h($lead['intro'])?></p><div class="meta"><?=h($source['role'])?> · <?=h($source['name'])?></div>
<div class="actions"><?=briefExternal($source,'primary','读'.$source['role'])?><a class="secondary" href="<?=h(briefReadUrl($lead,$date))?>">看中文解读 →</a></div></article>
<figure class="art"><div class="plate product-plate" aria-hidden="true"><i class="axis x"></i><i class="axis y"></i><i class="orbit o1"></i><i class="orbit o2"></i><i class="orbit o3"></i><i class="signal"></i><i class="pulse"></i></div><figcaption><?=h($lead['category'])?><span>编辑插图</span></figcaption></figure>
<?php else: ?><article class="lead"><h2>本期暂无已发布新闻</h2><a class="secondary" href="/archive/">查看往期 →</a></article><?php endif; ?>
<aside class="digest"><?php if ($others): ?><h2>近日选读</h2><div class="digest-list"><?php foreach (array_slice($others,0,2) as $item): ?><article><div class="meta"><?=h($item['category'])?> · <?=h($item['editionDate'])?></div><h3><a href="<?=h(briefReadUrl($item,$date))?>"><?=h($item['title'])?></a></h3></article><?php endforeach; ?></div><?php endif; ?>
<div class="progress" hidden><p data-progress role="status"></p><button class="mark" type="button" data-mark>标记本次已读</button></div></aside></section>
<section class="timeline" id="timeline"><div class="section-head"><h2>近七天要闻</h2><?php if ($previous): ?><a href="<?=h(briefEditionUrl($previous))?>">← 上期午报</a><?php endif; if ($next): ?><a href="<?=h(briefEditionUrl($next))?>">下期午报 →</a><?php endif; ?><a href="/archive/">全部归档 →</a></div>
<div class="filters" hidden role="group" aria-label="按主题筛选"><?php foreach (array_merge(array('全部'),$categories) as $category): ?><button type="button" data-filter="<?=h($category)?>" aria-pressed="<?=$category==='全部'?'true':'false'?>"><?=h($category)?></button><?php endforeach; ?></div>
<p class="sr-only" role="status" data-results></p><div class="rows"><?php foreach ($recent as $item): $s=briefSource($item);
    $revision=substr(hash('sha256',json_encode(array($item['title'],$item['intro'],$item['sections']))),0,16); ?>
<article class="row" data-token="<?=h($item['id'].'-'.$revision)?>" data-kind="<?=h($item['category'])?>"><time class="rowdate" datetime="<?=h($item['editionDate'])?>"><?=h(substr($item['editionDate'],5))?></time><div class="rowbody"><div class="meta"><?=h($item['category'])?> · <?=h($s['name'])?></div><h3><a href="<?=h(briefReadUrl($item,$date))?>"><?=h($item['title'])?></a></h3><p><?=h($item['intro'])?></p></div><?=briefExternal($s,'rowlink','读'.$s['role'])?></article><?php endforeach; ?></div>
<p class="note" data-empty hidden>没有匹配的新闻。<button type="button" data-reset>查看全部</button></p><?php if (!$recent): ?><p>近七天暂无已发布新闻。</p><?php endif; ?></section></main>
<?php $body = ob_get_clean();
    return briefShell($date.' 午间简报', $lead ? $lead['intro'] : '启步 AI 近期要闻', $path, $body);
}
