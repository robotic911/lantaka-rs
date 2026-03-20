<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Thank you for staying at Lantaka!',
        );
    }

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
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.guest_checkout',
    
        );
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
