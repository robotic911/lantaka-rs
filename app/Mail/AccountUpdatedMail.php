<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $campusName;
    public $changedFields;

    public function __construct($user, array $changedFields = [])
    {
        $this->user          = $user;
        $this->campusName    = env('CAMPUS_NAME', 'Lantaka Reservation System');
        $this->changedFields = $changedFields;
    }

    public function build()
    {
        return $this->subject('Account Details Updated — Lantaka Reservation System')
                    ->view('emails.account_updated');
    }
}
