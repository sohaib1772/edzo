<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
   use Queueable, SerializesModels;

    public $code;
    public $subject;


    public function __construct($code, $subject)
    {
        $this->code = $code;
        $this->subject = $subject;

    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.email_verification');
    }
}
