<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny URI router. Supports exact paths and {id}-style placeholders.
 *
 * Patterns: /messages/{id}  -> matches /messages/42, captures id=42
 */
final class Router
{
    /** @var array<string, list<array{pattern: string, handler: array{0: class-string, 1: string}}>> */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    public function get(string $pattern, array $handler): void
    {
        $this->routes['GET'][] = ['pattern' => $pattern, 'handler' => $handler];
    }

    public function post(string $pattern, array $handler): void
    {
        $this->routes['POST'][] = ['pattern' => $pattern, 'handler' => $handler];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Trim trailing slash (except root)
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        $bucket = $this->routes[$method] ?? [];
        foreach ($bucket as $route) {
            $regex = $this->compile($route['pattern']);
            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter(
                    $matches,
                    static fn ($k) => !is_int($k),
                    ARRAY_FILTER_USE_KEY,
                );
                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->{$action}($params);
                return;
            }
        }

        // No match
        http_response_code(404);
        echo '<!doctype html><meta charset="utf-8"><title>404</title>'
           . '<h1>404 — Not found</h1><p><a href="/">Back to IntraBox</a></p>';
    }

    private function compile(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}
