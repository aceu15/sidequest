<?php
require_once __DIR__.'/../includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf'] ?? null)) redirect('dashboard.php');

$pdo = db();
$qid = (int)($_POST['quest_id'] ?? 0);
$uid = (int)($_POST['participant_id'] ?? 0);
$creatorId = (int)user()['id'];

$check = $pdo->prepare("SELECT q.title, qp.status participant_status FROM quests q JOIN quest_participants qp ON qp.quest_id=q.id AND qp.user_id=? WHERE q.id=? AND q.creator_id=?");
$check->execute([$uid, $qid, $creatorId]);
$q = $check->fetch();
if (!$q) {
    flash('error', 'Invalid completion request.');
    redirect('dashboard.php');
}

if (!in_array($q['participant_status'], ['completed','accepted','in_progress'], true)) {
    flash('error', 'There is no pending completion to confirm.');
    redirect('edit-quest.php?id='.$qid);
}

$pdo->beginTransaction();
try {
    $changed = finalize_participant_completion($pdo, $qid, $uid);
    if (!$changed) {
        $pdo->rollBack();
        flash('error', 'This completion has already been confirmed.');
        redirect('edit-quest.php?id='.$qid);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'Unable to confirm completion right now.');
    redirect('edit-quest.php?id='.$qid);
}

notify($uid, 'QUEST CONFIRMED', 'Your completion was confirmed. XP, rewards, and achievements have been updated.', 'dashboard.php');
flash('success', 'Completion confirmed. XP, rewards, and achievements updated for the player.');
redirect('edit-quest.php?id='.$qid);
