<?php

namespace App\Http\Controllers;

use App\Actions\Messages\ReceiveMessageAction;
use App\Actions\Pages\LoadPageAction;
use App\Enums\Messages\MessageTypeEnum;
use App\Enums\Pages\PageEnum;
use App\Http\Requests\Site\ApplicationRequest;
use App\Models\JobPosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    public function show(Request $request, LoadPageAction $loadPage): View
    {
        return view('pages.jobs', [
            'page' => $loadPage->execute(PageEnum::Jobs),
            'positions' => JobPosition::query()->active()->ordered()->get(),
            'selectedPosition' => (string) $request->query('stelle'),
        ]);
    }

    public function store(ApplicationRequest $request, ReceiveMessageAction $receiveMessage): RedirectResponse
    {
        $receiveMessage->execute(MessageTypeEnum::Application, $request->messageAttributes(), $request->file('attachment'));

        return to_route('jobs')->withFragment('bewerben')->with('sent', true);
    }
}
