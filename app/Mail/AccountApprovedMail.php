<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $plainPassword;
    public $campusName;

    public function __construct($user, string $plainPassword)
    {
        $this->user          = $user;
        $this->plainPassword = $plainPassword;
        $this->campusName    = env('CAMPUS_NAME', 'Lantaka Reservation System');
    }

    public function build()
    {
        return $this->subject('Account Approved — Lantaka Reservation System')
                    ->view('emails.account_approved');
    }
}
