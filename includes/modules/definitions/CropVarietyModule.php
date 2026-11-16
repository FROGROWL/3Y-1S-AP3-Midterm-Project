<?php

namespace AgriInfo\Modules\Definitions;

final class CropVarietyModule
{
    public static function definition(): array
    {
        return [
            'title' => 'Crop varieties',
            'singular' => 'Crop variety',
            'table' => 'crop_variety',
            'pk' => 'variety_id',
            'fields' => [
                ['crop_id', 'Crop type', 'ref:crop', true],
                ['variety_name', 'Variety name', 'text', true],
                ['growing_period_days', 'Growing period (days)', 'number', false],
                ['growing_season_id', 'Recommended season', 'lookup:growing_season', false],
            ],
        ];
    }
}
