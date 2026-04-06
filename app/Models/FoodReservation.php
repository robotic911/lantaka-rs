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
        /**
         * Food_Set_ID is now a TEXT column (not a FK) that encodes the selected
         * set ID together with the user's chosen customisations in one string:
         *
         *   "setId",["riceId","drinksId","dessertId","fruitId"]
         *
         * e.g.  "5",["12","18","21","9"]
         *
         * Positions in the array:
         *   0 → rice food ID
         *   1 → drinks food ID
         *   2 → dessert food ID
         *   3 → fruit food ID
         *
         * An empty string "" means "not chosen / not applicable".
         */
        'Food_Set_ID',
        'Venue_Reservation_ID',
        'Client_ID',
        'Staff_ID',
        'Food_Reservation_Serving_Date',
        'Food_Reservation_Meal_time',
        'Food_Reservation_Total_Price',
        'Food_Reservation_Status',
    ];

    /**
     * Parse the Food_Set_ID text field and return an associative array:
     *   [
     *     'set_id'     => int|null,
     *     'custom_ids' => [riceId, drinksId, dessertId, fruitId],  // strings, '' = none
     *   ]
     */
    public function parseFoodSetId(): array
    {
        $raw = $this->Food_Set_ID ?? '';

        // New format: "5",["12","18","21","9"]
        if (preg_match('/^"(\d+)",(\[.*\])$/', $raw, $m)) {
            $customIds = json_decode($m[2], true);
            return [
                'set_id'     => (int) $m[1],
                'custom_ids' => is_array($customIds) ? $customIds : ['', '', '', ''],
            ];
        }

        // Legacy format: plain integer (old records before the migration)
        if (is_numeric($raw) && $raw !== '') {
            return [
                'set_id'     => (int) $raw,
                'custom_ids' => ['', '', '', ''],
            ];
        }

        return ['set_id' => null, 'custom_ids' => ['', '', '', '']];
    }

    /** The individual food item (null for set rows). */
    public function food()
    {
        return $this->belongsTo(Food::class, 'Food_ID', 'Food_ID');
    }
}
