<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CancellationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public string $type;
    public ?string $adminNote;

    public function __construct($reservation, string $type, ?string $adminNote = null)
    {
        $this->reservation = $reservation;
        $this->type        = $type;
        $this->adminNote   = $adminNote;
    }

    public function build(): self
    {
        return $this->subject('Cancellation Request Rejected — Lantaka Reservation System')
                    ->view('emails.cancellation_rejected')
                    ->with([
                        'reservation' => $this->reservation,
                        'type'        => $this->type,
                        'adminNote'   => $this->adminNote,
                    ]);
    }
}
