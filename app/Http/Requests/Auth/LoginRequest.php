<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Fehlversuche pro E-Mail und IP, bevor der Login gesperrt wird.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Dauer der Sperre in Sekunden.
     */
    private const LOCKOUT_SECONDS = 15 * 60;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Zugangsdaten prüfen und einloggen – mit Sperre gegen Brute-Force-Versuche.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            event(new Lockout($this));

            throw ValidationException::withMessages([
                'email' => 'Zu viele Fehlversuche. Bitte in '.ceil(RateLimiter::availableIn($this->throttleKey()) / 60).' Minuten erneut versuchen.',
            ]);
        }

        $credentials = ['email' => Str::lower($this->string('email')->trim()->toString()), 'password' => $this->string('password')->toString()];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'E-Mail oder Passwort ist falsch.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
