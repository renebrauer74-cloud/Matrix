<?php

namespace App\Mail;

use App\Enums\Messages\MessageTypeEnum;
use App\Models\Message;
use App\Models\MessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Antwort aus dem Admin-Posteingang an den Absender einer Nachricht.
 *
 * Die Nachricht heißt hier $inquiry, weil $message in Mail-Views für die Mail selbst reserviert ist.
 */
class MessageReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Message $inquiry, public MessageReply $reply) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $replyTo = $this->inquiry->type === MessageTypeEnum::Application
            ? config('matrix.venue.jobs_email')
            : config('matrix.venue.email');

        return new Envelope(
            replyTo: [new Address($replyTo, config('matrix.venue.name'))],
            subject: 'Re: '.($this->inquiry->subject ?: $this->inquiry->type->label()).' – '.config('matrix.venue.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(text: 'mail.message-reply');
    }
}
