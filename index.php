<?php
require_once __DIR__ . '/includes/functions.php';
$page_title = 'The world is full of SideQuests';
$pdo = db();
$featured = $pdo->query("SELECT q.*, c.name category, u.username FROM quests q JOIN categories c ON c.id=q.category_id JOIN users u ON u.id=q.creator_id WHERE q.status='open' ORDER BY q.created_at DESC LIMIT 4")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-copy">
    <div class="eyebrow">REAL-WORLD QUEST PLATFORM</div>
    <h1>THE WORLD IS<br><em>FULL OF</em> SIDEQUESTS.</h1>
    <p>Post something worth doing. Find something worth joining. Meet people, complete missions, earn XP, and build your reputation.</p>
    <div class="hero-actions">
      <a class="btn gold" href="<?= base_url('quests.php') ?>">Explore SideQuests →</a>
      <a class="btn ghost" href="<?= base_url($me ? 'create-quest.php' : 'register.php') ?>">Post a Quest</a>
    </div>
    <div class="hero-stats">
      <span><strong><?= (int)$pdo->query("SELECT COUNT(*) FROM quests")->fetchColumn() ?></strong> quests posted</span>
      <span><strong><?= (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?></strong> players</span>
      <span><strong><?= (int)$pdo->query("SELECT COUNT(*) FROM quest_participants WHERE status='completed'")->fetchColumn() ?></strong> completed</span>
    </div>
  </div>
  <div class="hero-board">
    <div class="board-top"><span>LIVE QUEST BOARD</span><span class="pulse">● LIVE</span></div>
    <?php foreach ($featured as $i => $q): ?>
    <a class="mini-quest" href="<?= base_url('quest.php?id='.(int)$q['id']) ?>">
      <div class="mini-index">0<?= $i+1 ?></div>
      <div class="mini-body"><small><?= e(strtoupper($q['category'])) ?> · <?= e($q['difficulty']) ?></small><h3><?= e($q['title']) ?></h3><p><?= e($q['location']) ?></p></div>
      <div class="mini-xp">+<?= (int)$q['xp_reward'] ?> XP</div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<section class="section">
  <div class="section-head"><div><span class="eyebrow">HOW IT WORKS</span><h2>TURN FREE TIME INTO A MISSION.</h2></div></div>
  <div class="steps">
    <div><b>01</b><h3>DISCOVER</h3><p>Browse real-world SideQuests by distance, reward, category, difficulty, and availability.</p></div>
    <div><b>02</b><h3>ACCEPT</h3><p>Request a slot, get accepted, and enter the quest flow with a clear mission brief.</p></div>
    <div><b>03</b><h3>COMPLETE</h3><p>Finish the mission, get creator confirmation, earn XP, unlock achievements, and climb.</p></div>
  </div>
</section>
<section class="section split-callout">
  <div><span class="eyebrow">PLAYER PROGRESSION</span><h2>YOUR LIFE, BUT WITH XP.</h2><p>Every completed quest makes your player profile stronger. Level up to unlock more active quest capacity.</p><a href="<?= base_url('leaderboard.php') ?>" class="text-link">See the Hall of Fame →</a></div>
  <div class="xp-demo"><span>LEVEL 08</span><strong>1,840 <small>/ 2,000 XP</small></strong><div class="progress"><i style="width:82%"></i></div><div class="xp-meta"><span>162 XP to next level</span><span>2 / 3 ACTIVE QUESTS</span></div></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
