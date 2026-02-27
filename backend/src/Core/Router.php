<?php

namespace App\Core;

class Router {

    public static function dispatch(): void {

        $url = $_GET['url'] ?? '';
        $url = explode('/', trim($url, '/'));

        $controllerName = !empty($url[0])
            ? "App\\Controllers\\" . ucfirst($url[0]) . "Controller"
            : "App\\Controllers\\UserController";

        $method = $url[1] ?? "index";

        if (class_exists($controllerName)) {

            $controller = new $controllerName();

            if (method_exists($controller, $method)) {
                $controller->$method();
                return;
            }
        }

        http_response_code(404);
        echo json_encode(["error" => "Route non trouvée"]);
    }
}