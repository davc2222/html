<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php'; require_once __DIR__.'/../config/config.php'; if(session_status()===PHP_SESSION_NONE)session_start();
function back(int $id,string $m,string $t='success'):never{header('Location: /admin/user_view.php?'.http_build_query(['id'=>$id,'message'=>$m,'type'=>$t]));exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: /admin/users.php');exit;}
$id=filter_input(INPUT_POST,'user_id',FILTER_VALIDATE_INT); if(!$id)exit('מזהה לא תקין');
if(empty($_SESSION['admin_user_csrf'])||!hash_equals((string)$_SESSION['admin_user_csrf'],(string)($_POST['csrf_token']??'')))back($id,'בקשה לא תקינה','error');
$pf=require __DIR__.'/../profile_fields.php'; if(!is_array($pf))$pf=[]; $a=(string)($_POST['action']??'');
try{
if($a==='save_field'){ $f=(string)($_POST['field']??''); if(!isset($pf[$f])||!preg_match('/^[A-Za-z0-9_]+$/',$f))throw new RuntimeException('שדה לא מורשה'); $s=$pdo->prepare("UPDATE users_profile SET `$f`=? WHERE Id=? LIMIT 1");$s->execute([trim((string)($_POST['value']??'')),$id]);back($id,'הטקסט נשמר'); }
if($a==='save_basic'){ $allowed=['Name','Email','DOB','Gender_Str','Zone_Str','Place_Str'];$set=[];$p=[':id'=>$id];foreach($allowed as $f){if(array_key_exists($f,$_POST)){$set[]="`$f`=:$f";$p[":$f"]=trim((string)$_POST[$f]);}}if($set){$s=$pdo->prepare('UPDATE users_profile SET '.implode(',',$set).' WHERE Id=:id LIMIT 1');$s->execute($p);}back($id,'הפרטים נשמרו'); }
if($a==='save_fields'){ $allowed=[];foreach($pf as $f=>$cfg)if(($cfg['side']??'')==='right'&&($cfg['type']??'')!=='computed')$allowed[$f]=1;$set=[];$p=[':id'=>$id];foreach((array)($_POST['fields']??[]) as $f=>$v){if(!isset($allowed[$f])||!preg_match('/^[A-Za-z0-9_]+$/',$f))continue;$ph=':f'.count($set);$set[]="`$f`=$ph";$p[$ph]=trim((string)$v);}if($set){$s=$pdo->prepare('UPDATE users_profile SET '.implode(',',$set).' WHERE Id=:id LIMIT 1');$s->execute($p);}back($id,'פרטי הפרופיל נשמרו'); }
throw new RuntimeException('פעולה לא מוכרת');
}catch(Throwable $e){back($id,$e->getMessage(),'error');}
