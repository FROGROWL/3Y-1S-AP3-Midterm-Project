<?php
$filterFields = [
    $primaryKey => ['label' => 'ID', 'type' => 'integer'],
];
foreach ($module['fields'] as [$fieldKey, $fieldLabel, $fieldType]) {
    $filterFields[$fieldKey] = [
        'label' => $fieldLabel,
        'type' => in_array($fieldType, ['text', 'email'], true)
            ? 'text'
            : (str_starts_with($fieldType, 'ref:') || str_starts_with($fieldType, 'lookup:')
                ? 'related'
                : ($fieldType === 'date' || $fieldType === 'computed-date' ? 'date' : 'number')),
        'source' => $fieldType,
    ];
}
$lookupTables = [
    'soil_type' => ['id' => 'soil_type_id', 'label' => 'code'],
    'irrigation_type' => ['id' => 'irrigation_type_id', 'label' => 'code'],
    'crop_category' => ['id' => 'crop_category_id', 'label' => 'code'],
    'growing_season' => ['id' => 'growing_season_id', 'label' => 'code'],
    'quality_grade' => ['id' => 'quality_grade_id', 'label' => 'code'],
    'planting_status' => ['id' => 'status_id', 'label' => 'code'],
];
$filters = [];
foreach ($filterFields as $fieldKey => $fieldInfo) {
    $value = trim((string) ($_GET['filter'][$fieldKey] ?? ''));
    if ($value !== '') {
        $filters[$fieldKey] = $value;
    }
}
$sort = $_GET['sort'] ?? $primaryKey;
$allowedSort = array_merge(
    [$primaryKey],
    array_map(fn ($field) => $field[0], $module['fields'])
);

if (!in_array($sort, $allowedSort, true)) {
    $sort = $primaryKey;
}

$direction = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$conditions = [];
$params = [];

foreach ($filters as $fieldKey => $value) {
    $info = $filterFields[$fieldKey];
    $kind = $info['type'];
    if ($kind === 'text') {
        $conditions[] = 'CAST(m."' . $fieldKey . '" AS TEXT) ILIKE ?';
        $params[] = '%' . $value . '%';
        continue;
    }
    if ($kind === 'date') {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $value) {
            $conditions[] = 'FALSE';
        } else {
            $conditions[] = 'm."' . $fieldKey . '" = ?';
            $params[] = $value;
        }
        continue;
    }
    if ($kind === 'number' || $kind === 'integer') {
        if (!is_numeric($value)) {
            $conditions[] = 'FALSE';
        } else {
            $conditions[] = 'm."' . $fieldKey . '" = ?';
            $params[] = $value;
        }
        continue;
    }

    $source = $info['source'];
    if (str_starts_with($source, 'lookup:')) {
        $conditions[] = 'm."' . $fieldKey . '" = ?';
        $params[] = $value;
        continue;
    }

    $reference = substr($source, 4);
    $referenceConfig = [
        'farmer' => ['from' => 'farmer f', 'id' => 'f.farmer_id', 'text' => "f.first_name || ' ' || f.last_name"],
        'farm' => ['from' => 'farm f', 'id' => 'f.farm_id', 'text' => 'f.farm_name || \' \' || f.location'],
        'crop' => ['from' => 'crop f', 'id' => 'f.crop_id', 'text' => 'f.crop_name'],
        'crop_variety' => ['from' => 'crop_variety f JOIN crop c ON c.crop_id = f.crop_id', 'id' => 'f.variety_id', 'text' => "c.crop_name || ' / ' || f.variety_name"],
        'harvest_remark' => ['from' => 'harvest_remark f', 'id' => 'f.remark_id', 'text' => 'f.code'],
        'planting' => ['from' => 'planting f JOIN farm af ON af.farm_id = f.farm_id JOIN crop_variety av ON av.variety_id = f.variety_id JOIN crop ac ON ac.crop_id = av.crop_id JOIN planting_status aps ON aps.status_id = f.status_id', 'id' => 'f.planting_id', 'text' => "af.farm_name || ' ' || ac.crop_name || ' ' || av.variety_name || ' ' || aps.code || ' ' || CAST(f.planting_date AS TEXT)"],
    ];
    $refInfo = $referenceConfig[$reference];
    $conditions[] = 'EXISTS (SELECT 1 FROM ' . $refInfo['from'] . ' WHERE '
        . $refInfo['id'] . ' = m."' . $fieldKey . '" AND (CAST('
        . $refInfo['id'] . ' AS TEXT) = ? OR ' . $refInfo['text'] . ' ILIKE ?))';
    $params[] = $value;
    $params[] = '%' . $value . '%';
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countStatement = db()->prepare("SELECT COUNT(*) FROM \"{$table}\" m {$where}");
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$perPage = 10;
$totalPages = max(1, (int) ceil($total / $perPage));
$currentPage = max(1, min($totalPages, (int) ($_GET['p'] ?? 1)));
$offset = ($currentPage - 1) * $perPage;

$listStatement = db()->prepare(
    "SELECT m.* FROM \"{$table}\" m {$where}
     ORDER BY m.\"{$sort}\" {$direction}
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStatement->execute($params);
$rows = $listStatement->fetchAll();
$deleteImpacts = [];
foreach ($rows as $row) {
    $deleteImpacts[(string) $row[$primaryKey]] = moduleDeleteImpact($table, (int) $row[$primaryKey]);
}

