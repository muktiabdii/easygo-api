<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetOtpMail extends Mailable
{
    public $user;
    public $otp;

    public function __construct($user, $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode OTP Reset Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'reset_otp_mail',
            with: [
                'user' => $this->user,
                'otp' => $this->otp
            ],
        );
    }
}
