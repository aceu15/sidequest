<?php
require_once __DIR__.'/../includes/functions.php';require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'&&verify_csrf($_POST['csrf']??null)){
 $status=($_POST['action']??'resolve')==='dismiss'?'dismissed':'resolved';
 db()->prepare("UPDATE reports SET status=?,resolved_by=?,resolved_at=NOW() WHERE id=? AND status='open'")->execute([$status,user()['id'],(int)$_POST['id']]);
 flash('success',$status==='resolved'?'Report resolved.':'Report dismissed.');
}
redirect('admin/index.php');
