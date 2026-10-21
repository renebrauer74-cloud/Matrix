<?php

namespace App\Http\Controllers;

use App\Actions\Messages\ReceiveMessageAction;
use App\Enums\Messages\MessageTypeEnum;
use App\Http\Requests\Site\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact');
    }

    public function store(ContactRequest $request, ReceiveMessageAction $receiveMessage): RedirectResponse
    {
        $receiveMessage->execute(MessageTypeEnum::Contact, $request->messageAttributes());

        return to_route('contact')->withFragment('formular')->with('sent', true);
    }
}
