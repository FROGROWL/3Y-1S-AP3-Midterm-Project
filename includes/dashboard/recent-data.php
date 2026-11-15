<?php
$recentHarvestPage = max(1, (int) ($_GET['recent_harvest_page'] ?? 1));
$sixMonthsAgo = $manilaToday->modify('-6 months')->format('Y-m-d');
$recentHarvestCount = $pdo->prepare('SELECT COUNT(*) FROM harvest WHERE harvest_date BETWEEN ? AND ?');
$recentHarvestCount->execute([$sixMonthsAgo, $todayDate]);
$recentHarvestTotal = (int) $recentHarvestCount->fetchColumn();
$recentHarvestPages = max(1, (int) ceil($recentHarvestTotal / $watchPerPage));
$recentHarvestPage = min($recentHarvestPage, $recentHarvestPages);
$recentHarvests = $pdo->prepare(
    'SELECT h.harvest_date, h.quantity_kg,
            h.harvest_id,
            c.crop_name || \' / \' || v.variety_name AS crop_display,
            f.farm_name
     FROM harvest h
     JOIN planting p ON p.planting_id = h.planting_id
     JOIN crop_variety v ON v.variety_id = p.variety_id
     JOIN crop c ON c.crop_id = v.crop_id
     JOIN farm f ON f.farm_id = p.farm_id
     WHERE h.harvest_date BETWEEN ? AND ?
     ORDER BY h.harvest_date DESC, h.harvest_id DESC
     LIMIT ' . $watchPerPage . ' OFFSET ' . (($recentHarvestPage - 1) * $watchPerPage)
);
$recentHarvests->execute([$sixMonthsAgo, $todayDate]);
$recentHarvests = $recentHarvests->fetchAll();
$todayAuditPage = max(1, (int) ($_GET['today_audit_page'] ?? 1));
$dashboardPageState = [
    'page' => 'dashboard',
    'status_watch_page' => $statusWatchPage,
    'harvest_watch_page' => $harvestWatchPage,
    'recent_harvest_page' => $recentHarvestPage,
    'today_audit_page' => $todayAuditPage,
];
$renderDashboardPagination = static function (string $parameter, int $currentPage, int $totalPages) use (&$dashboardPageState): string {
    if ($totalPages <= 1) {
        return '';
    }
    $previous = $dashboardPageState;
    $previous[$parameter] = max(1, $currentPage - 1);
    $next = $dashboardPageState;
    $next[$parameter] = min($totalPages, $currentPage + 1);
    return '<nav class="pagination is-small is-centered mt-3" aria-label="Dashboard list pages">'
        . '<a class="pagination-previous" href="' . e(url(http_build_query($previous))) . '"'
        . ($currentPage <= 1 ? ' disabled' : '') . '>Previous</a>'
        . '<a class="pagination-next" href="' . e(url(http_build_query($next))) . '"'
        . ($currentPage >= $totalPages ? ' disabled' : '') . '>Next</a>'
        . '<span class="pagination-list">Page ' . $currentPage . ' of ' . $totalPages . '</span>'
        . '</nav>';
};

