<?php
require_once __DIR__.'/includes/functions.php';require_login();$id=(int)($_GET['id']??0);$pdo=db();
$s=$pdo->prepare("SELECT * FROM quests WHERE id=? AND creator_id=?");$s->execute([$id,user()['id']]);$q=$s->fetch();if(!$q){http_response_code(403);exit('Quest not found or not yours.');}
if($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??null)){
 $action=$_POST['action']??'save';
 if($action==='cancel'){$pdo->prepare("UPDATE quests SET status='cancelled' WHERE id=?")->execute([$id]);flash('success','Quest cancelled.');redirect('quest.php?id='.$id);}
 if($action==='complete'){
  $pdo->beginTransaction();
  try {
   // Finishing a mission as its creator closes the mission and confirms every
   // participant who was accepted, in progress, or had already submitted completion.
   $eligible=$pdo->prepare("SELECT user_id FROM quest_participants WHERE quest_id=? AND status IN ('accepted','in_progress','completed') FOR UPDATE");
   $eligible->execute([$id]);
   $players=$eligible->fetchAll(PDO::FETCH_COLUMN);
   $confirmed=0;
   foreach($players as $playerId){ if(finalize_participant_completion($pdo,$id,(int)$playerId)) $confirmed++; }
   $pdo->prepare("UPDATE quests SET status='completed' WHERE id=? AND status NOT IN ('completed','cancelled')")->execute([$id]);
   $pdo->commit();
   foreach($players as $playerId){ notify((int)$playerId,'QUEST FINISHED','The creator finished the quest. Your completion, XP, rewards, and achievements are now updated.','dashboard.php'); }
   flash('success','Quest finished. '.$confirmed.' participant completion'.($confirmed===1?' was':'s were').' confirmed and rewards/achievements updated.');
  } catch(Throwable $e){
   if($pdo->inTransaction()) $pdo->rollBack();
   flash('error','Unable to finish this quest right now.');
  }
  redirect('quest.php?id='.$id);
 }
 $title=trim($_POST['title']);$description=trim($_POST['description']);$location=trim($_POST['location']);$requirements=trim($_POST['requirements']);$instructions=trim($_POST['instructions']);$reward=max(0,(float)$_POST['reward']);
 $pdo->prepare("UPDATE quests SET title=?,description=?,location=?,requirements=?,instructions=?,reward_amount=? WHERE id=?")->execute([$title,$description,$location,$requirements,$instructions,$reward,$id]);flash('success','Quest updated.');redirect('quest.php?id='.$id);
}
$parts=$pdo->prepare("SELECT qp.*,u.username,u.avatar_path FROM quest_participants qp JOIN users u ON u.id=qp.user_id WHERE qp.quest_id=? ORDER BY qp.requested_at DESC");$parts->execute([$id]);$parts=$parts->fetchAll();
$page_title='Manage Quest';require __DIR__.'/includes/header.php';
?>
<div class="form-page"><div class="page-head compact"><div><span class="eyebrow">CREATOR CONTROL</span><h1>MANAGE MISSION.</h1><p><?=e($q['title'])?></p></div></div>
<form method="post" class="quest-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<section><div class="two"><label>Title<input name="title" value="<?=e($q['title'])?>" required></label><label>Location<input name="location" value="<?=e($q['location'])?>" required></label></div><label>Description<textarea name="description" rows="5"><?=e($q['description'])?></textarea></label><label>Reward (₱)<input name="reward" type="number" step="50" min="0" value="<?=e($q['reward_amount'])?>"></label><label>Requirements<textarea name="requirements" rows="3"><?=e($q['requirements'])?></textarea></label><label>Instructions<textarea name="instructions" rows="3"><?=e($q['instructions'])?></textarea></label></section>
<div class="submit-row"><button class="btn gold" name="action" value="save">Save Changes</button><div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end"><?php if(in_array($q['status'],['open','requested','accepted','in_progress'],true)): ?><button class="btn" name="action" value="complete">✓ Mark Quest Finished</button><button class="btn danger" name="action" value="cancel">Cancel Quest</button><?php elseif($q['status']==='completed' && array_filter($parts,fn($p)=>in_array($p['status'],['accepted','in_progress','completed'],true))): ?><button class="btn" name="action" value="complete">✓ Finalize Participant Completions</button><?php endif;?></div></div></form>
<section class="participants"><div class="section-head"><div><span class="eyebrow">PLAYER ROSTER</span><h2>PARTICIPANTS</h2></div></div>
<?php foreach($parts as $p):?><div class="participant"><?=avatar_markup($p, 'avatar')?> <strong><a href="<?=base_url('profile.php?id='.(int)$p['user_id'])?>"><?=e($p['username'])?></a></strong><span class="status <?=e($p['status'])?>"><?=e(status_label($p['status']))?></span><?php if($p['status']==='requested'):?><form method="post" action="<?=base_url('api/participant.php')?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="quest_id" value="<?=$id?>"><input type="hidden" name="participant_id" value="<?=$p['user_id']?>"><button name="action" value="accept" class="small-btn">Accept</button><button name="action" value="reject" class="small-btn danger-text">Reject</button></form><?php elseif($p['status']==='completed'): $cc=$pdo->prepare("SELECT status FROM quest_completions WHERE quest_id=? AND participant_id=? ORDER BY id DESC LIMIT 1");$cc->execute([$id,$p['user_id']]);$completionStatus=$cc->fetchColumn(); if($completionStatus==='pending'): ?><form method="post" action="<?=base_url('api/complete.php')?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="quest_id" value="<?=$id?>"><input type="hidden" name="participant_id" value="<?=$p['user_id']?>"><button class="small-btn">Confirm Completion</button></form><?php else: ?><small class="completion-note">Completion confirmed</small><?php endif; ?><?php endif;?></div><?php endforeach;if(!$parts):?><div class="empty">No players yet. Share the quest and let the roster grow.</div><?php endif;?></section></div>
<?php require __DIR__.'/includes/footer.php'; ?>
