<?php
namespace AgriInfo\Entities;

final class CropVariety extends Entity
{
    protected const TABLE = 'crop_variety';
    protected const PRIMARY_KEY = 'variety_id';
    protected const COLUMNS = ['crop_id', 'variety_name', 'growing_period_days', 'growing_season_id'];
}
