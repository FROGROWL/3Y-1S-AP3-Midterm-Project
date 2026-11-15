<?php
    if ($page === 'harvests' && !$id) {
        $readyCheck = db()->prepare(
            "SELECT 1 FROM planting p JOIN planting_status s ON s.status_id = p.status_id
             WHERE p.planting_id = ? AND s.code = 'ready'"
        );
        $readyCheck->execute([$data['planting_id'] ?? null]);

        if (!$readyCheck->fetchColumn()) {
            flash('danger', 'Harvests can only be added for plantings marked ready to harvest.');
            go('page=' . $page);
        }
    }

