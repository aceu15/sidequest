<?php
require_once __DIR__ . '/functions.php';
$me = user();
$flash = get_flash();
$unread = 0;
if ($me) {
    $s = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $s->execute([$me['id']]);
    $unread = (int)$s->fetchColumn();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? APP_NAME) ?> · SIDEQUEST</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css?v=4.1') ?>">
</head>
<body>
<div class="site-shell">
<header class="topbar">
  <a class="brand" href="<?= base_url('index.php') ?>"><span class="brand-mark">SQ</span><span>SIDEQUEST</span></a>
  <nav class="nav">
    <a href="<?= base_url('quests.php') ?>">Quest Board</a>
    <a href="<?= base_url('leaderboard.php') ?>">Leaderboard</a>
    <?php if ($me): ?>
      <a href="<?= base_url('dashboard.php') ?>">Command Center</a>
      <a href="<?= base_url('my-quests.php') ?>">My Quests</a>
      <a href="<?= base_url('create-quest.php') ?>" class="nav-cta">+ Post Quest</a>
      <?php if (($me['role'] ?? 'user') === 'admin'): ?>
        <a href="<?= base_url('admin/index.php') ?>" class="admin-link">Admin</a>
      <?php endif; ?>
      <a href="<?= base_url('notifications.php') ?>" class="notif">◌<?php if ($unread): ?><b><?= $unread ?></b><?php endif; ?></a>
      <a href="<?= base_url('profile.php?id='.(int)$me['id']) ?>" aria-label="Open profile"><?= avatar_markup($me, 'avatar') ?></a>
      <a href="<?= base_url('logout.php') ?>" class="logout-link">Log out</a>
    <?php else: ?>
      <a href="<?= base_url('login.php') ?>">Sign in</a>
      <a href="<?= base_url('register.php') ?>" class="nav-cta">Join SIDEQUEST</a>
    <?php endif; ?>
  </nav>
</header>
<?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<main>
