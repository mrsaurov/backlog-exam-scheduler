<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ExamNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $content;
    public $attachmentPaths;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $content, $attachmentPaths = [])
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->attachmentPaths = $attachmentPaths;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            html: 'emails.exam-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        $attachments = [];
        
        foreach ($this->attachmentPaths as $attachmentData) {
            $attachments[] = Attachment::fromPath($attachmentData['path'])
                ->as($attachmentData['name'])
                ->withMime($attachmentData['mime']);
        }
        
        return $attachments;
    }
}
