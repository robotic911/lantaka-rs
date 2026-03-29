<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RETIRED — cancellation data is now stored directly on the
 * RoomReservation / VenueReservation models (cancellation_status,
 * cancellation_reason, cancellation_admin_note, etc.).
 *
 * This stub is kept so the class name resolves without fatal errors
 * if any cached/old reference remains. It can be deleted once you
 * are confident no code path instantiates it.
 */
class CancellationRequest extends Model
{
    protected $table = 'cancellation_requests';
}
