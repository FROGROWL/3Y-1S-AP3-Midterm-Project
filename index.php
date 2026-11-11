<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$page=$_GET['page']??'dashboard';
try {
 if($page==='login' && $_SERVER['REQUEST_METHOD']==='POST') { check_csrf(); $s=db()->prepare('SELECT user_id,full_name,email,password_hash,role,is_active FROM app_user WHERE lower(email)=lower(?)'); $s->execute([trim($_POST['email']??'')]); $u=$s->fetch(); if($u && $u['is_active'] && password_verify((string)($_POST['password']??''),$u['password_hash'])) { session_regenerate_id(true); unset($u['password_hash']); $_SESSION['user']=$u; flash('success','Welcome back, '.$u['full_name'].'.'); go('page=dashboard'); } flash('danger','Email or password is incorrect.'); go('page=login'); }
 if($page==='logout') { if($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit('Use the sign-out form.'); } check_csrf(); session_destroy(); go('page=login'); }
 if($page==='login') { require __DIR__.'/views/login.php'; exit; }
 require_login();
 if($page==='dashboard') require __DIR__.'/views/dashboard.php';
 elseif($page==='reports') require __DIR__.'/views/reports.php';
 elseif($page==='users') require __DIR__.'/views/users.php';
 elseif(array_key_exists($page, require __DIR__.'/modules.php')) { require __DIR__.'/module.php'; }
 else { http_response_code(404); echo 'Page not found'; }
} catch(Throwable $ex) { error_log((string)$ex); http_response_code(500); $message=envv('APP_ENV','production')==='development' ? $ex->getMessage() : 'The request could not be completed. Check the application log.'; require __DIR__.'/views/error.php'; }
