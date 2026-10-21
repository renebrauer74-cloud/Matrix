<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    private static ?array $user = null;

    public static function user(): ?array
    {
        if (self::$user === null && ($id = Session::get('uid'))) {
            self::$user = User::find((int) $id);
            if (self::$user === null) {
                Session::forget('uid');
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                User::setPassword((int) $user['id'], $password);
            }
            self::login($user);
            return true;
        }
        return false;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        Session::put('uid', (int) $user['id']);
        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        self::$user = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
    }

    // --- Brute-Force-Schutz -------------------------------------------------

    public static function isLockedOut(string $ip): bool
    {
        $count = (int) Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL ' . self::LOCK_MINUTES . ' MINUTE)',
            [$ip]
        );
        return $count >= self::MAX_ATTEMPTS;
    }

    public static function recordFailure(string $ip, string $email): void
    {
        Database::query('INSERT INTO login_attempts (ip, email, attempted_at) VALUES (?, ?, NOW())', [$ip, mb_substr($email, 0, 190)]);
        Database::query('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }

    public static function clearFailures(string $ip): void
    {
        Database::query('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
    }
}
