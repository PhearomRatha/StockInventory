<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class OTPMail extends Mailable
{
    public $name;
    public $otp;
    public $expiresAt;

    /**
     * Create a new message instance.
     *
     * @param string $name
     * @param string $otp
     * @param \Carbon\Carbon $expiresAt
     */
    public function __construct(string $name, string $otp, $expiresAt)
    {
        $this->name = $name;
        $this->otp = $otp;
        $this->expiresAt = $expiresAt instanceof \Carbon\Carbon 
            ? $expiresAt->format('Y-m-d H:i:s') 
            : $expiresAt;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('🔐 Your OTP Code - Stock Inventory System')
                    ->view('emails.otp')
                    ->with([
                        'name' => $this->name,
                        'otp' => $this->otp,
                        'expiresAt' => $this->expiresAt,
                    ]);
    }
}
