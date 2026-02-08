<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class VerifyEmail extends Mailable
{
    public $user;
    public $otp;
    public $expiresAt;

    /**
     * Create a new message instance.
     *
     * @param mixed $user
     * @param string $otp
     */
    public function __construct($user, $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
        $this->expiresAt = now()->addMinutes(10)->format('Y-m-d H:i:s');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Verify Your Email Address - OTP Verification')
                    ->view('emails.verify');
    }
}
