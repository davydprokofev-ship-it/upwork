<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';
    protected $fillable = ['hotel_id', 'room_number', 'type', 'floor', 'price_per_night', 'status'];

    public function hotel() { return $this->belongsTo(Hotel::class); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function orders() { return $this->hasMany(Order::class); }
}
