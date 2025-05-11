<?php

namespace App\Mail;

use App\Models\Reminder; // Import Reminder model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Implement ShouldQueue for background sending
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderNotification extends Mailable implements ShouldQueue // Implement ShouldQueue
{
    use Queueable, SerializesModels;

    // Make the reminder public so it's automatically available in the view
    public Reminder $reminder;

    /**
     * Create a new message instance.
     */
    public function __construct(Reminder $reminder) // Accept Reminder object
    {
        $this->reminder = $reminder;
    }

    /**
     * Get the message envelope.
     * Defines subject, recipients etc.
     */
    public function envelope(): Envelope
    {
        // Set subject dynamically based on reminder title
        return new Envelope(
            subject: 'Reminder: ' . $this->reminder->title,
        );
    }

    /**
     * Get the message content definition.
     * Specifies the view to use.
     */
    public function content(): Content
    {
        // Use the markdown view we generated
        return new Content(
            markdown: 'emails.reminders.notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return []; // No attachments for now
    }
}