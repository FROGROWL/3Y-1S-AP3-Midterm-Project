<?php
namespace AgriInfo\Entities;

final class Farmer extends Entity
{
    protected const TABLE = 'farmer';
    protected const PRIMARY_KEY = 'farmer_id';
    protected const COLUMNS = ['first_name', 'last_name', 'contact_number', 'address', 'date_registered'];
}
