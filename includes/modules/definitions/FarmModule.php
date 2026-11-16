<?php

namespace AgriInfo\Modules\Definitions;

final class FarmModule
{
    public static function definition(): array
    {
        return [
            'title' => 'Farms',
            'table' => 'farm',
            'pk' => 'farm_id',
            'fields' => [
                ['farmer_id', 'Farmer', 'ref:farmer', true],
                ['farm_name', 'Farm name', 'text', true],
                ['location', 'Location', 'text', true],
                ['area_hectares', 'Area (hectares)', 'number', true],
                ['soil_type_id', 'Soil type', 'lookup:soil_type', false],
                ['irrigation_type_id', 'Irrigation type', 'lookup:irrigation_type', false],
            ],
        ];
    }
}
