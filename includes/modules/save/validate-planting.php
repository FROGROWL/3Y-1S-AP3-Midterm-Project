<?php
    if ($page === 'plantings') {
        $plantingDate = $data['planting_date'] ?? null;
        $varietyId = $data['variety_id'] ?? null;
        $farmId = (int) ($data['farm_id'] ?? 0);
        $areaPlanted = (float) ($data['area_planted'] ?? 0);

        $varietyQuery = db()->prepare(
            'SELECT growing_period_days FROM crop_variety WHERE variety_id = ?'
        );
        $varietyQuery->execute([$varietyId]);
        $growingDays = $varietyQuery->fetchColumn();

        if (!$growingDays || !$plantingDate) {
            flash('danger', 'Choose a crop variety with a growing period and a planting date to calculate harvest date.');
            go('page=' . $page . ($id ? '&edit=' . $id : ''));
        }

        $harvestDate = (new DateTimeImmutable($plantingDate))
            ->modify('+' . (int) $growingDays . ' days')
            ->format('Y-m-d');
        $data['expected_harvest_date'] = $harvestDate;

        $statusQuery = db()->prepare(
            'SELECT code FROM planting_status WHERE status_id = ?'
        );
        $statusQuery->execute([$data['status_id'] ?? null]);
        $statusCode = $statusQuery->fetchColumn();
        $today = new DateTimeImmutable('today');
        $chosenDate = new DateTimeImmutable($plantingDate);
        $daysToHarvest = (int) $today->diff(new DateTimeImmutable($harvestDate))->format('%r%a');
        $allowedStatuses = $chosenDate > $today
            ? ['Planting', 'Cancelled']
            : ($daysToHarvest > 20
                ? ['Planting', 'Growing', 'Cancelled']
                : ['Ready to harvest', 'Harvested', 'Cancelled']);

        if (!in_array($statusCode, $allowedStatuses, true)) {
            flash('danger', 'The selected status does not match the planting and expected harvest dates.');
            go('page=' . $page . ($id ? '&edit=' . $id : ''));
        }

        $capacityQuery = db()->prepare(
            "SELECT f.area_hectares,
                    COALESCE(SUM(p.area_planted) FILTER (
                        WHERE lower(ps.code) IN ('planting', 'growing', 'ready to harvest')
                    ), 0) AS used_area
             FROM farm f
             LEFT JOIN planting p
               ON p.farm_id = f.farm_id AND p.planting_id <> COALESCE(?, 0)
             LEFT JOIN planting_status ps ON ps.status_id = p.status_id
             WHERE f.farm_id = ?
             GROUP BY f.farm_id"
        );
        $capacityQuery->execute([$id, $farmId]);
        $capacity = $capacityQuery->fetch();

        $capacityToReserve = in_array($statusCode, ['Planting', 'Growing', 'Ready to harvest'], true)
            ? $areaPlanted
            : 0;
        $availableArea = max(
            0,
            (float) ($capacity['area_hectares'] ?? 0) - (float) ($capacity['used_area'] ?? 0)
        );
        if (
            !$capacity
            || $areaPlanted > $availableArea
            || (float) $capacity['used_area'] + $capacityToReserve > (float) $capacity['area_hectares']
        ) {
            flash('danger', 'Area planted exceeds the remaining farm area. Available: ' . number_format($availableArea, 2) . ' ha.');
            go('page=' . $page . ($id ? '&edit=' . $id : ''));
        }
    }

