<?php
require_once __DIR__.'/../includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf'] ?? null)) redirect('dashboard.php');

$pdo = db();
$qid = (int)($_POST['quest_id'] ?? 0);
$uid = (int)user()['id'];

$stmt = $pdo->prepare("SELECT q.title, q.status, qp.status participant_status FROM quests q JOIN quest_participants qp ON qp.quest_id=q.id AND qp.user_id=? WHERE q.id=?");
$stmt->execute([$uid, $qid]);
$row = $stmt->fetch();
if (!$row || !in_array($row['participant_status'], ['accepted'], true)) {
    flash('error', 'You can only start an accepted quest.');
    redirect('quest.php?id='.$qid);
}
if ($row['status'] === 'cancelled') {
    flash('error', 'This quest has been cancelled.');
    redirect('quest.php?id='.$qid);
}

$pdo->prepare("UPDATE quest_participants SET status='in_progress', started_at=NOW() WHERE quest_id=? AND user_id=? AND status='accepted'")
    ->execute([$qid, $uid]);
notify($uid, 'QUEST STARTED', 'You started “'.$row['title'].'”. Complete it when the mission is finished.', 'quest.php?id='.$qid);
flash('success', 'Quest started. Good luck, adventurer.');
redirect('quest.php?id='.$qid);
