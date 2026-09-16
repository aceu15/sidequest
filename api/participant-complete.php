<?php
require_once __DIR__.'/../includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf'] ?? null)) redirect('dashboard.php');

$pdo = db();
$qid = (int)($_POST['quest_id'] ?? 0);
$uid = (int)user()['id'];

$stmt = $pdo->prepare("SELECT q.*, qp.status participant_status FROM quests q JOIN quest_participants qp ON qp.quest_id=q.id AND qp.user_id=? WHERE q.id=?");
$stmt->execute([$uid, $qid]);
$q = $stmt->fetch();
if (!$q || !in_array($q['participant_status'], ['in_progress'], true)) {
    flash('error', 'This quest is not ready to be completed.');
    redirect('quest.php?id='.$qid);
}

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE quest_participants SET status='completed', completed_at=NOW() WHERE quest_id=? AND user_id=? AND status='in_progress'")
        ->execute([$qid, $uid]);

    $existing = $pdo->prepare("SELECT id,status FROM quest_completions WHERE quest_id=? AND participant_id=? ORDER BY id DESC LIMIT 1");
    $existing->execute([$qid, $uid]);
    $completion = $existing->fetch();
    if (!$completion) {
        $pdo->prepare("INSERT INTO quest_completions (quest_id, participant_id, participant_confirmed_at, status) VALUES (?,?,NOW(),'pending')")
            ->execute([$qid, $uid]);
    } elseif ($completion['status'] !== 'confirmed') {
        $pdo->prepare("UPDATE quest_completions SET participant_confirmed_at=NOW(), status='pending' WHERE id=?")
            ->execute([$completion['id']]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error', 'Unable to submit completion right now.');
    redirect('quest.php?id='.$qid);
}

notify((int)$q['creator_id'], 'COMPLETION READY', user()['username'].' marked “'.$q['title'].'” as completed. Review the player roster to confirm it.', 'edit-quest.php?id='.$qid);
notify($uid, 'COMPLETION SUBMITTED', 'Your completion of “'.$q['title'].'” is waiting for the quest creator to confirm.', 'quest.php?id='.$qid);
flash('success', 'Quest marked complete. Waiting for creator confirmation before XP and rewards are awarded.');
redirect('quest.php?id='.$qid);
