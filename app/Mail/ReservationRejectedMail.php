<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationRejectedMail extends Mailable
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
        return $this->subject('Reservation Update — Lantaka Reservation System')
                    ->view('emails.reservation_rejected')
                    ->with(['reservation' => $this->reservation, 'type' => $this->type]);
    }
}
