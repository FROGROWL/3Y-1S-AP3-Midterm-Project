<?php

function moduleDeleteSnapshot(string $table, int $id): ?array
{
    $queries = [
        'farmer' => 'SELECT f.* FROM farmer f WHERE f.farmer_id = ?',
        'farm' => "SELECT f.*, farmer.first_name || ' ' || farmer.last_name AS farmer_name, soil.code AS soil_type, irrigation.code AS irrigation_type FROM farm f JOIN farmer ON farmer.farmer_id = f.farmer_id LEFT JOIN soil_type soil ON soil.soil_type_id = f.soil_type_id LEFT JOIN irrigation_type irrigation ON irrigation.irrigation_type_id = f.irrigation_type_id WHERE f.farm_id = ?",
        'crop' => 'SELECT crop.*, category.code AS crop_category FROM crop LEFT JOIN crop_category category ON category.crop_category_id = crop.crop_category_id WHERE crop.crop_id = ?',
        'crop_variety' => "SELECT v.*, c.crop_name, s.code AS growing_season FROM crop_variety v JOIN crop c ON c.crop_id = v.crop_id LEFT JOIN growing_season s ON s.growing_season_id = v.growing_season_id WHERE v.variety_id = ?",
        'planting' => "SELECT p.*, f.farm_name, f.location, c.crop_name, v.variety_name, s.code AS status FROM planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop_variety v ON v.variety_id = p.variety_id JOIN crop c ON c.crop_id = v.crop_id JOIN planting_status s ON s.status_id = p.status_id WHERE p.planting_id = ?",
        'harvest' => "SELECT h.*, p.planting_date, f.farm_name, c.crop_name, v.variety_name, q.code AS quality_grade, r.code AS harvest_remark FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm f ON f.farm_id = p.farm_id JOIN crop_variety v ON v.variety_id = p.variety_id JOIN crop c ON c.crop_id = v.crop_id LEFT JOIN quality_grade q ON q.quality_grade_id = h.quality_grade_id JOIN harvest_remark r ON r.remark_id = h.remark_id WHERE h.harvest_id = ?",
    ];
    if (!isset($queries[$table])) return null;
    $statement = db()->prepare($queries[$table]);
    $statement->execute([$id]);
    return $statement->fetch() ?: null;
}

function moduleDeleteImpact(string $table, int $id): array
{
    $counts = [];
    $count = static function (string $name, string $sql) use (&$counts, $id): void {
        $statement = db()->prepare($sql);
        $statement->execute([$id]);
        $counts[$name] = (int) $statement->fetchColumn();
    };
    if ($table === 'farmer') {
        $count('farms', 'SELECT COUNT(*) FROM farm WHERE farmer_id = ?');
        $count('plantings', 'SELECT COUNT(*) FROM planting p JOIN farm f ON f.farm_id = p.farm_id WHERE f.farmer_id = ?');
        $count('harvests', 'SELECT COUNT(*) FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN farm f ON f.farm_id = p.farm_id WHERE f.farmer_id = ?');
    } elseif ($table === 'farm') {
        $count('plantings', 'SELECT COUNT(*) FROM planting WHERE farm_id = ?');
        $count('harvests', 'SELECT COUNT(*) FROM harvest h JOIN planting p ON p.planting_id = h.planting_id WHERE p.farm_id = ?');
    } elseif ($table === 'crop') {
        $count('varieties', 'SELECT COUNT(*) FROM crop_variety WHERE crop_id = ?');
        $count('plantings', 'SELECT COUNT(*) FROM planting p JOIN crop_variety v ON v.variety_id = p.variety_id WHERE v.crop_id = ?');
        $count('harvests', 'SELECT COUNT(*) FROM harvest h JOIN planting p ON p.planting_id = h.planting_id JOIN crop_variety v ON v.variety_id = p.variety_id WHERE v.crop_id = ?');
    } elseif ($table === 'crop_variety') {
        $count('plantings', 'SELECT COUNT(*) FROM planting WHERE variety_id = ?');
        $count('harvests', 'SELECT COUNT(*) FROM harvest h JOIN planting p ON p.planting_id = h.planting_id WHERE p.variety_id = ?');
    } elseif ($table === 'planting') {
        $count('harvests', 'SELECT COUNT(*) FROM harvest WHERE planting_id = ?');
    }
    return $counts;
}

function moduleDeleteBlocked(string $table, array $impact): bool
{
    return ($table === 'crop' || $table === 'crop_variety')
        && isset($impact['plantings'])
        && $impact['plantings'] > 0;
}
