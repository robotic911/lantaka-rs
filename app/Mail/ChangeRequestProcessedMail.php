<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangeRequestProcessedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public string $type;
    public string $decision;   // 'approved' | 'rejected'
    public ?string $adminNote;

    public function __construct($reservation, string $type, string $decision, ?string $adminNote = null)
    {
        $this->reservation = $reservation;
        $this->type        = $type;
        $this->decision    = $decision;
        $this->adminNote   = $adminNote;
    }

    public function build(): self
    {
        $subject = $this->decision === 'approved'
            ? 'Request for Changes Approved — Lantaka Reservation System'
            : 'Request for Changes Rejected — Lantaka Reservation System';

        return $this->subject($subject)
                    ->view('emails.change_request_processed')
                    ->with([
                        'reservation' => $this->reservation,
                        'type'        => $this->type,
                        'decision'    => $this->decision,
                        'adminNote'   => $this->adminNote,
                    ]);
    }
}
