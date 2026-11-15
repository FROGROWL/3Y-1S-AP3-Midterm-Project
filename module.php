<?php
$modules = require __DIR__ . '/modules.php';
$module = $modules[$page];
$table = $module['table'];
$primaryKey = $module['pk'];
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;

require_once __DIR__ . '/includes/modules/delete-support.php';
require __DIR__ . '/includes/module-actions.php';
require __DIR__ . '/includes/module-data.php';
require __DIR__ . '/views/module.php';
