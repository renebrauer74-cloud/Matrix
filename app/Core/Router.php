<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:array, auth:bool}> */
    private array $routes = [];

    public function get(string $pattern, array $handler, bool $auth = false): void
    {
        $this->add('GET', $pattern, $handler, $auth);
    }

    public function post(string $pattern, array $handler, bool $auth = false): void
    {
        $this->add('POST', $pattern, $handler, $auth);
    }

    private function add(string $method, string $pattern, array $handler, bool $auth): void
    {
        // {id:\d+} oder {slug}
        $regex = preg_replace_callback('#\{(\w+)(?::([^}]+))?\}#', static function ($m) {
            return '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')';
        }, rtrim($pattern, '/') ?: '/');

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#u',
            'handler' => $handler,
            'auth'    => $auth,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        $methodMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $methodMatched = true;
                continue;
            }

            if ($route['auth'] && !Auth::check()) {
                Session::put('intended', $_SERVER['REQUEST_URI'] ?? '/admin');
                redirect('admin/login');
            }
            if ($method === 'POST' && !Csrf::verify($_POST['_token'] ?? null)) {
                http_response_code(419);
                echo View::render('errors/419', [], 'layouts/blank');
                return;
            }

            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $route['handler'];
            (new $class())->$action(...$params);
            return;
        }

        http_response_code($methodMatched ? 405 : 404);
        echo View::render('errors/404', ['title' => 'Seite nicht gefunden'], 'layouts/site');
    }
}
