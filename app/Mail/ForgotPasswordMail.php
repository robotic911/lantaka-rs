<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Account;

class ForgotPasswordMail extends Mailable
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
        return $this->subject('Your New Password — Lantaka Reservation System')
                    ->view('emails.forgot_password')
                    ->with([
                        'user'          => $this->user,
                        'plainPassword' => $this->plainPassword,
                    ]);
    }
}
