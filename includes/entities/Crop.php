<?php
namespace AgriInfo\Entities;

final class Crop extends Entity
{
    protected const TABLE = 'crop';
    protected const PRIMARY_KEY = 'crop_id';
    protected const COLUMNS = ['crop_name', 'crop_category_id'];
}
