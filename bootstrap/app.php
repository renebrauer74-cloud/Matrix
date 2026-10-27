<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Abgelaufene Sitzung im Admin (z. B. beim Löschen nach langer Pause): zum Login bzw. zurück
        // ins Formular mit Hinweis – statt der Fehlerseite „Sitzung abgelaufen“.
        $exceptions->render(function (HttpException $exception, Request $request) {
            if (! $exception->getPrevious() instanceof TokenMismatchException || ! $request->is('admin', 'admin/*') || $request->expectsJson()) {
                return null;
            }

            if ($request->user() === null) {
                redirect()->setIntendedUrl(url()->previous(route('admin.dashboard')));

                return to_route('admin.login')->with('error', 'Deine Sitzung war abgelaufen – bitte erneut anmelden. Die letzte Aktion wurde nicht ausgeführt.');
            }

            return back(fallback: route('admin.dashboard'))
                ->withInput($request->except(['_token', '_method', 'password', 'current_password', 'password_confirmation']))
                ->with('error', 'Das Formular war zu lange geöffnet – bitte noch einmal absenden.');
        });
    })->create();
