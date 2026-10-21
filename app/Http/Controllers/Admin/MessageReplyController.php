<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Messages\ReplyToMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageReplyRequest;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MessageReplyController extends Controller
{
    public function store(MessageReplyRequest $request, Message $message, ReplyToMessageAction $replyToMessage): RedirectResponse
    {
        try {
            $replyToMessage->execute($message, $request->user(), $request->input('body'));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Die E-Mail konnte nicht verschickt werden. Bitte später erneut versuchen oder direkt per Mail antworten.');
        }

        return to_route('admin.messages.show', $message)->withFragment('verlauf')
            ->with('success', 'Antwort an '.$message->email.' verschickt.');
    }
}
