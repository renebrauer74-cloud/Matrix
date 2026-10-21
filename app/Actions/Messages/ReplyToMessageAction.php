<?php

namespace App\Actions\Messages;

use App\Enums\Messages\MessageStatusEnum;
use App\Mail\MessageReplyMail;
use App\Models\Message;
use App\Models\MessageReply;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Schickt eine Antwort per E-Mail an den Absender und hält sie im Verlauf der Nachricht fest.
 *
 * Gespeichert wird erst nach dem Versand – schlägt er fehl, bleibt der Verlauf unverändert.
 */
class ReplyToMessageAction
{
    public function execute(Message $message, User $author, string $body): MessageReply
    {
        $reply = $message->replies()->make(['body' => $body]);
        $reply->author()->associate($author);

        Mail::to($message->email, $message->name)->send(new MessageReplyMail($message, $reply));

        $reply->save();

        if ($message->status === MessageStatusEnum::New) {
            $message->update(['status' => MessageStatusEnum::InProgress]);
        }

        return $reply;
    }
}
