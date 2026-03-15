<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodReservation extends Model
{
    protected $table = 'food_reservations';
    protected $primaryKey = 'food_reservation_id';
    
    protected $fillable = [
        'food_id',
        'venue_reservation_id',
        'client_id',
        'staff_id',
        'serving_time',
        'meal_time',
        'total_price',
        'status',
    ];
}
