<?php

namespace AgriInfo\Modules\Definitions;

final class CropModule
{
    public static function definition(): array
    {
        return [
            'title' => 'Crop types',
            'table' => 'crop',
            'pk' => 'crop_id',
            'fields' => [
                ['crop_name', 'Crop name', 'text', true],
                ['crop_category_id', 'Category', 'lookup:crop_category', false],
            ],
        ];
    }
}
