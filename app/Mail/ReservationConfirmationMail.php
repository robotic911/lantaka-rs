<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Reservation;

class ReservationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Use nullsafe operator in case room/venue relationship was deleted
        $name = $this->reservation->room
                ? ($this->reservation->room?->Room_Number ?? 'Room')
                : ($this->reservation->venue?->Venue_Name ?? 'Venue');

        return $this->subject("Lantaka Online Reservation – Status Update for $name")
                    ->view('emails.reservation_confirmation')
                    ->with(['reservation' => $this->reservation]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
