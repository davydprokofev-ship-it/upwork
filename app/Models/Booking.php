<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{

    protected $table = 'booking';

    protected $fillable = [
        'user_id', 'room_id', 'guests_count',
        'check_in_date', 'check_out_date',
        'total_price', 'status'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function room() { return $this->belongsTo(Room::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
