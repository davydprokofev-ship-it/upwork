<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $fillable = ['user_id', 'room_id', 'hotel_id', 'status', 'total_price', 'note'];

    public function user() { return $this->belongsTo(User::class); }
    public function room() { return $this->belongsTo(Room::class); }
    public function hotel() { return $this->belongsTo(Hotel::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
}
