<?php
require_once __DIR__.'/includes/functions.php';
$id=(int)($_GET['id']??0);
if($id<=0){http_response_code(404);exit;}
$stmt=db()->prepare("SELECT username,avatar_path FROM users WHERE id=?");$stmt->execute([$id]);$u=$stmt->fetch();
if(!$u || empty($u['avatar_path'])){http_response_code(404);exit;}
$name=basename((string)$u['avatar_path']);
$storage=dirname(__DIR__).'/SIDEQUEST_USER_DATA/avatars/'.$name;
$legacy=__DIR__.'/uploads/avatars/'.$name;
$file=is_file($storage)?$storage:(is_file($legacy)?$legacy:null);
if(!$file){http_response_code(404);exit;}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file);
$allowed=['image/jpeg','image/png','image/webp','image/gif'];
if(!in_array($mime,$allowed,true)){http_response_code(415);exit;}
header('Content-Type: '.$mime);
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($file);
