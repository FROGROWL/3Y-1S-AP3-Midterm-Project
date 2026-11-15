<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'advance_planting') {
    check_csrf();
    $plantingId = filter_input(INPUT_POST, 'planting_id', FILTER_VALIDATE_INT);
    $advance = $pdo->prepare(
        "UPDATE planting p
         SET status_id = next_status.status_id, updated_at = now()
         FROM planting_status current_status, planting_status next_status
         WHERE p.planting_id = ?
           AND current_status.status_id = p.status_id
           AND next_status.code = CASE current_status.code
               WHEN 'planting' THEN 'growing'
               WHEN 'growing' THEN 'ready'
           END
           AND ((current_status.code = 'planting' AND p.planting_date < ?::date)
             OR (current_status.code = 'growing'
                 AND p.planting_date <= ?::date
                 AND p.expected_harvest_date <= ?::date + INTERVAL '20 days'))
         RETURNING p.planting_id"
    );
    $advance->execute([$plantingId, $todayDate, $todayDate, $todayDate]);
    $advancedId = $advance->fetchColumn();
    if ($advancedId) {
        $statusLookup = $pdo->prepare(
            'SELECT status.code FROM planting JOIN planting_status status USING (status_id)
             WHERE planting.planting_id = ?'
        );
        $statusLookup->execute([(int) $advancedId]);
        $afterStatus = $statusLookup->fetchColumn();
        audit('UPDATE', 'planting', (int) $advancedId, [
            'status' => [
                'before' => $afterStatus === 'growing' ? 'planting' : 'growing',
                'after' => $afterStatus,
            ],
        ]);
        flash('success', 'Planting #' . (int) $advancedId . ' status updated.');
    } else {
        flash('danger', 'This planting no longer meets the date rule for a status update.');
    }
    go('page=dashboard');
}

