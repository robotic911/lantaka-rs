<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomReservation extends Model
{
    protected $table = 'Room_Reservation';
    protected $primaryKey = 'Room_Reservation_ID';

    protected $fillable = [
        'Room_ID', // from'room_id', // Changed to lowercase to match migration
        'Client_ID',
        'Room_Reservation_Date',
        'Room_Reservation_Check_In_Time',
        'Room_Reservation_Check_Out_Time',
        'Room_Reservation_Total_Price',
        'Room_Reservation_Pax', //from 'pax',
        'Room_Reservation_Purpose', //from 'purpose',
        'Room_Reservation_Discount', //from 'status',
        'Room_Reservation_Status', //from 'status',
        'Room_Reservation_Payment_Status', // from 'payment_status',
        'Room_Reservation_Additional_Fees',
        'Room_Reservation_Additional_Fees_Desc'
    ];

    public function room() {
        return $this->belongsTo(Room::class, 'Room_ID'); // Match lowercase
    }

    public function user() {
        return $this->belongsTo(Account::class, 'Client_ID');
    }
}
