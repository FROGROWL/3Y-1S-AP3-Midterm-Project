<?php
namespace AgriInfo\Entities;

final class Harvest extends Entity
{
    protected const TABLE = 'harvest';
    protected const PRIMARY_KEY = 'harvest_id';
    protected const COLUMNS = ['planting_id', 'harvest_date', 'quantity_kg', 'selling_price_per_kg', 'quality_grade_id', 'remark_id'];
}
