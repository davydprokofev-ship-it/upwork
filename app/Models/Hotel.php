<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $table = 'hotels';
    protected $fillable = ['name', 'city', 'address', 'phone', 'email'];

    public function rooms() { return $this->hasMany(Room::class); }
    public function orders() { return $this->hasMany(Order::class); }
}
