<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['name', 'sku', 'supplier', 'stock', 'minimum_stock', 'unit_cost', 'sale_price'];
}
