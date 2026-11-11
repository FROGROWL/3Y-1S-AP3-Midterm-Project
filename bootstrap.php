<?php
declare(strict_types=1);
ini_set('session.use_strict_mode','1');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','samesite'=>'Lax','path'=>'/']);
session_start();
function envv(string $name, ?string $fallback = null): ?string { static $loaded=false; if (!$loaded) { $path=__DIR__.'/.env'; if (is_file($path)) { foreach(file($path, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) { $line=trim($line); if ($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue; [$k,$v]=explode('=',$line,2); $_ENV[trim($k)]=trim(trim($v)," \"'"); } } $loaded=true; } return $_ENV[$name] ?? $fallback; }
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function db(): PDO { static $pdo=null; if ($pdo) return $pdo; $required=['DB_HOST','DB_NAME','DB_USERNAME','DB_PASSWORD']; foreach($required as $key) if (envv($key)===null || envv($key)==='') throw new RuntimeException('Database configuration is incomplete. Copy .env.example to .env and enter the Supabase connection values.'); $dsn='pgsql:host='.envv('DB_HOST').';port='.envv('DB_PORT','5432').';dbname='.envv('DB_NAME').';sslmode='.envv('DB_SSLMODE','require'); return $pdo=new PDO($dsn,envv('DB_USERNAME'),envv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); }
function url(string $q=''): string { return 'index.php'.($q ? '?'.$q : ''); }
function go(string $q): never { header('Location: '.url($q)); exit; }
function flash(string $kind,string $msg): void { $_SESSION['flash']=[$kind,$msg]; }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void { if(!hash_equals(csrf(),(string)($_POST['csrf']??''))) { http_response_code(419); exit('Session expired. Please go back and try again.'); } }
function user(): ?array { return $_SESSION['user']??null; }
function require_login(): void { if(!user()) go('page=login'); }
function require_admin(): void { require_login(); if(user()['role']!=='Administrator') { http_response_code(403); exit('Administrator access required.'); } }
function audit(string $action,string $entity,?int $id=null): void { $s=db()->prepare('INSERT INTO audit_log(user_id,action,entity,entity_id) VALUES(?,?,?,?)'); $s->execute([user()['user_id']??null,$action,$entity,$id]); }
