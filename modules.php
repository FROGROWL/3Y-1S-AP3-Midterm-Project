<?php

use AgriInfo\Modules\Definitions\CropModule;
use AgriInfo\Modules\Definitions\CropVarietyModule;
use AgriInfo\Modules\Definitions\FarmModule;
use AgriInfo\Modules\Definitions\FarmerModule;
use AgriInfo\Modules\Definitions\HarvestModule;
use AgriInfo\Modules\Definitions\PlantingModule;

require_once __DIR__ . '/includes/modules/definitions/FarmerModule.php';
require_once __DIR__ . '/includes/modules/definitions/FarmModule.php';
require_once __DIR__ . '/includes/modules/definitions/CropModule.php';
require_once __DIR__ . '/includes/modules/definitions/CropVarietyModule.php';
require_once __DIR__ . '/includes/modules/definitions/PlantingModule.php';
require_once __DIR__ . '/includes/modules/definitions/HarvestModule.php';

return [
    'farmers' => FarmerModule::definition(),
    'farms' => FarmModule::definition(),
    'crops' => CropModule::definition(),
    'varieties' => CropVarietyModule::definition(),
    'plantings' => PlantingModule::definition(),
    'harvests' => HarvestModule::definition(),
];
