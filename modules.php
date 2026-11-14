<?php

return [
    'farmers' => [
        'title' => 'Farmers',
        'table' => 'farmer',
        'pk' => 'farmer_id',
        'search' => ['first_name', 'last_name', 'contact_number', 'address'],
        'fields' => [
            ['first_name', 'First name', 'text', true],
            ['last_name', 'Last name', 'text', true],
            ['contact_number', 'Contact number', 'text', false],
            ['address', 'Address', 'text', false],
            ['date_registered', 'Date registered', 'date', true],
        ],
    ],
    'farms' => [
        'title' => 'Farms',
        'table' => 'farm',
        'pk' => 'farm_id',
        'search' => ['farm_name', 'location'],
        'fields' => [
            ['farmer_id', 'Farmer', 'ref:farmer', true],
            ['farm_name', 'Farm name', 'text', true],
            ['location', 'Location', 'text', true],
            ['area_hectares', 'Area (hectares)', 'number', true],
            ['soil_type_id', 'Soil type', 'lookup:soil_type', false],
            ['irrigation_type_id', 'Irrigation type', 'lookup:irrigation_type', false],
        ],
    ],
    'crops' => [
        'title' => 'Crop types',
        'table' => 'crop',
        'pk' => 'crop_id',
        'search' => ['crop_name'],
        'fields' => [
            ['crop_name', 'Crop name', 'text', true],
            ['crop_category_id', 'Category', 'lookup:crop_category', false],
        ],
    ],
    'varieties' => [
        'title' => 'Crop varieties',
        'singular' => 'Crop variety',
        'table' => 'crop_variety',
        'pk' => 'variety_id',
        'search' => ['variety_name'],
        'fields' => [
            ['crop_id', 'Crop type', 'ref:crop', true],
            ['variety_name', 'Variety name', 'text', true],
            ['growing_period_days', 'Growing period (days)', 'number', false],
            ['growing_season_id', 'Recommended season', 'lookup:growing_season', false],
        ],
    ],
    'plantings' => [
        'title' => 'Plantings',
        'table' => 'planting',
        'pk' => 'planting_id',
        'search' => [],
        'fields' => [
            ['farm_id', 'Farm', 'ref:farm', true],
            ['variety_id', 'Crop variety', 'ref:crop_variety', true],
            ['planting_date', 'Planting date', 'date', true],
            ['area_planted', 'Area planted (ha)', 'number', true],
            ['seed_quantity', 'Seed quantity (kg)', 'number', false],
            ['expected_harvest_date', 'Expected harvest date (calculated)', 'computed-date', false],
            ['status_id', 'Status', 'lookup:planting_status', true],
        ],
    ],
    'harvests' => [
        'title' => 'Harvests',
        'table' => 'harvest',
        'pk' => 'harvest_id',
        'search' => [],
        'fields' => [
            ['planting_id', 'Planting ID', 'ref:planting', true],
            ['harvest_date', 'Harvest date', 'date', true],
            ['quantity_kg', 'Quantity (kg)', 'number', true],
            ['selling_price_per_kg', 'Selling price/kg', 'number', false],
            ['quality_grade_id', 'Quality grade', 'lookup:quality_grade', false],
            ['remark_id', 'Harvest remark', 'ref:harvest_remark', true],
        ],
    ],
];
