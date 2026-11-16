<?php

namespace AgriInfo\Modules\Definitions;

final class FarmerModule
{
    public static function definition(): array
    {
        return [
            'title' => 'Farmers',
            'table' => 'farmer',
            'pk' => 'farmer_id',
            'fields' => [
                ['first_name', 'First name', 'text', true],
                ['last_name', 'Last name', 'text', true],
                ['contact_number', 'Contact number', 'text', false],
                ['address', 'Address', 'text', false],
                ['date_registered', 'Date registered', 'date', true],
            ],
        ];
    }
}
