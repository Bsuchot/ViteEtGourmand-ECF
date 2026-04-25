<?php

namespace App\Core;


class Router
{
    private array $routes = [];

    public function __construct(private string $method, private string $uri)
    {
        $this->uri = parse_url($uri, PHP_URL_PATH);
    }

    public function register(string|array $method, string $uri, string $controller, string $action)
    {
        if (!is_array($method)) {
            $method = [$method];
        }

        $this->routes[] = [
            'method'     => $method,
            'uri'        => $uri,
            'controller' => $controller,
            'action'     => $action
        ];
    }

    private function match(string $routeUri, string $requestUri, &$params = []): bool
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $routeUri);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $requestUri, $matches)) {
            $params = array_filter(
                $matches,
                fn($key) => is_string($key),
                ARRAY_FILTER_USE_KEY
            );
            return true;
        }

        return false;
    }

    public function run()
    {
        $uriMatched = false;

        foreach ($this->routes as $route) {
            $params = [];
            if (!$this->match($route['uri'], $this->uri, $params)) {
                continue;
            }

            $uriMatched = true;

            if (!in_array($this->method, $route['method'])) {
                continue;
            }

            $controller = $route['controller'];
            $action     = $route['action'];

            if (!class_exists($controller)) {
                throw new \LogicException('Le controller ' . $controller . ' n\'existe pas');
            }

            $controller = new $controller();

            if (!method_exists($controller, $action)) {
                throw new \LogicException('La méthode ' . $action . ' n\'existe pas dans le controller ' . $controller::class);
            }

            return $controller->$action(...array_values($params));
        }

        if ($uriMatched) {
            http_response_code(405);
            throw new \RuntimeException($this->method . ' n\'est pas autorisée pour cette URL');
        }

        http_response_code(404);
        throw new \RuntimeException('La route "' . $this->uri . '" n\'existe pas');
    }
    public function getRoutes(): array
    {
        return $this->routes;
    }
}