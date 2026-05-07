<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    protected $table = 'dishes';
    protected $fillable = ['name', 'category', 'price', 'calories', 'weight', 'description', 'image', 'available'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'dish_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
    public function orderItems() { return $this->hasMany(OrderItem::class); }
}
