<?php
declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\EventController;
use App\Controllers\HomeController;
use App\Controllers\PageController;

/** @var App\Core\Router $router */

// --- Öffentliche Seiten --------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/programm', [EventController::class, 'index']);
$router->get('/programm/{kategorie:konzerte|partys|sonstiges}', [EventController::class, 'index']);
$router->get('/konzerte', [EventController::class, 'concerts']);
$router->get('/partys', [EventController::class, 'parties']);
$router->get('/event/{slug:[a-z0-9-]+}', [EventController::class, 'show']);
$router->get('/event/{slug:[a-z0-9-]+}/kalender.ics', [EventController::class, 'ics']);

$router->get('/location', [PageController::class, 'location']);
$router->get('/anfahrt', [PageController::class, 'directions']);
$router->get('/jobs', [PageController::class, 'jobs']);
$router->get('/impressum', [PageController::class, 'imprint']);
$router->get('/datenschutz', [PageController::class, 'privacy']);

// --- Admin -------------------------------------------------------------------------
$router->get('/admin/login', [Admin\AuthController::class, 'showLogin']);
$router->post('/admin/login', [Admin\AuthController::class, 'login']);
$router->post('/admin/logout', [Admin\AuthController::class, 'logout'], true);

$router->get('/admin', [Admin\DashboardController::class, 'index'], true);

$router->get('/admin/events', [Admin\EventController::class, 'index'], true);
$router->get('/admin/events/neu', [Admin\EventController::class, 'create'], true);
$router->post('/admin/events', [Admin\EventController::class, 'store'], true);
$router->get('/admin/events/{id:\d+}/bearbeiten', [Admin\EventController::class, 'edit'], true);
$router->post('/admin/events/{id:\d+}', [Admin\EventController::class, 'update'], true);
$router->post('/admin/events/{id:\d+}/loeschen', [Admin\EventController::class, 'destroy'], true);
$router->post('/admin/events/{id:\d+}/duplizieren', [Admin\EventController::class, 'duplicate'], true);
$router->post('/admin/events/{id:\d+}/status', [Admin\EventController::class, 'toggleStatus'], true);

$router->get('/admin/benutzer', [Admin\UserController::class, 'index'], true);
$router->post('/admin/benutzer', [Admin\UserController::class, 'store'], true);
$router->post('/admin/benutzer/{id:\d+}/loeschen', [Admin\UserController::class, 'destroy'], true);
$router->get('/admin/konto', [Admin\UserController::class, 'account'], true);
$router->post('/admin/konto', [Admin\UserController::class, 'updateAccount'], true);
