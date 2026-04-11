<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class GuestCheckOutMail extends Mailable
{
    use Queueable, SerializesModels;
    public $reservation;
    public $type;
    public $foodTotal;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reservation, $type = 'room', $foodTotal = 0)
    {
        $this->reservation = $reservation;
        $this->type        = $type;
        $this->foodTotal   = $foodTotal;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('You\'ve Checked Out — Lantaka Reservation System')
                    ->view('emails.guest_checkout')
                    ->with([
                        'reservation' => $this->reservation,
                        'type'        => $this->type,
                        'foodTotal'   => $this->foodTotal,
                    ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
