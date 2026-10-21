<?php
declare(strict_types=1);

/**
 * Front-Controller: alle Anfragen, die keine Datei treffen, landen hier.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\Session;
use App\Core\View;

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

Session::start();

$router = new Router();
require APP_PATH . '/routes.php';

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    if (config('app.debug')) {
        echo '<pre style="padding:2rem;font:14px/1.5 monospace;white-space:pre-wrap">' . e((string) $e) . '</pre>';
    } else {
        echo View::render('errors/500', ['title' => 'Fehler'], 'layouts/blank');
    }
}
