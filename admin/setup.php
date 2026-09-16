<?php
require_once __DIR__.'/../includes/functions.php';
require_login();
$pdo=db();
$page_title='Admin Setup';
$adminCount=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
if($adminCount>0){ http_response_code(403); exit('Initial admin setup is already locked.'); }
if($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??null)){
    $pdo->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([(int)user()['id']]);
    flash('success','Administrator access enabled for your account.');
    redirect('admin/index.php');
}
require __DIR__.'/../includes/header.php';
?>
<div class="auth-wrap"><div class="auth-card"><span class="eyebrow">INITIAL ADMIN SETUP</span><h1>CLAIM CONTROL.</h1><p>No administrator account exists yet. Use the currently signed-in account as the first SIDEQUEST administrator.</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="btn gold full">Enable Admin Access</button></form><p class="form-foot">This setup page automatically locks after the first admin is created.</p></div></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
