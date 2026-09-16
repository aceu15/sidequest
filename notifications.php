<?php
require_once __DIR__.'/includes/functions.php';require_login();$me=user();$pdo=db();$pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$me['id']]);
$n=$pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");$n->execute([$me['id']]);$n=$n->fetchAll();$page_title='Notifications';require __DIR__.'/includes/header.php';
?>
<section class="page-head compact"><div><span class="eyebrow">INCOMING INTEL</span><h1>NOTIFICATIONS.</h1><p>Quest activity, XP, achievements, and roster updates.</p></div></section>
<div class="notification-list"><?php foreach($n as $x):?><a href="<?=e($x['link']?base_url($x['link']):'#')?>" class="notification"><span>◉</span><div><b><?=e($x['title'])?></b><p><?=e($x['message'])?></p><small><?=date('M j · g:i A',strtotime($x['created_at']))?></small></div></a><?php endforeach;if(!$n):?><div class="empty">No new intelligence.</div><?php endif;?></div>
<?php require __DIR__.'/includes/footer.php'; ?>
