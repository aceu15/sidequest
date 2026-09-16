<?php
require_once __DIR__.'/includes/functions.php';require_login();$page_title='Achievements';$pdo=db();$me=user();
$s=$pdo->prepare("SELECT a.*,ua.earned_at FROM achievements a LEFT JOIN user_achievements ua ON ua.achievement_id=a.id AND ua.user_id=? ORDER BY (ua.id IS NOT NULL) DESC,a.id");$s->execute([$me['id']]);$all=$s->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="page-head compact"><div><span class="eyebrow">PLAYER PROGRESSION</span><h1>ACHIEVEMENTS.</h1><p>Milestones that prove you actually showed up.</p></div></section>
<div class="achievement-grid"><?php foreach($all as $a):?><div class="achievement <?= $a['earned_at']?'earned':'locked'?>"><span><?= $a['earned_at']?'✦':'◇'?></span><div><h3><?=e($a['name'])?></h3><p><?=e($a['description'])?></p><?php if($a['earned_at']):?><small>UNLOCKED <?=date('M j, Y',strtotime($a['earned_at']))?></small><?php else:?><small>LOCKED</small><?php endif;?></div></div><?php endforeach;?></div>
<?php require __DIR__.'/includes/footer.php'; ?>
