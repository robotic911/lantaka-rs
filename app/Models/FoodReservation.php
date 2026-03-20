<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodReservation extends Model
{
    protected $table = 'Food_Reservation';
    protected $primaryKey = 'Food_Reservation_ID';

    protected $fillable = [
        'Food_ID',
        'Venue_Reservation_ID',
        'Client_ID',
        'Staff_ID',
        'Food_Reservation_Serving_Date',
        'Food_Reservation_Meal_time',
        'Food_Reservation_Total_Price',
        'Food_Reservation_Status',
    ];
}
