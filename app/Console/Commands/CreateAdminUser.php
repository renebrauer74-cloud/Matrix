<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('matrix:admin
    {email : E-Mail-Adresse für den Login}
    {--name= : Anzeigename (Standard: Teil vor dem @)}
    {--password= : Passwort direkt setzen statt interaktiv abfragen, z. B. auf Laravel Cloud}
    {--password-hash= : Vorhandenen bcrypt-Hash übernehmen, z. B. aus der alten users-Tabelle}')]
#[Description('Legt einen Admin-Zugang an oder setzt dessen Passwort neu')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $hash = $this->option('password-hash');
        $plain = $this->option('password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Bitte eine gültige E-Mail-Adresse angeben.');

            return self::FAILURE;
        }

        if ($plain !== null && mb_strlen($plain) < 10) {
            $this->error('Das Passwort muss mindestens 10 Zeichen lang sein.');

            return self::FAILURE;
        }

        if ($hash !== null && ! str_starts_with($hash, '$2y$')) {
            $this->error('Der Hash muss ein bcrypt-Hash sein ($2y$...).');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $this->option('name') ?? text(label: 'Name', default: Str::before($email, '@'), required: true);
        }

        $user->password = $hash ?? Hash::make($plain ?? password(
            label: 'Passwort',
            required: true,
            validate: fn (string $value): ?string => mb_strlen($value) < 10 ? 'Mindestens 10 Zeichen.' : null,
        ));
        $user->save();

        $this->info($user->wasRecentlyCreated
            ? "Admin {$email} angelegt."
            : "Passwort für {$email} aktualisiert.");

        return self::SUCCESS;
    }
}
