<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomReservation extends Model
{
    protected $table = 'Room_Reservation';
    protected $primaryKey = 'Room_Reservation_ID';

    protected $fillable = [
        'Room_ID',
        'Client_ID',
        'Room_Reservation_Date',
        'Room_Reservation_Check_In_Time',
        'Room_Reservation_Check_Out_Time',
        'Room_Reservation_Total_Price',
        'Room_Reservation_Pax',
        'Room_Reservation_Purpose',
        'Room_Reservation_Notes',
        'Room_Reservation_Discount',
        'Room_Reservation_Status',
        'Room_Reservation_Payment_Status',
        'Room_Reservation_Additional_Fees',
        'Room_Reservation_Additional_Fees_Desc',
        // Cancellation request fields (stored directly on the reservation row)
        'cancellation_status',
        'cancellation_reason',
        'cancellation_admin_note',
        'cancellation_processed_by',
        'cancellation_requested_at',
        'cancellation_processed_at',
        // Request for Changes fields (reschedule / food modification)
        'change_request_status',
        'change_request_type',
        'change_request_reason',
        'change_request_details',
        'change_request_admin_note',
        'change_request_processed_by',
        'change_request_requested_at',
        'change_request_processed_at',
    ];

    protected $casts = [
        'change_request_details'         => 'array',
        'Room_Reservation_Total_Price'    => 'decimal:2',
        'Room_Reservation_Discount'       => 'decimal:2',
        'Room_Reservation_Additional_Fees'=> 'decimal:2',
    ];

    public function room() {
        return $this->belongsTo(Room::class, 'Room_ID'); // Match lowercase
    }

    public function user() {
        return $this->belongsTo(Account::class, 'Client_ID');
    }
}
