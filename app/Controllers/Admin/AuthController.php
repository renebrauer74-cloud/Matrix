<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('admin');
        }
        view('admin/login', ['title' => 'Login'], 'layouts/blank');
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ip = client_ip();

        if (Auth::isLockedOut($ip)) {
            flash('error', 'Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.');
            redirect('admin/login');
        }
        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            Auth::recordFailure($ip, $email);
            Session::flash('_old', ['email' => $email]);
            flash('error', 'E-Mail oder Passwort ist falsch.');
            redirect('admin/login');
        }

        Auth::clearFailures($ip);
        $intended = (string) Session::pull('intended', '');
        // Nur interne Admin-Pfade als Weiterleitungsziel erlauben
        if ($intended !== '' && preg_match('#^/[^/\\\\]#', $intended) && str_contains($intended, '/admin')) {
            header('Location: ' . $intended, true, 302);
            exit;
        }
        redirect('admin');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'Du wurdest abgemeldet.');
        redirect('admin/login');
    }
}
