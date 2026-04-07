<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangeRequestSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public string $type;
    public string $clientName;
    public string $accName;
    public string $reqType;

    public function __construct($reservation, string $type, string $clientName, string $accName, string $reqType)
    {
        $this->reservation = $reservation;
        $this->type        = $type;
        $this->clientName  = $clientName;
        $this->accName     = $accName;
        $this->reqType     = $reqType;
    }

    public function build(): self
    {
        return $this->subject('New Request for Changes — Lantaka Reservation System')
                    ->view('emails.change_request_submitted')
                    ->with([
                        'reservation' => $this->reservation,
                        'type'        => $this->type,
                        'clientName'  => $this->clientName,
                        'accName'     => $this->accName,
                        'reqType'     => $this->reqType,
                    ]);
    }
}
