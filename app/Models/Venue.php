<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{    
    protected $table = 'Venue';
    protected $primaryKey = 'Venue_ID';


    use HasFactory;

    protected $fillable = [
        'user_id',
        // Venue_ID intentionally excluded — primary key must not be mass-assignable
        'Venue_Name',           // from 'name'
        'Venue_Capacity',       // from 'capacity'
        'Venue_Internal_Price', // from 'price'
        'Venue_External_Price', // from 'external_price'
        'Venue_Status',         // from 'status'
        'Venue_Description',    // from 'description'
        'Venue_Image',          // from 'image'
    ];

    protected $casts = [
        'Venue_Internal_Price' => 'decimal:2',
        'Venue_External_Price' => 'decimal:2',
    ];
}