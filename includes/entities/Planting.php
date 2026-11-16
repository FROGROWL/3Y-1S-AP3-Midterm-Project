<?php
namespace AgriInfo\Entities;

final class Planting extends Entity
{
    protected const TABLE = 'planting';
    protected const PRIMARY_KEY = 'planting_id';
    protected const COLUMNS = ['farm_id', 'variety_id', 'planting_date', 'area_planted', 'seed_quantity', 'expected_harvest_date', 'status_id'];
}
