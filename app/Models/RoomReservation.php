<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomReservation extends Model
{
    protected $table = 'room_reservations';
    protected $primaryKey = 'Room_Reservation_ID';

    protected $fillable = [
        'room_id', // Changed to lowercase to match migration
        'Client_ID', 
        'Room_Reservation_Date', 
        'Room_Reservation_Check_In_Time',
        'Room_Reservation_Check_Out_Time', 
        'Room_Reservation_Total_Price',
        'pax', 
        'status'
    ];

    public function room() { 
        return $this->belongsTo(Room::class, 'room_id'); // Match lowercase
    }

    public function user() { 
        return $this->belongsTo(User::class, 'Client_ID'); 
    }
}
