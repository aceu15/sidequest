<?php
require_once __DIR__.'/includes/functions.php';
require_login();
$pdo=db();$me=user();$page_title='My Quests';

$created=$pdo->prepare("SELECT q.*,c.name category,(SELECT COUNT(*) FROM quest_participants qp WHERE qp.quest_id=q.id AND qp.status IN ('requested','accepted','in_progress','completed')) participant_count FROM quests q JOIN categories c ON c.id=q.category_id WHERE q.creator_id=? ORDER BY q.created_at DESC");
$created->execute([$me['id']]);$created=$created->fetchAll();

$joined=$pdo->prepare("SELECT q.*,c.name category,qp.status participant_status,qp.started_at,qp.completed_at,u.username creator_username FROM quest_participants qp JOIN quests q ON q.id=qp.quest_id JOIN categories c ON c.id=q.category_id JOIN users u ON u.id=q.creator_id WHERE qp.user_id=? ORDER BY qp.requested_at DESC");
$joined->execute([$me['id']]);$joined=$joined->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="page-head compact"><div><span class="eyebrow">PLAYER + CREATOR HUB</span><h1>MY QUESTS.</h1><p>Track missions you created and missions you joined.</p></div><a class="btn gold" href="<?=base_url('create-quest.php')?>">+ Post a Quest</a></section>
<section class="my-quests-section"><div class="section-head"><div><span class="eyebrow">PLAYER ROSTER</span><h2>QUESTS I JOINED</h2></div></div>
<div class="my-quests-grid">
<?php foreach($joined as $q): ?>
<article class="my-quest-card"><div><span class="quest-kicker"><?=e(strtoupper($q['category']))?> · BY <?=e(strtoupper($q['creator_username']))?></span><h2><a href="<?=base_url('quest.php?id='.(int)$q['id'])?>"><?=e($q['title'])?></a></h2><p><?=e(mb_strimwidth($q['description'],0,180,'…'))?></p><div class="meta-row"><span><?=e($q['location'])?></span><span><?=date('M j · g:i A',strtotime($q['scheduled_at']))?></span></div></div><div class="my-quest-actions"><span class="status <?=e($q['participant_status'])?>"><?=e(status_label($q['participant_status']))?></span><a class="btn gold" href="<?=base_url('quest.php?id='.(int)$q['id'])?>"><?=in_array($q['participant_status'],['accepted','in_progress'],true)?'Open Quest':'View Quest'?></a></div></article>
<?php endforeach; if(!$joined): ?><div class="empty"><h3>You haven't joined a quest yet.</h3><p>Browse the Quest Board and accept a mission.</p><a class="btn gold" href="<?=base_url('quests.php')?>">Browse Quests</a></div><?php endif; ?>
</div></section>
<section class="my-quests-section"><div class="section-head"><div><span class="eyebrow">CREATOR CONTROL</span><h2>QUESTS I CREATED</h2></div></div>
<div class="my-quests-grid">
<?php foreach($created as $q): ?><article class="my-quest-card"><div><span class="quest-kicker"><?=e(strtoupper($q['category']))?> · <?=e(strtoupper($q['difficulty']))?></span><h2><a href="<?=base_url('quest.php?id='.(int)$q['id'])?>"><?=e($q['title'])?></a></h2><p><?=e(mb_strimwidth($q['description'],0,180,'…'))?></p><div class="meta-row"><span><?=e($q['location'])?></span><span><?=date('M j · g:i A',strtotime($q['scheduled_at']))?></span><span><?=e($q['participant_count'])?> / <?=e($q['max_participants'])?> rostered</span></div></div><div class="my-quest-actions"><span class="status <?=e($q['status'])?>"><?=e(status_label($q['status']))?></span><a class="btn gold" href="<?=base_url('edit-quest.php?id='.(int)$q['id'])?>">Manage Quest</a></div></article><?php endforeach; if(!$created): ?><div class="empty"><h3>You haven't created a quest yet.</h3><p>Post your first mission and it will appear here.</p><a class="btn gold" href="<?=base_url('create-quest.php')?>">+ Post a Quest</a></div><?php endif; ?>
</div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
