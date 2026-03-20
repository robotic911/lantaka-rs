<?php

namespace App\Mail;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountReactivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Account $user;
    public string $plainPassword;

    public function __construct(Account $user, string $plainPassword)
    {
        $this->user          = $user;
        $this->plainPassword = $plainPassword;
    }

    public function build(): self
    {
        return $this->subject('Account Reactivated — Lantaka Reservation System')
                    ->view('emails.account_reactivated');
    }
}
