<?php

namespace App\Mail;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $staff,
        public Restaurant $restaurant,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your staff account for {$this->restaurant->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff-account-created',
            with: [
                'staff' => $this->staff,
                'restaurant' => $this->restaurant,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }
}
