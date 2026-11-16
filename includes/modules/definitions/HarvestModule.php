<?php

namespace AgriInfo\Modules\Definitions;

final class HarvestModule
{
    public static function definition(): array
    {
        return [
            'title' => 'Harvests',
            'table' => 'harvest',
            'pk' => 'harvest_id',
            'fields' => [
                ['planting_id', 'Planting ID', 'ref:planting', true],
                ['harvest_date', 'Harvest date', 'date', true],
                ['quantity_kg', 'Quantity (kg)', 'number', true],
                ['selling_price_per_kg', 'Selling price/kg', 'number', false],
                ['quality_grade_id', 'Quality grade', 'lookup:quality_grade', false],
                ['remark_id', 'Harvest remark', 'ref:harvest_remark', true],
            ],
        ];
    }
}
