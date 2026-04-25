<?php


use App\Core\Router;
use Dotenv\Dotenv;
use App\Controllers\SecurityController;
use App\Controllers\HoraireController;
use App\Controllers\AllergeneController;
use App\Controllers\RoleController;
use App\Controllers\ThemeController;
use App\Controllers\AvisController;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

header('Access-Control-Allow-Origin: *'); // mettre l'URL du frontend
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


$router = new Router($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);



// Route Horaire
$router->register('POST',   '/api/horaire/create', HoraireController::class, 'create');
$router->register('GET',      '/api/horaire/read',   HoraireController::class, 'read');
$router->register('PUT',    '/api/horaire/update', HoraireController::class, 'update');
$router->register('DELETE', '/api/horaire/delete', HoraireController::class, 'delete');

// Route Allergene
$router->register('POST',   '/api/allergene/create', AllergeneController::class, 'create');
$router->register('GET',      '/api/allergene/{id}',   AllergeneController::class, 'read');
$router->register('PUT',    '/api/allergene/{id}', AllergeneController::class, 'update');
$router->register('DELETE', '/api/allergene/{id}', AllergeneController::class, 'delete');

// Route Role
$router->register('POST',   '/api/role/create', RoleController::class, 'create');
$router->register('GET',      '/api/role/{id}',   RoleController::class, 'read');
$router->register('PUT',    '/api/role/{id}', RoleController::class, 'update');
$router->register('DELETE', '/api/role/{id}', RoleController::class, 'delete');

// Route Theme
$router->register('POST',   '/api/theme/create', ThemeController::class, 'create');
$router->register('GET',      '/api/theme/{id}',   ThemeController::class, 'read');
$router->register('PUT',    '/api/theme/{id}', ThemeController::class, 'update');
$router->register('DELETE', '/api/theme/{id}', ThemeController::class, 'delete');

// Route Utilisateur
$router->register('POST',   '/api/utilisateur/registration', SecurityController::class, 'registration');
$router->register('POST',   '/api/utilisateur/login', SecurityController::class, 'login');
$router->register('POST',   '/api/utilisateur/logout', SecurityController::class, 'logout');
$router->register('GET',      '/api/utilisateur/{id}',   SecurityController::class, 'read');
$router->register('PUT',    '/api/utilisateur/{id}', SecurityController::class, 'update');
$router->register('DELETE', '/api/utilisateur/{id}', SecurityController::class, 'delete');

// Route Avis
$router->register('POST',   '/api/avis/create', AvisController::class, 'create');
$router->register('GET',      '/api/avis/{id}',   AvisController::class, 'read');
$router->register('PUT',    '/api/avis/{id}', AvisController::class, 'update');
$router->register('DELETE', '/api/avis/{id}', AvisController::class, 'delete');

try {
    $router->run();
} catch (\RuntimeException $e) {
    $code = http_response_code();
    echo json_encode([
        'error' => match($code) {
            404 => 'Route introuvable',
            405 => 'Méthode non autorisée',
            default => $e->getMessage()
        }
    ]);
}


