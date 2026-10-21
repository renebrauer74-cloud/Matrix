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
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('new_password')) {
            $user->password = $request->input('new_password');
        }

        $user->save();

        if ($user->wasChanged('password')) {
            $request->session()->regenerate();
        }

        return to_route('admin.account.edit')->with('success', 'Konto gespeichert.');
    }
}
