<?php

namespace AgriInfo\Modules\Definitions;

final class PlantingModule
{
    public static function definition(): array
    {
        return [
            'title' => 'Plantings',
            'table' => 'planting',
            'pk' => 'planting_id',
            'fields' => [
                ['farm_id', 'Farm', 'ref:farm', true],
                ['variety_id', 'Crop variety', 'ref:crop_variety', true],
                ['planting_date', 'Planting date', 'date', true],
                ['area_planted', 'Area planted (ha)', 'number', true],
                ['seed_quantity', 'Seed quantity (kg)', 'number', false],
                ['expected_harvest_date', 'Expected harvest date (calculated)', 'computed-date', false],
                ['status_id', 'Status', 'lookup:planting_status', true],
            ],
        ];
    }
}
