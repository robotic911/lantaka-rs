<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    // This allows these columns to be filled by Reservation::create()
    protected $fillable = [
        'user_id',
        'accommodation_id',
        'type',
        'check_in',
        'check_out',
        'pax',
        'total_amount',
        'status'
    ];
    // -- RELATIONSHIPS --

    // Link to the User who made the booking
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Link to the Room (if it's a room booking)
    // Note: You might need to adjust this if you store room_id and venue_id in the same column
    public function room()
    {
        return $this->belongsTo(Room::class, 'accommodation_id');
    }

    // Link to the Venue (if it's a venue booking)
    public function venue()
    {
        return $this->belongsTo(Venue::class, 'accommodation_id');
    }

    public function foods() {
    return $this->belongsToMany(
        \App\Models\Food::class, 
        'food_reservations', 
        'venue_reservation_id', 
        'food_id'
    )->withPivot(['food_reservation_id', 'status', 'total_price']);
}
}