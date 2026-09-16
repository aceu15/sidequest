<?php
require_once __DIR__.'/includes/functions.php'; require_login();
$page_title='Post a Quest';$pdo=db();$cats=$pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf($_POST['csrf']??null))$error='Invalid session token.';
 $title=trim($_POST['title']??'');$description=trim($_POST['description']??'');$category=(int)($_POST['category_id']??0);$location=trim($_POST['location']??'');$date=trim($_POST['date']??'');$time=trim($_POST['time']??'');$people=max(1,(int)($_POST['people']??1));$difficulty=$_POST['difficulty']??'Normal';$duration=max(15,(int)($_POST['duration']??60));$reward=max(0,(float)($_POST['reward']??0));$requirements=trim($_POST['requirements']??'');$instructions=trim($_POST['instructions']??'');$lat=$_POST['latitude']!==''? (float)$_POST['latitude']:null;$lon=$_POST['longitude']!==''?(float)$_POST['longitude']:null;
 if(!$error && (!$title||!$description||!$category||!$location||!$date||!$time))$error='Please complete all required mission details.';
 if(!$error){$xp=difficulty_xp($difficulty);$max=max(1,(int)($_POST['max_participants']??$people));$dt="$date $time";$stmt=$pdo->prepare("INSERT INTO quests (creator_id,title,description,category_id,location,latitude,longitude,scheduled_at,required_people,difficulty,duration_minutes,reward_amount,xp_reward,requirements,instructions,max_participants) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");$stmt->execute([user()['id'],$title,$description,$category,$location,$lat,$lon,$dt,(string)$people,$difficulty,$duration,$reward,$xp,$requirements,$instructions,$max]);flash('success','Quest published. Let the players find you.');redirect('quest.php?id='.$pdo->lastInsertId());}
}
require __DIR__.'/includes/header.php';
?>
<div class="form-page"><div class="page-head compact"><div><span class="eyebrow">QUEST CREATOR</span><h1>POST A MISSION.</h1><p>Give players a reason to show up.</p></div></div>
<?php if($error): ?><div class="inline-error"><?=e($error)?></div><?php endif;?>
<form method="post" class="quest-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<section><div class="form-section-title"><b>01</b><div><span>CORE BRIEF</span><small>What is the mission?</small></div></div>
<div class="two"><label>Quest title *<input name="title" required maxlength="100" placeholder="e.g. Sunset Basketball Run"></label><label>Category *<select name="category_id" required><?php foreach($cats as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select></label></div>
<label>Description *<textarea name="description" required rows="6" placeholder="Explain what players will actually do..."></textarea></label></section>
<section><div class="form-section-title"><b>02</b><div><span>MISSION LOGISTICS</span><small>When and where?</small></div></div>
<div class="two"><label>Location / address *<input name="location" required placeholder="e.g. Marilao Sports Complex"></label><label>Date *<input name="date" type="date" required></label></div>
<div class="three"><label>Time *<input name="time" type="time" required></label><label>Duration (minutes)<input name="duration" type="number" min="15" value="60"></label><label>People needed<input name="people" type="number" min="1" value="1"></label></div>
<div class="two"><label>Latitude <input name="latitude" placeholder="Optional"></label><label>Longitude <input name="longitude" placeholder="Optional"></label></div>
</section>
<section><div class="form-section-title"><b>03</b><div><span>DIFFICULTY & REWARD</span><small>Set the mission value.</small></div></div>
<div class="two"><label>Difficulty<select name="difficulty"><option>Easy</option><option selected>Normal</option><option>Hard</option><option>Epic</option></select></label><label>Maximum participants<input name="max_participants" type="number" min="1" value="1"></label></div>
<label>Cash reward (₱) <input name="reward" type="number" min="0" step="50" value="0"><small class="hint">Demo ledger only. No real payment is processed.</small></label>
</section>
<section><div class="form-section-title"><b>04</b><div><span>PLAYER NOTES</span><small>Requirements and instructions.</small></div></div>
<label>Requirements<textarea name="requirements" rows="4" placeholder="Optional: bring water, basic camera, student ID..."></textarea></label><label>Instructions / rules<textarea name="instructions" rows="4" placeholder="Optional mission rules..."></textarea></label></section>
<div class="submit-row"><span>XP is automatically calculated from difficulty.</span><button class="btn gold">Publish SideQuest →</button></div>
</form></div>
<?php require __DIR__.'/includes/footer.php'; ?>
