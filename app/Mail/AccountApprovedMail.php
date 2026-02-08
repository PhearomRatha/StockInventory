<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountApprovedMail extends Mailable
{
    public $name;
    public $email;

    /**
     * Create a new message instance.
     *
     * @param string $name
     * @param string $email
     */
    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('✅ Your Account Has Been Approved - Stock Inventory System')
                    ->view('emails.account-approved')
                    ->with([
                        'name' => $this->name,
                        'email' => $this->email,
                        'loginUrl' => route('login'),
                    ]);
    }
}
