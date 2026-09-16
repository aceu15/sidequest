<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect('dashboard.php');
$page_title = 'Create Player';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) $error = 'Invalid session token.';
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$error && (strlen($username)<3 || strlen($username)>30)) $error = 'Username must be 3–30 characters.';
    if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email.';
    if (!$error && strlen($password)<6) $error = 'Password must be at least 6 characters.';
    if (!$error) {
        try {
            $stmt = db()->prepare("INSERT INTO users (username,email,password_hash) VALUES (?,?,?)");
            $stmt->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = (int)db()->lastInsertId();
            flash('success','Player created. Your first quest awaits.');
            redirect('dashboard.php');
        } catch (PDOException $e) { $error = 'Username or email is already in use.'; }
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap"><div class="auth-card"><span class="eyebrow">CREATE PLAYER</span><h1>ENTER THE QUEST.</h1><p>Build your player identity and start discovering missions.</p>
<?php if($error): ?><div class="inline-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-grid">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label>Username<input name="username" required maxlength="30" autocomplete="username"></label>
<label>Email<input name="email" type="email" required autocomplete="email"></label>
<label>Password<div class="password-wrap"><input id="registerPassword" name="password" type="password" required minlength="6" autocomplete="new-password"><button type="button" class="password-toggle" data-password-toggle="registerPassword" aria-label="Show password" aria-pressed="false"><svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></label>
<button class="btn gold full">Create Player</button>
</form><p class="form-foot">Already playing? <a href="<?= base_url('login.php') ?>">Sign in</a>.</p></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
