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
        'Venue_Reservation_Notes',
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
        'change_request_details'          => 'array',
        'Venue_Reservation_Total_Price'    => 'decimal:2',
        'Venue_Reservation_Discount'       => 'decimal:2',
        'Venue_Reservation_Additional_Fees'=> 'decimal:2',
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
            'Food_Reservation',     // pivot table
            'Venue_Reservation_ID', // FK for this model
            'Food_ID'               // FK for Food
        )->withPivot(
            'Food_Reservation_ID',
            'Food_Reservation_Status',
            'Food_Reservation_Serving_Date',
            'Food_Reservation_Meal_time',
            'Food_Reservation_Total_Price',
            'Food_Set_ID'
        )->whereNotNull('Food_Reservation.Food_ID');
    }

    /**
     * Food-set reservation rows — one row per set selection.
     * Food_Set_ID is now a TEXT column containing the set ID and customisation IDs;
     * use FoodReservation::parseFoodSetId() to decode it.
     */
    public function foodSetReservations()
    {
        return $this->hasMany(FoodReservation::class, 'Venue_Reservation_ID', 'Venue_Reservation_ID')
                    ->whereNotNull('Food_Set_ID');
    }

    /**
     * All food reservation rows (individual + set) for this venue reservation.
     * Used to compute the total food cost.
     */
    public function foodReservations()
    {
        return $this->hasMany(FoodReservation::class, 'Venue_Reservation_ID', 'Venue_Reservation_ID');
    }
}
