<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueReservation extends Model
{
    use HasFactory;

    protected $table = 'Venue_Reservation';
    protected $primaryKey = 'Venue_Reservation_ID';

    protected $fillable = [
        'Venue_ID',
        'Client_ID',
        'Venue_Reservation_Date',
        'Venue_Reservation_Check_In_Time',
        'Venue_Reservation_Check_Out_Time',
        'Venue_Reservation_Pax',
        'Venue_Reservation_Purpose',
        'Venue_Reservation_Total_Price',
        'Venue_Reservation_Status',
        'Venue_Reservation_Payment_Status',
        'Venue_Reservation_Additional_Fees',
        'Venue_Reservation_Additional_Fees_Desc',
        'Venue_Reservation_Discount',
        // Cancellation request fields (stored directly on the reservation row)
        'cancellation_status',
        'cancellation_reason',
        'cancellation_admin_note',
        'cancellation_processed_by',
        'cancellation_requested_at',
        'cancellation_processed_at',
    ];

    public function venue() {
        return $this->belongsTo(Venue::class, 'Venue_ID'); // Match lowercase
    }

    public function user() {
        return $this->belongsTo(Account::class, 'Client_ID');
    }

    public function foods()
    {
        return $this->belongsToMany(
            Food::class,
            'Food_Reservation',    // Table name
            'Venue_Reservation_ID', // Foreign key for VenueReservation
            'Food_ID'               // Foreign key for Food
        )->withPivot('Food_Reservation_Status', 'Food_Reservation_Serving_Date', 'Food_Reservation_Meal_time', 'Food_Reservation_Total_Price');
    }
}
