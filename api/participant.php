<?php
require_once __DIR__.'/../includes/functions.php';require_login();if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf($_POST['csrf']??null))redirect('dashboard.php');
$pdo=db();$qid=(int)$_POST['quest_id'];$uid=(int)$_POST['participant_id'];$action=$_POST['action']??'';$s=$pdo->prepare("SELECT q.*,qp.status FROM quests q JOIN quest_participants qp ON qp.quest_id=q.id AND qp.user_id=? WHERE q.id=? AND q.creator_id=?");$s->execute([$uid,$qid,user()['id']]);$row=$s->fetch();
if(!$row){flash('error','Participant not found.');redirect('edit-quest.php?id='.$qid);}
if($action==='accept'){$pdo->prepare("UPDATE quest_participants SET status='accepted',accepted_at=NOW() WHERE quest_id=? AND user_id=?")->execute([$qid,$uid]);notify($uid,'QUEST ACCEPTED','You were accepted into “'.$row['title'].'”.','quest.php?id='.$qid);flash('success','Player accepted.');}
elseif($action==='reject'){$pdo->prepare("UPDATE quest_participants SET status='rejected' WHERE quest_id=? AND user_id=?")->execute([$qid,$uid]);notify($uid,'REQUEST DECLINED','Your request for “'.$row['title'].'” was declined.');flash('success','Player rejected.');}
redirect('edit-quest.php?id='.$qid);
