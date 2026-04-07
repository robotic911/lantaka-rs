<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CancellationRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public string $type;
    public string $clientName;
    public string $accName;

    public function __construct($reservation, string $type, string $clientName, string $accName)
    {
        $this->reservation = $reservation;
        $this->type        = $type;
        $this->clientName  = $clientName;
        $this->accName     = $accName;
    }

    public function build(): self
    {
        return $this->subject('New Cancellation Request — Lantaka Reservation System')
                    ->view('emails.cancellation_requested')
                    ->with([
                        'reservation' => $this->reservation,
                        'type'        => $this->type,
                        'clientName'  => $this->clientName,
                        'accName'     => $this->accName,
                    ]);
    }
}
