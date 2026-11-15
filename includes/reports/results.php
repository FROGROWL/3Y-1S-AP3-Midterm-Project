<?php
$results = [];
$totalHarvests = 0;
$totalKg = 0.0;
$grossValue = 0.0;
$projectedKg = 0.0;
$projectedGross = 0.0;
$cancelledPlantings = 0;

if (!$dateError) {
    if ($scope === 'farms') {
        $farmSql = 'SELECT farm_id AS group_id, farm_name AS label FROM farm';
        if ($farmId !== null) {
            $farmSql .= ' WHERE farm_id = ?';
        }
        $farmSql .= ' ORDER BY farm_name';
        $farmStatement = $pdo->prepare($farmSql);
        $farmStatement->execute($farmId !== null ? [$farmId] : []);
        foreach ($farmStatement->fetchAll() as $farmRow) {
            $results[(string) $farmRow['group_id']] = [
                'group_id' => $farmRow['group_id'],
                'label' => $farmRow['label'],
                'harvest_count' => 0,
                'total_kg' => 0.0,
                'average_kg' => 0.0,
                'gross_value' => 0.0,
                'quality_score' => 0.0,
                'cancelled_count' => 0,
                'forecast_kg' => 0.0,
                'forecast_gross' => 0.0,
            ];
        }
    }

    $actual = $pdo->prepare(
        "SELECT {$dimension['id']} AS group_id, {$dimension['label']} AS label,
                COUNT(h.harvest_id) AS harvest_count,
                COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
                COALESCE(AVG(h.quantity_kg), 0) AS average_kg,
                COALESCE(SUM(h.quantity_kg * COALESCE(h.selling_price_per_kg, 0)), 0) AS gross_value,
                COALESCE(AVG(CASE grade.code
                    WHEN 'Grade A' THEN 3.0 WHEN 'Grade B' THEN 2.0
                    WHEN 'Grade C' THEN 1.0 ELSE NULL END), 0) AS quality_score
         FROM harvest h
         JOIN planting p ON p.planting_id = h.planting_id
         {$joins}
         LEFT JOIN quality_grade grade ON grade.quality_grade_id = h.quality_grade_id
         WHERE h.harvest_date BETWEEN ? AND ? {$actualFilterSql}
         GROUP BY {$dimension['group']}"
    );
    $actual->execute(array_merge([$from, $to], $actualFilterParams));
    foreach ($actual->fetchAll() as $row) {
        $key = (string) $row['group_id'];
        $results[$key] = array_merge([
            'group_id' => $row['group_id'],
            'label' => $row['label'],
            'harvest_count' => 0,
            'total_kg' => 0.0,
            'average_kg' => 0.0,
            'gross_value' => 0.0,
            'quality_score' => 0.0,
            'cancelled_count' => 0,
            'forecast_kg' => 0.0,
            'forecast_gross' => 0.0,
        ], $row);
    }

    $cancelled = $pdo->prepare(
        "SELECT {$dimension['id']} AS group_id, {$dimension['label']} AS label,
                COUNT(*) AS cancelled_count
         FROM planting p {$joins}
         JOIN planting_status status ON status.status_id = p.status_id
         WHERE lower(status.code) = 'cancelled'
           AND p.updated_at::date BETWEEN ? AND ? {$filterSql}
         GROUP BY {$dimension['group']}"
    );
    $cancelled->execute(array_merge([$from, $to], $filterParams));
    foreach ($cancelled->fetchAll() as $row) {
        $key = (string) $row['group_id'];
        $results[$key] ??= [
            'group_id' => $row['group_id'], 'label' => $row['label'],
            'harvest_count' => 0, 'total_kg' => 0.0, 'average_kg' => 0.0,
            'gross_value' => 0.0, 'quality_score' => 0.0,
            'cancelled_count' => 0, 'forecast_kg' => 0.0, 'forecast_gross' => 0.0,
        ];
        $results[$key]['cancelled_count'] = (int) $row['cancelled_count'];
    }

    $forecast = $pdo->prepare(
        "WITH planting_history AS (
            SELECT {$dimension['id']} AS group_id,
                   p.planting_id,
                   MAX(p.area_planted) AS area_planted,
                   SUM(h.quantity_kg) AS harvested_kg,
                   SUM(h.quantity_kg * COALESCE(h.selling_price_per_kg, 0)) AS gross_value
            FROM harvest h
            JOIN planting p ON p.planting_id = h.planting_id
            {$joins}
            LEFT JOIN quality_grade grade ON grade.quality_grade_id = h.quality_grade_id
            WHERE h.harvest_date BETWEEN ? AND ? {$actualFilterSql}
            GROUP BY {$dimension['id']}, p.planting_id
         ), history AS (
            SELECT group_id,
                   SUM(harvested_kg) / NULLIF(SUM(area_planted), 0) AS kg_per_hectare,
                   SUM(gross_value) / NULLIF(SUM(harvested_kg), 0) AS average_price
            FROM planting_history GROUP BY group_id
         ), expected AS (
            SELECT {$dimension['id']} AS group_id, {$dimension['label']} AS label,
                   SUM(p.area_planted) AS forecast_area
            FROM planting p {$joins}
            JOIN planting_status status ON status.status_id = p.status_id
            WHERE lower(status.code) IN ('planting', 'growing', 'ready to harvest')
              AND p.expected_harvest_date > CURRENT_DATE
              AND p.expected_harvest_date BETWEEN ? AND ? {$filterSql}
            GROUP BY {$dimension['group']}
         )
         SELECT expected.group_id, expected.label,
                expected.forecast_area * COALESCE(history.kg_per_hectare, 0) AS forecast_kg,
                expected.forecast_area * COALESCE(history.kg_per_hectare, 0)
                    * COALESCE(history.average_price, 0) AS forecast_gross
         FROM expected LEFT JOIN history USING (group_id)"
    );
    $forecast->execute(array_merge(
        [$from, $to], $actualFilterParams,
        [$from, $to], $filterParams
    ));
    foreach ($forecast->fetchAll() as $row) {
        $key = (string) $row['group_id'];
        $results[$key] ??= [
            'group_id' => $row['group_id'], 'label' => $row['label'],
            'harvest_count' => 0, 'total_kg' => 0.0, 'average_kg' => 0.0,
            'gross_value' => 0.0, 'quality_score' => 0.0,
            'cancelled_count' => 0, 'forecast_kg' => 0.0, 'forecast_gross' => 0.0,
        ];
        $results[$key]['forecast_kg'] = (float) $row['forecast_kg'];
        $results[$key]['forecast_gross'] = (float) $row['forecast_gross'];
    }

    foreach ($results as &$result) {
        foreach (['harvest_count', 'cancelled_count'] as $key) {
            $result[$key] = (int) ($result[$key] ?? 0);
        }
        foreach (['total_kg', 'average_kg', 'gross_value', 'quality_score', 'forecast_kg', 'forecast_gross'] as $key) {
            $result[$key] = (float) ($result[$key] ?? 0);
        }
        $totalHarvests += $result['harvest_count'];
        $totalKg += $result['total_kg'];
        $grossValue += $result['gross_value'];
        $projectedKg += $result['forecast_kg'];
        $projectedGross += $result['forecast_gross'];
        $cancelledPlantings += $result['cancelled_count'];
    }
    unset($result);
}
