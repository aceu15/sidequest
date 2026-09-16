<?php
require_once __DIR__.'/../includes/functions.php';require_login();if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf($_POST['csrf']??null))redirect('quests.php');
$qid=(int)$_POST['quest_id'];$reason=trim($_POST['reason']??'');$details=trim($_POST['details']??'');$pdo=db();$pdo->prepare("INSERT INTO reports (reporter_id,quest_id,reason,details) VALUES (?,?,?,?)")->execute([user()['id'],$qid,$reason,$details]);flash('success','Report submitted to moderators.');redirect('quest.php?id='.$qid);
