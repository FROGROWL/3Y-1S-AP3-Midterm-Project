<?php

require_admin();
$layoutTitle = 'Business intelligence reports';
$pdo = db();
$todayDate = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
$today = $todayDate->format('Y-m-d');
$defaultTo = $todayDate->modify('+90 days')->format('Y-m-d');
$from = (string) ($_GET['from'] ?? substr($today, 0, 4) . '-01-01');
$to = (string) ($_GET['to'] ?? $defaultTo);
$validDate = static function (string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};
$dateError = !$validDate($from) || !$validDate($to) || $from > $to;
$scope = in_array($_GET['scope'] ?? 'farms', ['farms', 'crops'], true)
    ? (string) ($_GET['scope'] ?? 'farms')
    : 'farms';
$groupBy = ($scope === 'crops' && ($_GET['group_by'] ?? '') === 'variety')
    ? 'variety'
    : 'crop';
$metrics = [
    'gross_value' => 'Recorded gross value',
    'forecast_gross' => 'Projected gross value',
    'total_kg' => 'Harvested quantity (kg)',
    'forecast_kg' => 'Projected quantity (kg)',
    'average_kg' => 'Average kg per harvest',
    'harvest_count' => 'Harvest count',
    'quality_score' => 'Average quality score (A=3)',
    'cancelled_count' => 'Cancelled plantings',
];
$metric = isset($metrics[$_GET['metric'] ?? '']) ? (string) $_GET['metric'] : 'gross_value';
$requestedOrder = (string) ($_GET['order_by'] ?? $metric);
$orderBy = $requestedOrder === 'label'
    ? 'label'
    : (isset($metrics[$requestedOrder]) ? $requestedOrder : $metric);
$direction = strtolower((string) ($_GET['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
$farmId = filter_var($_GET['farm_id'] ?? '', FILTER_VALIDATE_INT);
$cropId = filter_var($_GET['crop_id'] ?? '', FILTER_VALIDATE_INT);
$varietyId = filter_var($_GET['variety_id'] ?? '', FILTER_VALIDATE_INT);
$gradeId = filter_var($_GET['quality_grade_id'] ?? '', FILTER_VALIDATE_INT);
$farmId = $farmId && $farmId > 0 ? $farmId : null;
$cropId = $cropId && $cropId > 0 ? $cropId : null;
$varietyId = $varietyId && $varietyId > 0 ? $varietyId : null;
$gradeId = $gradeId && $gradeId > 0 ? $gradeId : null;
$farms = $pdo->query('SELECT farm_id, farm_name FROM farm ORDER BY farm_name')->fetchAll();
$crops = $pdo->query('SELECT crop_id, crop_name FROM crop ORDER BY crop_name')->fetchAll();
$varieties = $pdo->query(
    'SELECT variety.variety_id, variety.variety_name, crop.crop_name
     FROM crop_variety variety JOIN crop ON crop.crop_id = variety.crop_id
     ORDER BY crop.crop_name, variety.variety_name'
)->fetchAll();
$grades = $pdo->query(
    'SELECT quality_grade_id, label FROM quality_grade ORDER BY sort_order, label'
)->fetchAll();

$dimensions = [
    'farms' => [
        'id' => 'f.farm_id',
        'label' => 'f.farm_name',
        'group' => 'f.farm_id, f.farm_name',
    ],
    'crops' => [
        'id' => $groupBy === 'variety' ? 'v.variety_id' : 'c.crop_id',
        'label' => $groupBy === 'variety'
            ? "c.crop_name || ' / ' || v.variety_name"
            : 'c.crop_name',
        'group' => $groupBy === 'variety'
            ? 'v.variety_id, c.crop_name, v.variety_name'
            : 'c.crop_id, c.crop_name',
    ],
];
$dimension = $dimensions[$scope];
$joins = 'JOIN farm f ON f.farm_id = p.farm_id
          JOIN crop_variety v ON v.variety_id = p.variety_id
          JOIN crop c ON c.crop_id = v.crop_id';
$filterSql = '';
$filterParams = [];
foreach ([
    ['f.farm_id', $farmId],
    ['c.crop_id', $cropId],
    ['v.variety_id', $varietyId],
] as [$column, $value]) {
    if ($value !== null) {
        $filterSql .= ' AND ' . $column . ' = ?';
        $filterParams[] = $value;
    }
}
$actualFilterSql = $filterSql;
$actualFilterParams = $filterParams;
if ($gradeId !== null) {
    $actualFilterSql .= ' AND h.quality_grade_id = ?';
    $actualFilterParams[] = $gradeId;
}
