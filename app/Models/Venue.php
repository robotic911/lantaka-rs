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
        'Venue_ID',
        'Venue_Name',           // from 'name', // Remember: Venues have 'name', Rooms have 'room_number/type'
        'Venue_Capacity',       // from 'capacity',
        'Venue_Internal_Price', // from 'price',
        'Venue_External_Price', // from 'external_price',
        'Venue_Status',         // from 'status',
        'Venue_Description',    // from'description',
        'Venue_Image',          // from'image'
    ];
}