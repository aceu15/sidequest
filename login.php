<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect('dashboard.php');
$page_title='Sign In'; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) $error='Invalid session token.';
    else {
        $stmt=db()->prepare("SELECT * FROM users WHERE email=? OR username=? LIMIT 1");
        $stmt->execute([trim($_POST['login']??''),trim($_POST['login']??'')]);
        $u=$stmt->fetch();
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id']=$u['id']; session_regenerate_id(true); redirect('dashboard.php');
        } else $error='Incorrect login details.';
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap"><div class="auth-card"><span class="eyebrow">RETURNING PLAYER</span><h1>WELCOME BACK.</h1><p>Your active quests are waiting.</p>
<?php if($error): ?><div class="inline-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>Email or username<input name="login" required autofocus></label><label>Password<div class="password-wrap"><input id="loginPassword" name="password" type="password" required autocomplete="current-password"><button type="button" class="password-toggle" data-password-toggle="loginPassword" aria-label="Show password" aria-pressed="false"><svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></label>
<button class="btn gold full">Enter Command Center</button></form>
<p class="form-foot">New here? <a href="<?= base_url('register.php') ?>">Create a player</a>.</p></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
