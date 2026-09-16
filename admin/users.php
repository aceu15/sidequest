<?php
require_once __DIR__.'/../includes/functions.php';require_admin();$pdo=db();$page_title='User Management';
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf($_POST['csrf']??null)){
 $id=(int)$_POST['id'];$action=$_POST['action']??'';
 if($id===(int)user()['id']){flash('error','You cannot change your own admin role here.');redirect('admin/users.php');}
 if($action==='toggle_admin'){$pdo->prepare("UPDATE users SET role=IF(role='admin','user','admin') WHERE id=?")->execute([$id]);flash('success','User role updated.');}
 elseif($action==='delete'){$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);flash('success','User deleted.');}
}
$users=$pdo->query("SELECT id,username,email,role,level,xp,created_at FROM users ORDER BY created_at DESC")->fetchAll();require __DIR__.'/../includes/header.php';
?><div class="admin"><div class="page-head compact"><div><span class="eyebrow">ADMINISTRATION</span><h1>USER MANAGEMENT.</h1><p>Review accounts and manage administrator access.</p></div></div><div class="admin-table"><?php foreach($users as $u):?><div class="admin-row"><div><b><?=e($u['username'])?></b><small><?=e($u['email'])?> · Level <?=e($u['level'])?> · <?=e($u['xp'])?> XP</small></div><span class="status"><?=e($u['role'])?></span><?php if((int)$u['id']!==(int)user()['id']):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="small-btn" name="action" value="toggle_admin"><?= $u['role']==='admin'?'Remove Admin':'Make Admin' ?></button><button class="small-btn danger-text" name="action" value="delete" onclick="return confirm('Delete this user?')">Delete</button></form><?php endif;?></div><?php endforeach;?></div></div><?php require __DIR__.'/../includes/footer.php'; ?>
