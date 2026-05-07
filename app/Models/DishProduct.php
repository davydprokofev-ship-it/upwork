<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DishProduct extends Model
{
    protected $table = 'dish_products';
    protected $fillable = ['dish_id', 'product_id', 'quantity'];

    public function dish() { return $this->belongsTo(Dish::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
