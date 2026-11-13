<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.account', ['user' => $request->user()]);
    }

    public function update(AccountRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return to_route('admin.account.edit')->with('success', 'Profil gespeichert.');
    }
}
