<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueReservation extends Model
{
    use HasFactory;

    protected $table = 'venue_reservations';
    protected $primaryKey = 'Venue_Reservation_ID';

    protected $fillable = [
        'venue_id', 
        'Client_ID',
        'Venue_Reservation_Date',
        'Venue_Reservation_Check_In_Time',
        'Venue_Reservation_Check_Out_Time',
        'pax',
        'Venue_Reservation_Total_Price',
        'status',
    ];

    public function venue() {
        return $this->belongsTo(Venue::class, 'venue_id'); // Match lowercase
    }

    public function user() {
        return $this->belongsTo(User::class, 'Client_ID');
    }

    public function foods()
    {
        return $this->belongsToMany(
            Food::class, 
            'food_reservations',    // Table name
            'venue_reservation_id', // Foreign key for VenueReservation
            'food_id'               // Foreign key for Food
        )->withPivot('status', 'serving_time', 'total_price');
    }
}