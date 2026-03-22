<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'Room';
    protected $primaryKey = 'Room_ID';

    use HasFactory;

    // These must match your Migration columns exactly
    protected $fillable = [
        'user_id',
        'Room_ID',              //from 'user_id'
        'Room_Number',          //from'room_number'
        'Room_Type',            // from 'room_type'
        'Room_Capacity',        // from'capacity'
        'Room_Internal_Price',  //from'price'
        'Room_External_Price',  //from'external_price'
        'Room_Status',          // from status'
        'Room_Description',     // from'description',
        'Room_Image'            // from 'image'
    ];
}