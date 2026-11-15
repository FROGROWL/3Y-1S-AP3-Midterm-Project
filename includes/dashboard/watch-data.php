<?php
$statusWatchPage = max(1, (int) ($_GET['status_watch_page'] ?? 1));
$statusWatchCondition = "((s.code = 'planting' AND p.planting_date < ?::date)
    OR (s.code = 'growing' AND p.planting_date <= ?::date
        AND p.expected_harvest_date <= ?::date + INTERVAL '20 days'))";
$statusWatchCount = $pdo->prepare(
    'SELECT COUNT(*) FROM planting p JOIN planting_status s ON s.status_id = p.status_id WHERE ' . $statusWatchCondition
);
$statusWatchCount->execute([$todayDate, $todayDate, $todayDate]);
$statusWatchTotal = (int) $statusWatchCount->fetchColumn();
$statusWatchPages = max(1, (int) ceil($statusWatchTotal / $watchPerPage));
$statusWatchPage = min($statusWatchPage, $statusWatchPages);
$statusWatch = $pdo->prepare(
    "SELECT p.planting_id, p.planting_date, p.expected_harvest_date, s.code, s.label AS status,
            c.crop_name, v.variety_name, f.farm_name
     FROM planting p
     JOIN planting_status s ON s.status_id = p.status_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     JOIN farm f ON f.farm_id = p.farm_id
     WHERE {$statusWatchCondition}
     ORDER BY CASE WHEN s.code = 'planting' THEN 0 ELSE 1 END,
              p.expected_harvest_date NULLS LAST, p.planting_id
     LIMIT {$watchPerPage} OFFSET " . (($statusWatchPage - 1) * $watchPerPage)
);
$statusWatch->execute([$todayDate, $todayDate, $todayDate]);
$statusWatch = $statusWatch->fetchAll();

$harvestWatchPage = max(1, (int) ($_GET['harvest_watch_page'] ?? 1));
$harvestWatchCount = $pdo->prepare(
    "SELECT COUNT(*) FROM planting p JOIN planting_status s ON s.status_id = p.status_id
     WHERE p.expected_harvest_date <= ?::date + INTERVAL '20 days' AND s.code = 'ready'"
);
$harvestWatchCount->execute([$todayDate]);
$harvestWatchTotal = (int) $harvestWatchCount->fetchColumn();
$harvestWatchPages = max(1, (int) ceil($harvestWatchTotal / $watchPerPage));
$harvestWatchPage = min($harvestWatchPage, $harvestWatchPages);
$harvestWatch = $pdo->prepare(
    "SELECT p.planting_id, p.expected_harvest_date, s.label AS status,
            c.crop_name, v.variety_name, f.farm_name
     FROM planting p
     JOIN planting_status s ON s.status_id = p.status_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     JOIN farm f ON f.farm_id = p.farm_id
     WHERE p.expected_harvest_date <= ?::date + INTERVAL '20 days'
       AND s.code = 'ready'
     ORDER BY p.expected_harvest_date, p.planting_id
     LIMIT {$watchPerPage} OFFSET " . (($harvestWatchPage - 1) * $watchPerPage)
);
$harvestWatch->execute([$todayDate]);
$harvestWatch = $harvestWatch->fetchAll();

