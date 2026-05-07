<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    protected $fillable = ['company_name', 'contact_person', 'phone', 'email', 'address', 'inn', 'status'];

    public function products() { return $this->hasMany(Product::class); }
}
