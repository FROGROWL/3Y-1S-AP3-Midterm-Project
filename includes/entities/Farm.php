<?php
namespace AgriInfo\Entities;

final class Farm extends Entity
{
    protected const TABLE = 'farm';
    protected const PRIMARY_KEY = 'farm_id';
    protected const COLUMNS = ['farmer_id', 'farm_name', 'location', 'area_hectares', 'soil_type_id', 'irrigation_type_id'];
}
