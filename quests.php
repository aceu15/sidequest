<?php
require_once __DIR__ . '/includes/functions.php';
$page_title='Quest Board'; $pdo=db();
$category = trim($_GET['category']??''); $sort=$_GET['sort']??'newest'; $search=trim($_GET['q']??'');
$where=["q.status='open'"]; $params=[];
if($category){$where[]="c.slug=?";$params[]=$category;}
if($search){$where[]="(q.title LIKE ? OR q.description LIKE ? OR q.location LIKE ? OR u.username LIKE ?)";$s="%$search%";array_push($params,$s,$s,$s,$s);}
$order=match($sort){'reward'=>'q.reward_amount DESC','popular'=>'participant_count DESC','ending'=>'q.scheduled_at ASC','xp'=>'q.xp_reward DESC',default=>'q.created_at DESC'};
$sql="SELECT q.*,c.name category,c.slug category_slug,u.username,
(SELECT COUNT(*) FROM quest_participants qp WHERE qp.quest_id=q.id AND qp.status IN ('requested','accepted','in_progress','completed')) participant_count
FROM quests q JOIN categories c ON c.id=q.category_id JOIN users u ON u.id=q.creator_id WHERE ".implode(' AND ',$where)." ORDER BY $order";
$stmt=$pdo->prepare($sql);$stmt->execute($params);$quests=$stmt->fetchAll();
$cats=$pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="page-head"><div><span class="eyebrow">DISCOVERY / QUEST BOARD</span><h1>FIND YOUR NEXT<br><em>SIDEQUEST.</em></h1><p>Real missions. Real people. Pick something worth leaving the house for.</p></div><a class="btn gold" href="<?= base_url($me?'create-quest.php':'register.php') ?>">+ Post a Quest</a></section>
<section class="board-layout">
<aside class="filters"><form><input type="text" name="q" value="<?=e($search)?>" placeholder="Search quests..."><label>Sort by<select name="sort" onchange="this.form.submit()"><?php foreach(['newest'=>'Newest','reward'=>'Highest Reward','popular'=>'Most Popular','ending'=>'Ending Soon','xp'=>'Highest XP'] as $k=>$v): ?><option value="<?=$k?>" <?=$sort===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></label></form>
<div class="filter-title">CATEGORIES</div><a class="<?=!$category?'active':''?>" href="<?=base_url('quests.php')?>">All Quests</a><?php foreach($cats as $c): ?><a class="<?=$category===$c['slug']?'active':''?>" href="?category=<?=e($c['slug'])?>"><?=e($c['name'])?></a><?php endforeach;?></aside>
<div class="quest-feed">
<div class="feed-bar"><span><?=count($quests)?> AVAILABLE QUESTS</span><span>PHILIPPINES · QUEST BOARD</span></div>
<?php foreach($quests as $q): $dist=quest_distance($q); ?>
<article class="quest-card <?=($dist!==null && $dist<3)?'nearby':''?>">
<?php if($dist!==null && $dist<3): ?><span class="near-badge">NEAREST QUEST</span><?php endif;?>
<div class="quest-main"><div class="quest-kicker"><?=e(strtoupper($q['category']))?> · <?=e(strtoupper($q['difficulty']))?></div><h2><a href="<?=base_url('quest.php?id='.(int)$q['id'])?>"><?=e($q['title'])?></a></h2><p><?=e(mb_strimwidth($q['description'],0,170,'…'))?></p><div class="meta-row"><span>⌖ <?=e($q['location'])?></span><span>◷ <?=date('M j · g:i A',strtotime($q['scheduled_at']))?></span><span>♙ <?=count(explode(',',$q['required_people']))?> needed</span></div></div>
<div class="quest-side"><strong>+<?= (int)$q['xp_reward'] ?><small>XP</small></strong><?php if((float)$q['reward_amount']>0): ?><b class="cash">₱<?=number_format((float)$q['reward_amount'],0)?></b><?php else: ?><b class="muted">NO REWARD</b><?php endif;?><span><?= (int)$q['participant_count'] ?>/<?= (int)$q['max_participants'] ?> joined</span></div>
</article>
<?php endforeach; if(!$quests): ?><div class="empty"><h3>No quests found.</h3><p>Try another filter or be the one who posts the next mission.</p></div><?php endif;?>
</div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
