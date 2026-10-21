<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::query()->create($request->validated());

        return to_route('admin.users.index')->with('success', "Benutzer {$user->name} wurde angelegt.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return to_route('admin.users.index')->with('error', 'Du kannst dich nicht selbst löschen.');
        }

        if (User::query()->count() <= 1) {
            return to_route('admin.users.index')->with('error', 'Der letzte Benutzer kann nicht gelöscht werden.');
        }

        $user->delete();

        return to_route('admin.users.index')->with('success', 'Benutzer wurde gelöscht.');
    }
}
