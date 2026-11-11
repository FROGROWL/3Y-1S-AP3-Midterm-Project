<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if(PHP_SAPI!=='cli') { http_response_code(403); exit("Run installation from the command line.\n"); }
try {
 $pdo=db();
 foreach(['schema.sql','lookup_data.sql','sample_data.sql'] as $file) { $sql=file_get_contents(__DIR__.'/sql/'.$file); if($sql===false) throw new RuntimeException("Missing SQL file: $file"); $pdo->exec($sql); echo "Applied $file\n"; }
$email=envv('INSTALL_ADMIN_EMAIL'); $password=envv('INSTALL_ADMIN_PASSWORD'); $name=envv('INSTALL_ADMIN_NAME','System Administrator');
if($email && $password) { if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12) throw new RuntimeException('Set a valid INSTALL_ADMIN_EMAIL and an INSTALL_ADMIN_PASSWORD of at least 12 characters.'); $s=$pdo->prepare('INSERT INTO app_user(full_name,email,password_hash,role) VALUES(?,?,?,?) ON CONFLICT(email) DO NOTHING'); $s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),'Administrator']); echo "Administrator account ensured for $email\n"; } else echo "No admin created: set INSTALL_ADMIN_EMAIL and INSTALL_ADMIN_PASSWORD in .env, then rerun to create the first account.\n";
echo "Database installation completed.\n";
} catch(Throwable $e) { fwrite(STDERR,"Installation failed: ".$e->getMessage()."\n"); exit(1); }
