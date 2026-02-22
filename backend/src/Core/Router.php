<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    // Enregistrer une route
    public function add(string $method, string $path, callable $callback): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback
        ];
    }

    // Traiter la requête
    public function run(): void
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $route['path'] === $requestUri) {
                call_user_func($route['callback']);
                return;
            }
        }

        // Route non trouvée
        http_response_code(404);
        echo json_encode(['message' => 'Not Found']);
    }
}

