<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Models\User;

final class UserController
{
    public function index(): void
    {
        view('admin/users', ['title' => 'Benutzer', 'users' => User::all()], 'layouts/admin');
    }

    public function store(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Bitte einen Namen eingeben.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        } elseif (User::emailTaken($email)) {
            $errors['email'] = 'Diese E-Mail-Adresse ist bereits vergeben.';
        }
        if (mb_strlen($password) < 10) {
            $errors['password'] = 'Mindestens 10 Zeichen.';
        }
        if ($errors) {
            Session::flash('_old', ['name' => $name, 'email' => $email]);
            Session::flash('_errors', $errors);
            redirect('admin/benutzer');
        }

        User::create($name, $email, $password);
        flash('success', "Benutzer $name wurde angelegt.");
        redirect('admin/benutzer');
    }

    public function destroy(string $id): void
    {
        if ((int) $id === Auth::id()) {
            flash('error', 'Du kannst dich nicht selbst löschen.');
        } elseif (User::count() <= 1) {
            flash('error', 'Der letzte Benutzer kann nicht gelöscht werden.');
        } else {
            User::delete((int) $id);
            flash('success', 'Benutzer wurde gelöscht.');
        }
        redirect('admin/benutzer');
    }

    public function account(): void
    {
        view('admin/account', ['title' => 'Mein Konto', 'user' => Auth::user()], 'layouts/admin');
    }

    public function updateAccount(): void
    {
        $user = Auth::user();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirmation'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Bitte einen Namen eingeben.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        } elseif (User::emailTaken($email, (int) $user['id'])) {
            $errors['email'] = 'Diese E-Mail-Adresse ist bereits vergeben.';
        }
        if ($new !== '') {
            if (!password_verify($current, $user['password_hash'])) {
                $errors['current_password'] = 'Aktuelles Passwort ist falsch.';
            }
            if (mb_strlen($new) < 10) {
                $errors['new_password'] = 'Mindestens 10 Zeichen.';
            } elseif ($new !== $confirm) {
                $errors['new_password_confirmation'] = 'Passwörter stimmen nicht überein.';
            }
        }
        if ($errors) {
            Session::flash('_old', ['name' => $name, 'email' => $email]);
            Session::flash('_errors', $errors);
            redirect('admin/konto');
        }

        User::updateProfile((int) $user['id'], $name, $email);
        if ($new !== '') {
            User::setPassword((int) $user['id'], $new);
            session_regenerate_id(true);
        }
        flash('success', 'Konto gespeichert.');
        redirect('admin/konto');
    }
}
