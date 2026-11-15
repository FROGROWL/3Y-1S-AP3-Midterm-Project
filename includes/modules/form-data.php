<?php
$referenceTables = [
    'farmer' => ['id' => 'farmer_id', 'label' => "first_name || ' ' || last_name"],
    'farm' => ['id' => 'farm_id', 'label' => 'farm_name'],
    'crop' => ['id' => 'crop_id', 'label' => 'crop_name'],
    'crop_variety' => [
        'id' => 'variety.variety_id',
        'label' => "crop.crop_name || ' / ' || variety.variety_name",
        'from' => 'crop_variety variety JOIN crop ON crop.crop_id = variety.crop_id',
    ],
    'planting' => [
        'id' => 'planting.planting_id',
        'label' => "'Planting #' || planting.planting_id::text",
        'from' => 'planting',
        'order' => 'planting.planting_id ASC',
    ],
    'harvest_remark' => ['id' => 'remark_id', 'label' => 'code', 'order' => 'sort_order, code'],
];
$references = [];

foreach ($module['fields'] as [$key, $label, $type]) {
    if (str_starts_with($type, 'ref:')) {
        $reference = substr($type, 4);
        $referenceInfo = $referenceTables[$reference];
        $from = $referenceInfo['from'] ?? $reference;
        $order = $referenceInfo['order'] ?? 'label';
        $filter = '';
        if ($page === 'harvests' && $reference === 'planting') {
            $filter = "WHERE EXISTS (
                SELECT 1 FROM planting_status ps
                WHERE ps.status_id = planting.status_id AND lower(ps.code) = 'ready to harvest'
            )";
            if ($record && !empty($record['planting_id'])) {
                $filter .= ' OR planting.planting_id = ' . (int) $record['planting_id'];
            }
        }
        $references[$key] = db()->query(
            "SELECT {$referenceInfo['id']} AS id,
                    {$referenceInfo['label']} AS label
             FROM {$from}
             {$filter}
             ORDER BY {$order}"
        )->fetchAll();
    }

    if (str_starts_with($type, 'lookup:')) {
        $lookupName = substr($type, 7);
        $lookupInfo = $lookupTables[$lookupName];
        $lookupOrder = $lookupInfo['order'] ?? 'sort_order, code';
        $references[$key] = db()->query(
            "SELECT {$lookupInfo['id']} AS id, {$lookupInfo['label']} AS label
             FROM {$lookupName}
             ORDER BY {$lookupOrder}"
        )->fetchAll();
    }
}

$plantingDetails = [];
$farmDetails = [];
$varietyPeriods = [];

if ($page === 'plantings') {
    $farmQuery = db()->prepare(
        "SELECT f.farm_id, f.location, f.area_hectares,
                COALESCE(st.code, 'Not set') AS soil_type,
                COALESCE(it.code, 'Not set') AS irrigation_type,
                COALESCE(SUM(p.area_planted) FILTER (
                    WHERE lower(ps.code) IN ('planting', 'growing', 'ready to harvest')
                ), 0) AS used_area
         FROM farm f
         LEFT JOIN soil_type st ON st.soil_type_id = f.soil_type_id
         LEFT JOIN irrigation_type it ON it.irrigation_type_id = f.irrigation_type_id
         LEFT JOIN planting p ON p.farm_id = f.farm_id
              AND p.planting_id <> COALESCE(?, 0)
         LEFT JOIN planting_status ps ON ps.status_id = p.status_id
         GROUP BY f.farm_id, st.code, it.code"
    );
    $farmQuery->execute([$record['planting_id'] ?? 0]);
    foreach ($farmQuery->fetchAll() as $farmRow) {
        $farmDetails[(string) $farmRow['farm_id']] = $farmRow;
    }

    $varietyRows = db()->query(
        'SELECT variety.variety_id, variety.growing_period_days,
                COALESCE(season.code, \'Not specified\') AS growing_season
         FROM crop_variety variety
         LEFT JOIN growing_season season
           ON season.growing_season_id = variety.growing_season_id'
    )->fetchAll();
    foreach ($varietyRows as $varietyRow) {
        $varietyPeriods[(string) $varietyRow['variety_id']] = $varietyRow;
    }
}

if ($page === 'harvests') {
    $plantingRows = db()->query(
        "SELECT planting.planting_id,
                crop.crop_name || ' / ' || variety.variety_name AS crop,
                farm.farm_name AS farm,
                to_char(planting.planting_date, 'Mon DD, YYYY') AS planted,
                coalesce(
                    to_char(planting.expected_harvest_date, 'Mon DD, YYYY'),
                    'Not set'
                ) AS expected,
                planting_status.code AS status
         FROM planting
         JOIN farm ON farm.farm_id = planting.farm_id
         JOIN crop_variety variety ON variety.variety_id = planting.variety_id
         JOIN crop ON crop.crop_id = variety.crop_id
         JOIN planting_status
           ON planting_status.status_id = planting.status_id
         WHERE lower(planting_status.code) = 'ready to harvest'"
    )->fetchAll();

    if ($record && !empty($record['planting_id'])) {
        $detailQuery = db()->prepare(
            "SELECT planting.planting_id,
                    crop.crop_name || ' / ' || variety.variety_name AS crop,
                    farm.farm_name AS farm,
                    to_char(planting.planting_date, 'Mon DD, YYYY') AS planted,
                    COALESCE(to_char(planting.expected_harvest_date, 'Mon DD, YYYY'), 'Not set') AS expected,
                    planting_status.code AS status
             FROM planting JOIN farm ON farm.farm_id = planting.farm_id
             JOIN crop_variety variety ON variety.variety_id = planting.variety_id
             JOIN crop ON crop.crop_id = variety.crop_id
             JOIN planting_status ON planting_status.status_id = planting.status_id
             WHERE planting.planting_id = ?"
        );
        $detailQuery->execute([$record['planting_id']]);
        $currentDetail = $detailQuery->fetch();
        $alreadyListed = array_filter(
            $plantingRows,
            fn ($row) => (int) $row['planting_id'] === (int) $record['planting_id']
        );
        if ($currentDetail && !$alreadyListed) {
            $plantingRows[] = $currentDetail;
        }
    }

    foreach ($plantingRows as $plantingRow) {
        $plantingDetails[(string) $plantingRow['planting_id']] = $plantingRow;
    }
}

