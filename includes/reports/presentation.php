<?php
$rows = array_values($results);
usort($rows, static function (array $left, array $right) use ($orderBy, $direction): int {
    $comparison = $orderBy === 'label'
        ? strcasecmp((string) $left['label'], (string) $right['label'])
        : (($left[$orderBy] ?? 0) <=> ($right[$orderBy] ?? 0));
    return $direction === 'asc' ? $comparison : -$comparison;
});
$tablePageSize = 10;
$tableTotal = count($rows);
$tableTotalPages = max(1, (int) ceil($tableTotal / $tablePageSize));
$tablePage = filter_var($_GET['table_page'] ?? 1, FILTER_VALIDATE_INT);
$tablePage = $tablePage !== false && $tablePage > 0 ? $tablePage : 1;
$tablePage = min($tablePage, $tableTotalPages);
$tableRows = array_slice($rows, ($tablePage - 1) * $tablePageSize, $tablePageSize);
$tableQuery = [
    'page' => 'reports',
    'from' => $from,
    'to' => $to,
    'scope' => $scope,
    'group_by' => $groupBy,
    'metric' => $metric,
    'farm_id' => $farmId,
    'crop_id' => $cropId,
    'variety_id' => $varietyId,
    'quality_grade_id' => $gradeId,
    'order_by' => $orderBy,
    'direction' => $direction,
];
$previousTableUrl = url(http_build_query($tableQuery + [
    'table_page' => max(1, $tablePage - 1),
]));
$nextTableUrl = url(http_build_query($tableQuery + [
    'table_page' => min($tableTotalPages, $tablePage + 1),
]));
$metricLabel = $metrics[$metric];
$maxValue = max(array_map(static fn (array $row): float => max(0, (float) ($row[$metric] ?? 0)), $rows) ?: [0]);
$dimensionLabel = $scope === 'farms' ? 'Farm performance' : ($groupBy === 'variety' ? 'Crop variety performance' : 'Crop performance');
