<?php
$stats = [];
$tables = [
    'farmers' => 'farmer',
    'farms' => 'farm',
    'harvests' => 'harvest',
];

foreach ($tables as $label => $table) {
    $stats[$label] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$stats['active plantings'] = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM planting p
     JOIN planting_status s ON s.status_id = p.status_id
     WHERE s.code IN ('planting', 'growing', 'ready')"
)->fetchColumn();
if ($isAdmin) {
    $stats['active users'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM app_user
         WHERE is_active
           AND signed_out_at IS NULL
           AND last_activity_at >= now() - INTERVAL '8 hours'"
    )->fetchColumn();
}
$totalPlantings = (int) $pdo->query('SELECT COUNT(*) FROM planting')->fetchColumn();

$totalHarvest = (float) $pdo
    ->query('SELECT COALESCE(SUM(quantity_kg), 0) FROM harvest')
    ->fetchColumn();

$statusSummary = $pdo->query(
    'SELECT s.code, s.label, COUNT(p.planting_id) AS record_count
     FROM planting_status s
     LEFT JOIN planting p ON p.status_id = s.status_id
     GROUP BY s.status_id, s.code, s.label, s.sort_order
     ORDER BY s.sort_order, s.label'
)->fetchAll();

