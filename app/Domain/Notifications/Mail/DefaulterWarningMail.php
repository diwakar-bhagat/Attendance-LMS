<?php

namespace App\Domain\Notifications\Mail;

use App\Domain\Identity\Models\User;
use App\Domain\Academic\Models\Section;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DefaulterWarningMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student;
    public $sectionName;
    public $facultyName;
    public $percentage;

    /**
     * Create a new message instance.
     */
    public function __construct(User $student, string $sectionName, string $facultyName, float $percentage)
    {
        $this->student = $student;
        $this->sectionName = $sectionName;
        $this->facultyName = $facultyName;
        $this->percentage = $percentage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'URGENT: Shortage of Attendance Notice | ' . $this->sectionName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notifications.defaulter_warning',
        );
    }
}