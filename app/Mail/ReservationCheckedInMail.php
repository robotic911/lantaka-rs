<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationCheckedInMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public string $type;

    public function __construct($reservation, string $type)
    {
        $this->reservation = $reservation;
        $this->type        = $type;
    }

    public function build(): self
    {
        return $this->subject('You\'re Checked In — Lantaka Reservation System')
                    ->view('emails.reservation_checked_in')
                    ->with(['reservation' => $this->reservation, 'type' => $this->type]);
    }
}
