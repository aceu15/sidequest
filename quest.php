<?php
require_once __DIR__ . '/includes/functions.php';
$id=(int)($_GET['id']??0);$pdo=db();
$stmt=$pdo->prepare("SELECT q.*,c.name category,u.username,u.id creator_id,u.avatar_path,(SELECT COUNT(*) FROM quest_participants qp WHERE qp.quest_id=q.id AND qp.status IN ('requested','accepted','in_progress','completed')) participant_count FROM quests q JOIN categories c ON c.id=q.category_id JOIN users u ON u.id=q.creator_id WHERE q.id=?");
$stmt->execute([$id]);$q=$stmt->fetch();if(!$q){http_response_code(404);exit('Quest not found');}
$page_title=$q['title'];$me=user();$myStatus=null;
if($me){$s=$pdo->prepare("SELECT status FROM quest_participants WHERE quest_id=? AND user_id=?");$s->execute([$id,$me['id']]);$myStatus=$s->fetchColumn() ?: null;}
require __DIR__.'/includes/header.php';
?>
<section class="mission-head"><div><span class="eyebrow"><?=e(strtoupper($q['category']))?> · <?=e(strtoupper($q['difficulty']))?></span><h1><?=e($q['title'])?></h1><p class="creator">CREATED BY <a class="creator-link" href="<?=base_url('profile.php?id='.(int)$q['creator_id'])?>"><?=avatar_markup(['id'=>$q['creator_id'],'username'=>$q['username'],'avatar_path'=>$q['avatar_path']], 'avatar')?> <span><?=e($q['username'])?></span></a></p></div><span class="status <?=e($q['status'])?>"><?=e(status_label($q['status']))?></span></section>
<div class="mission-grid"><article class="brief">
<div class="brief-top"><span>MISSION BRIEF</span><span>#<?=str_pad((string)$q['id'],5,'0',STR_PAD_LEFT)?></span></div>
<p class="lead"><?=nl2br(e($q['description']))?></p>
<div class="detail-grid"><div><small>LOCATION</small><strong>⌖ <?=e($q['location'])?></strong></div><div><small>DATE / TIME</small><strong><?=date('M j, Y · g:i A',strtotime($q['scheduled_at']))?></strong></div><div><small>DURATION</small><strong><?=e($q['duration_minutes'])?> min</strong></div><div><small>PARTICIPANTS</small><strong><?=e($q['participant_count'])?> / <?=e($q['max_participants'])?></strong></div></div>
<?php if($q['requirements']): ?><div class="brief-section"><small>REQUIREMENTS</small><p><?=nl2br(e($q['requirements']))?></p></div><?php endif;?>
<?php if($q['instructions']): ?><div class="brief-section"><small>INSTRUCTIONS / RULES</small><p><?=nl2br(e($q['instructions']))?></p></div><?php endif;?>
<div class="safety"><b>SAFETY NOTE</b><span>Meet in appropriate public settings, use good judgment, and report quests that appear unsafe, misleading, or inappropriate.</span></div>
</article>
<aside class="mission-side"><div class="reward-box"><small>MISSION REWARD</small><strong><?= (float)$q['reward_amount']>0 ? '₱'.number_format((float)$q['reward_amount'],0) : 'NO REWARD' ?></strong><span>+<?=e($q['xp_reward'])?> XP</span></div>
<?php if($me && $me['id']===$q['creator_id']): ?><a class="btn gold full" href="<?=base_url('edit-quest.php?id='.$id)?>">Manage Quest</a>
<?php elseif(!$me): ?><a class="btn gold full" href="<?=base_url('login.php')?>">Sign in to Join</a>
<?php elseif($myStatus==='accepted'): ?><form method="post" action="<?=base_url('api/start.php')?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="quest_id" value="<?=$id?>"><button class="btn gold full">Start Quest →</button></form><div class="state-box">YOUR STATUS<br><strong>ACCEPTED</strong></div>
<?php elseif($myStatus==='in_progress'): ?><form method="post" action="<?=base_url('api/participant-complete.php')?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="quest_id" value="<?=$id?>"><button class="btn gold full">✓ Mark Quest Complete</button></form><div class="state-box">YOUR STATUS<br><strong>IN PROGRESS</strong></div>
<?php elseif($myStatus==='completed'): ?><div class="state-box">YOUR STATUS<br><strong>COMPLETION SUBMITTED</strong></div>
<?php elseif($myStatus==='requested'): ?><div class="state-box">YOUR STATUS<br><strong>REQUESTED</strong></div>
<?php elseif((int)$q['participant_count'] >= (int)$q['max_participants']): ?><div class="state-box">QUEST FULL</div>
<?php else: ?><form method="post" action="<?=base_url('api/join.php')?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="quest_id" value="<?=$id?>"><button class="btn gold full">Accept Quest →</button></form><?php endif;?>
<button class="btn ghost full" onclick="openReport()">Report Quest</button>
<div class="side-stats"><span>DIFFICULTY <b><?=e($q['difficulty'])?></b></span><span>CAPACITY <b><?=e($q['max_participants'])?></b></span><span>POSTED <b><?=date('M j',strtotime($q['created_at']))?></b></span></div></aside></div>
<div id="reportModal" class="modal"><div class="modal-card"><button onclick="closeReport()" class="modal-close">×</button><span class="eyebrow">COMMUNITY SAFETY</span><h2>REPORT THIS QUEST</h2><form method="post" action="<?=base_url('api/report.php')?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="quest_id" value="<?=$id?>"><label>Reason<select name="reason"><option>Inappropriate content</option><option>Unsafe activity</option><option>Suspicious behavior</option><option>Misleading reward</option><option>Spam</option></select></label><label>Details<textarea name="details" rows="4"></textarea></label><button class="btn gold full">Submit Report</button></form></div></div>
<?php require __DIR__.'/includes/footer.php'; ?>
