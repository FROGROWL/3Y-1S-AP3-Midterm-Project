<?php
$record = null;

if ($editId) {
    $record = $entity->getById((int) $editId);
}
$moduleName = match ($page) {
    'farmers' => 'Farmer',
    'farms' => 'Farm',
    'crops' => 'Crop',
    'varieties' => 'Crop variety',
    'plantings' => 'Planting',
    'harvests' => 'Harvest',
    default => $module['title'],
};

