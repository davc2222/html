<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php';require_once __DIR__.'/../config/config.php';if(session_status()===PHP_SESSION_NONE)session_start();
function back(int $id,string $m,string $t='success'):never{header('Location: /admin/user_view.php?'.http_build_query(['id'=>$id,'message'=>$m,'type'=>$t]));exit;}
$id=filter_input(INPUT_POST,'user_id',FILTER_VALIDATE_INT);if(!$id)exit('מזהה לא תקין');if(empty($_SESSION['admin_user_csrf'])||!hash_equals((string)$_SESSION['admin_user_csrf'],(string)($_POST['csrf_token']??'')))back($id,'בקשה לא תקינה','error');
try{$a=(string)($_POST['action']??'');if($a==='toggle_freeze'){$s=$pdo->prepare('UPDATE users_profile SET Is_Frozen=CASE WHEN COALESCE(Is_Frozen,0)=1 THEN 0 ELSE 1 END WHERE Id=? LIMIT 1');$s->execute([$id]);back($id,'מצב החשבון עודכן');}if($a==='toggle_email_verified'){$s=$pdo->prepare('UPDATE users_profile SET email_verified=CASE WHEN COALESCE(email_verified,0)=1 THEN 0 ELSE 1 END WHERE Id=? LIMIT 1');$s->execute([$id]);back($id,'מצב האימייל עודכן');}throw new RuntimeException('פעולה לא מוכרת');}catch(Throwable $e){back($id,$e->getMessage(),'error');}
