<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class User
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return DB::one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    }

    public static function all(): array
    {
        return DB::all('SELECT id, name, email, last_login_at, created_at FROM users ORDER BY name');
    }

    public static function count(): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM users');
    }

    public static function create(string $name, string $email, string $password): int
    {
        return DB::insert('users', [
            'name'          => trim($name),
            'email'         => mb_strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public static function updateProfile(int $id, string $name, string $email): void
    {
        DB::update('users', ['name' => trim($name), 'email' => mb_strtolower(trim($email))], $id);
    }

    public static function setPassword(int $id, string $password): void
    {
        DB::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], $id);
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function emailTaken(string $email, ?int $ignoreId = null): bool
    {
        return (bool) DB::value(
            'SELECT 1 FROM users WHERE email = ? AND id <> ?',
            [mb_strtolower(trim($email)), $ignoreId ?? 0]
        );
    }
}
