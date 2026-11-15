<?php
$record = null;

if ($editId) {
    $statement = db()->prepare(
        "SELECT * FROM \"{$table}\" WHERE \"{$primaryKey}\" = ?"
    );
    $statement->execute([$editId]);
    $record = $statement->fetch();
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

