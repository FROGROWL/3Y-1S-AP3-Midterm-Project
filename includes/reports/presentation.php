<?php
$rows = array_values($results);
usort($rows, static function (array $left, array $right) use ($orderBy, $direction): int {
    $comparison = $orderBy === 'label'
        ? strcasecmp((string) $left['label'], (string) $right['label'])
        : (($left[$orderBy] ?? 0) <=> ($right[$orderBy] ?? 0));
    return $direction === 'asc' ? $comparison : -$comparison;
});
$metricLabel = $metrics[$metric];
$maxValue = max(array_map(static fn (array $row): float => max(0, (float) ($row[$metric] ?? 0)), $rows) ?: [0]);
$dimensionLabel = $scope === 'farms' ? 'Farm performance' : ($groupBy === 'variety' ? 'Crop variety performance' : 'Crop performance');
