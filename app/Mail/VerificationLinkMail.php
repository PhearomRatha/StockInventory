<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class VerificationLinkMail extends Mailable
{
    public $name;
    public $verificationUrl;
    public $expiresInMinutes;

    /**
     * Create a new message instance.
     *
     * @param string $name
     * @param string $verificationUrl
     * @param int $expiresInMinutes
     */
    public function __construct(string $name, string $verificationUrl, int $expiresInMinutes = 60)
    {
        $this->name = $name;
        $this->verificationUrl = $verificationUrl;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('🔗 Verify Your Email Address - Stock Inventory System')
                    ->view('emails.verify-link')
                    ->with([
                        'name' => $this->name,
                        'verificationUrl' => $this->verificationUrl,
                        'expiresInMinutes' => $this->expiresInMinutes,
                    ]);
    }
}
