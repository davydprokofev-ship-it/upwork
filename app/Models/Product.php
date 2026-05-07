<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $fillable = ['supplier_id', 'name', 'category', 'unit', 'price_per_unit', 'quantity_in_stock', 'min_stock', 'status'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function dish() { return $this->belongsToMany(Dish::class, 'dish_products')->withPivot('quantity'); }
}
