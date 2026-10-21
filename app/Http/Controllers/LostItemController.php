<?php

namespace App\Http\Controllers;

use App\Actions\Messages\ReceiveMessageAction;
use App\Enums\Messages\MessageTypeEnum;
use App\Http\Requests\Site\LostItemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LostItemController extends Controller
{
    public function show(): View
    {
        return view('pages.lost-items');
    }

    public function store(LostItemRequest $request, ReceiveMessageAction $receiveMessage): RedirectResponse
    {
        $receiveMessage->execute(MessageTypeEnum::LostItem, $request->messageAttributes(), $request->file('attachment'));

        return to_route('lost-items')->withFragment('formular')->with('sent', true);
    }
}
