<?php
require_once __DIR__ . '/../includes/functions.php';require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf($_POST['csrf']??null))redirect('quests.php');
$pdo=db();$qid=(int)$_POST['quest_id'];$me=user();
try{
 $pdo->beginTransaction();
 $s=$pdo->prepare("SELECT q.*,COUNT(qp.id) participants FROM quests q LEFT JOIN quest_participants qp ON qp.quest_id=q.id AND qp.status IN ('requested','accepted','in_progress','completed') WHERE q.id=? GROUP BY q.id FOR UPDATE");$s->execute([$qid]);$q=$s->fetch();
 if(!$q||$q['status']!=='open')throw new Exception('Quest is not open.');
 if((int)$q['creator_id']===(int)$me['id'])throw new Exception('You cannot join your own quest.');
 if((int)$q['participants'] >= (int)$q['max_participants'])throw new Exception('Quest is full.');
 if(active_quest_count((int)$me['id']) >= quest_capacity((int)$me['level']))throw new Exception('You have reached your active quest capacity. Complete or leave an active quest before joining another.');
 $exists=$pdo->prepare("SELECT id FROM quest_participants WHERE quest_id=? AND user_id=?");$exists->execute([$qid,$me['id']]);if($exists->fetch())throw new Exception('You already have a participation record.');
 $pdo->prepare("INSERT INTO quest_participants (quest_id,user_id,status) VALUES (?,?, 'requested')")->execute([$qid,$me['id']]);
 notify((int)$q['creator_id'],'NEW PLAYER REQUEST',$me['username'].' requested to join “'.$q['title'].'”.','edit-quest.php?id='.$qid);
 $pdo->commit();flash('success','Request sent. The creator will confirm your slot.');
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error',$e->getMessage());}
redirect('quest.php?id='.$qid);
