<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function build()
    {
        // Use our cross-model check for the name
        $name = ($this->reservation->room)
                ? ($this->reservation->room->Room_Number ?? 'Room')
                : ($this->reservation->venue->Venue_Name ?? 'Venue');

        return $this->subject("Lantaka Online Reservation – Status Update for $name")
                    ->view('emails.reservation_confirmation');
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
        view: 'emails.reservation_confirmation',
        with: [
            'reservation' => $this->reservation, // This explicitly sends it to the view
        ],
    );
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
