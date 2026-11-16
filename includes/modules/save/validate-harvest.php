<?php
    if ($page === 'harvests') {
        $plantingDateQuery = db()->prepare(
            'SELECT planting_date FROM planting WHERE planting_id = ?'
        );
        $plantingDateQuery->execute([$data['planting_id'] ?? null]);
        $plantingDateValue = $plantingDateQuery->fetchColumn();
        $harvestDateValue = $data['harvest_date'] ?? null;
        $plantingDate = $plantingDateValue
            ? new DateTimeImmutable((string) $plantingDateValue)
            : null;
        $harvestDate = $harvestDateValue
            ? new DateTimeImmutable((string) $harvestDateValue)
            : null;

        if (!$plantingDate || !$harvestDate || $harvestDate <= $plantingDate) {
            flash('danger', 'Harvest date must be after the selected planting date.');
            go('page=' . $page . ($id ? '&edit=' . $id : ''));
        }
    }

    if ($page === 'harvests' && !$id) {
        $readyCheck = db()->prepare(
            "SELECT 1 FROM planting p JOIN planting_status s ON s.status_id = p.status_id
             WHERE p.planting_id = ? AND lower(s.code) = 'ready to harvest'"
        );
        $readyCheck->execute([$data['planting_id'] ?? null]);

        if (!$readyCheck->fetchColumn()) {
            flash('danger', 'Harvests can only be added for plantings marked ready to harvest.');
            go('page=' . $page);
        }
    }

