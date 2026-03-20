<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $campusName;

    public function __construct($user)
    {
        $this->user       = $user;
        $this->campusName = env('CAMPUS_NAME', 'Lantaka Reservation System');
    }

    public function build()
    {
        return $this->subject('Account Registration Update — Lantaka Reservation System')
                    ->view('emails.account_declined');
    }
}
