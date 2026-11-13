<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountPasswordRequest;
use Illuminate\Http\RedirectResponse;

class AccountPasswordController extends Controller
{
    public function update(AccountPasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);
        $request->session()->regenerate();

        return to_route('admin.account.edit')
            ->with('success', 'Dein Passwort wurde geändert.')
            ->with('password_changed', true);
    }
}
