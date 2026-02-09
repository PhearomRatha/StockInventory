<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountRejectedMail extends Mailable
{
    public $name;
    public $reason;

    /**
     * Create a new message instance.
     *
     * @param string $name
     * @param string $reason
     */
    public function __construct(string $name, string $reason)
    {
        $this->name = $name;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('❌ Your Registration Has Been Rejected - Stock Inventory System')
                    ->view('emails.account-rejected')
                    ->with([
                        'name' => $this->name,
                        'reason' => $this->reason,
                    ]);
    }
}
