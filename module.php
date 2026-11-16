<?php
$modules = require __DIR__ . '/modules.php';
$module = $modules[$page];
$table = $module['table'];
$primaryKey = $module['pk'];
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;

require_once __DIR__ . '/includes/entities/Farmer.php';
require_once __DIR__ . '/includes/entities/Farm.php';
require_once __DIR__ . '/includes/entities/Crop.php';
require_once __DIR__ . '/includes/entities/CropVariety.php';
require_once __DIR__ . '/includes/entities/Planting.php';
require_once __DIR__ . '/includes/entities/Harvest.php';

$entityClass = match ($page) {
    'farmers' => \AgriInfo\Entities\Farmer::class,
    'farms' => \AgriInfo\Entities\Farm::class,
    'crops' => \AgriInfo\Entities\Crop::class,
    'varieties' => \AgriInfo\Entities\CropVariety::class,
    'plantings' => \AgriInfo\Entities\Planting::class,
    'harvests' => \AgriInfo\Entities\Harvest::class,
};
$entity = new $entityClass(db());

require_once __DIR__ . '/includes/modules/delete-support.php';
require __DIR__ . '/includes/module-actions.php';
require __DIR__ . '/includes/module-data.php';
require __DIR__ . '/views/module.php';
