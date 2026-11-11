<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
class Database { public function connect(): PDO { return db(); } }
