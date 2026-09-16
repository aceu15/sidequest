<?php
require_once __DIR__.'/includes/functions.php';
$pdo=db();
$me=user();
$id=(int)($_GET['id'] ?? ($me['id'] ?? 0));
if($id<=0){ http_response_code(404); exit('Profile not found.'); }

$s=$pdo->prepare("SELECT * FROM users WHERE id=?");$s->execute([$id]);$p=$s->fetch();
if(!$p){http_response_code(404);exit('Player not found');}

$isOwn = $me && (int)$me['id']===$id;
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!$isOwn){http_response_code(403);exit('Forbidden');}
    if(!verify_csrf($_POST['csrf']??null)){$error='Invalid session token.';}
    $username=trim($_POST['username']??'');
    $email=trim($_POST['email']??'');
    $bio=trim($_POST['bio']??'');
    $currentPassword=$_POST['current_password']??'';
    $newPassword=$_POST['new_password']??'';
    if(!$error && (strlen($username)<3 || strlen($username)>30))$error='Username must be 3–30 characters.';
    if(!$error && !filter_var($email,FILTER_VALIDATE_EMAIL))$error='Enter a valid email.';
    if(!$error && strlen($bio)>500)$error='Bio must be 500 characters or fewer.';
    if(!$error && $newPassword!=='' && !password_verify($currentPassword,$p['password_hash']))$error='Enter your current password before changing it.';
    if(!$error && $newPassword!=='' && strlen($newPassword)<6)$error='New password must be at least 6 characters.';
    $avatarPath=$p['avatar_path']??null;
    if(!$error && !empty($_FILES['avatar']['name'])){
        if($_FILES['avatar']['error']!==UPLOAD_ERR_OK || $_FILES['avatar']['size']>5*1024*1024)$error='Profile picture must be an image up to 5 MB.';
        else{
            $info=@getimagesize($_FILES['avatar']['tmp_name']);
            $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            $mime=$info['mime']??'';
            if(!$info || !isset($allowed[$mime]))$error='Use a JPG, PNG, WEBP, or GIF image.';
            else{
                $dir=dirname(__DIR__).'/SIDEQUEST_USER_DATA/avatars';if(!is_dir($dir))mkdir($dir,0755,true);
                $name='avatar_'.$id.'_'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
                if(!move_uploaded_file($_FILES['avatar']['tmp_name'],$dir.'/'.$name))$error='Could not save the profile picture.';
                else $avatarPath=$name;
            }
        }
    }
    if(!$error){
        try{
            $check=$pdo->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id<>?");$check->execute([$username,$email,$id]);
            if($check->fetch())$error='That username or email is already in use.';
            else{
                if($newPassword!==''){
                    $pdo->prepare("UPDATE users SET username=?,email=?,bio=?,avatar_path=?,password_hash=? WHERE id=?")->execute([$username,$email,$bio,$avatarPath,password_hash($newPassword,PASSWORD_DEFAULT),$id]);
                }else{
                    $pdo->prepare("UPDATE users SET username=?,email=?,bio=?,avatar_path=? WHERE id=?")->execute([$username,$email,$bio,$avatarPath,$id]);
                }
                flash('success','Profile updated.'); redirect('profile.php?id='.$id);
            }
        }catch(PDOException $e){$error='Unable to update the profile right now.';}
    }
    $p=array_merge($p,['username'=>$username,'email'=>$email,'bio'=>$bio,'avatar_path'=>$avatarPath]);
}

$completed=(int)$pdo->query("SELECT COUNT(*) FROM quest_participants WHERE user_id=$id AND status='completed'")->fetchColumn();
$created=(int)$pdo->query("SELECT COUNT(*) FROM quests WHERE creator_id=$id")->fetchColumn();
$active=(int)$pdo->query("SELECT COUNT(*) FROM quest_participants WHERE user_id=$id AND status IN ('accepted','in_progress')")->fetchColumn();
$ach=$pdo->prepare("SELECT a.* FROM user_achievements ua JOIN achievements a ON a.id=ua.achievement_id WHERE ua.user_id=? ORDER BY ua.earned_at DESC");$ach->execute([$id]);$ach=$ach->fetchAll();
$page_title=$p['username'].' Profile';require __DIR__.'/includes/header.php';
?>
<section class="profile-hero"><div class="profile-avatar-wrap"><?=avatar_markup($p,'big-avatar')?></div><div class="profile-identity"><span class="eyebrow">PLAYER PROFILE</span><h1><?=e($p['username'])?></h1><p>LEVEL <?=$p['level']?> · <?=number_format($p['xp'])?> XP · Joined <?=date('M Y',strtotime($p['created_at']))?></p><?php if(trim($p['bio']??'')): ?><div class="profile-bio"><?=nl2br(e($p['bio']))?></div><?php else: ?><div class="profile-bio muted-bio">No bio yet.</div><?php endif;?></div><?php if($isOwn): ?><button type="button" class="btn ghost profile-edit-toggle" data-profile-edit-toggle>Edit Profile</button><?php endif;?></section>
<?php if($isOwn): ?><section class="profile-editor" id="profileEditor" hidden><div class="form-card profile-editor-card"><div class="section-head"><div><span class="eyebrow">PLAYER SETTINGS</span><h2>EDIT PROFILE</h2></div></div><?php if($error): ?><div class="inline-error"><?=e($error)?></div><?php endif; ?><form method="post" enctype="multipart/form-data" class="profile-edit-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="profile-edit-grid"><div class="avatar-upload-card"><div id="avatarPreview"><?=avatar_markup($p,'big-avatar')?></div><label class="upload-drop"> <span>PROFILE PICTURE</span><strong>Choose an image</strong><small>JPG, PNG, WEBP, or GIF · max 5 MB</small><input id="avatarInput" type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif"></label></div><div class="profile-fields"><label>Username<input name="username" value="<?=e($p['username'])?>" maxlength="30" required></label><label>Email<input name="email" type="email" value="<?=e($p['email'])?>" required></label><label>Bio<textarea name="bio" rows="5" maxlength="500" placeholder="Tell people a little about yourself..."><?=e($p['bio']??'')?></textarea><small class="field-help"><span id="bioCount"><?=strlen((string)($p['bio']??''))?></span>/500 characters</small></label></div></div><details class="security-panel"><summary>Change password</summary><div class="two"><label>Current password<div class="password-wrap"><input id="profileCurrentPassword" name="current_password" type="password" autocomplete="current-password"><button type="button" class="password-toggle" data-password-toggle="profileCurrentPassword" aria-label="Show password" aria-pressed="false">◉</button></div></label><label>New password<div class="password-wrap"><input id="profileNewPassword" name="new_password" type="password" minlength="6" autocomplete="new-password"><button type="button" class="password-toggle" data-password-toggle="profileNewPassword" aria-label="Show password" aria-pressed="false">◉</button></div></label></div></details><div class="submit-row"><button type="button" class="btn ghost" data-profile-cancel>Cancel</button><button class="btn gold">Save Profile Changes</button></div></form></div></section><?php endif; ?>
<div class="profile-stats"><div><b><?=$completed?></b><span>QUESTS COMPLETED</span></div><div><b><?=$created?></b><span>QUESTS CREATED</span></div><div><b><?=$active?></b><span>ACTIVE QUESTS</span></div><div><b><?=quest_capacity((int)$p['level'])?></b><span>QUEST CAPACITY</span></div></div>
<section class="section"><div class="section-head"><div><span class="eyebrow">PLAYER RECORD</span><h2>ACHIEVEMENTS</h2></div></div><div class="achievement-grid"><?php foreach($ach as $a):?><div class="achievement"><span>✦</span><div><h3><?=e($a['name'])?></h3><p><?=e($a['description'])?></p></div></div><?php endforeach;if(!$ach):?><div class="empty">No achievements unlocked yet.</div><?php endif;?></div></section>
<script>
(()=>{const ed=document.getElementById('profileEditor'),toggle=document.querySelector('[data-profile-edit-toggle]');if(!ed||!toggle)return;toggle.addEventListener('click',()=>{ed.hidden=!ed.hidden;if(!ed.hidden)ed.scrollIntoView({behavior:'smooth',block:'start'});});document.querySelector('[data-profile-cancel]')?.addEventListener('click',()=>{ed.hidden=true;window.scrollTo({top:0,behavior:'smooth'});});const input=document.getElementById('avatarInput'),preview=document.getElementById('avatarPreview');input?.addEventListener('change',()=>{const f=input.files?.[0];if(!f)return;const url=URL.createObjectURL(f);preview.innerHTML='<span class="big-avatar has-avatar"><img src="'+url+'" alt="Profile preview"></span>';});const bio=document.querySelector('textarea[name="bio"]'),count=document.getElementById('bioCount');bio?.addEventListener('input',()=>count.textContent=bio.value.length);})();
</script>
<?php require __DIR__.'/includes/footer.php'; ?>
