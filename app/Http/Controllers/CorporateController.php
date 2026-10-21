<?php

namespace App\Http\Controllers;

use App\Actions\Messages\ReceiveMessageAction;
use App\Actions\Pages\LoadPageAction;
use App\Enums\Messages\MessageTypeEnum;
use App\Enums\Pages\PageEnum;
use App\Http\Requests\Site\CorporateInquiryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CorporateController extends Controller
{
    public function show(LoadPageAction $loadPage): View
    {
        return view('pages.corporate', [
            'page' => $loadPage->execute(PageEnum::Corporate),
        ]);
    }

    public function store(CorporateInquiryRequest $request, ReceiveMessageAction $receiveMessage): RedirectResponse
    {
        $receiveMessage->execute(MessageTypeEnum::CorporateInquiry, $request->messageAttributes());

        return to_route('corporate')->withFragment('anfrage')->with('sent', true);
    }
}
