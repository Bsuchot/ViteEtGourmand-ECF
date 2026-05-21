<?php

use App\Controllers\CommandeController;
use App\Controllers\ContactController;
use App\Controllers\MenuController;
use App\Controllers\PlatController;
use App\Controllers\RegimeController;
use App\Core\Router;
use Dotenv\Dotenv;
use App\Controllers\SecurityController;
use App\Controllers\HoraireController;
use App\Controllers\AllergeneController;
use App\Controllers\RoleController;
use App\Controllers\ThemeController;
use App\Controllers\AvisController;
use App\Controllers\EmployeController;

require_once __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__.'/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__.'/..');
    $dotenv->load();
}

$isProduction = !isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] === 'production';

session_set_cookie_params([
    'lifetime' => 86400,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();

$allowedOrigins = [
    'http://localhost:5500',
    'https://vite-et-gourmand-ecf.alwaysdata.net'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');
header('Content-Type: application/json');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data: blob:; connect-src 'self' http://localhost:5500 https://vite-et-gourmand-ecf.alwaysdata.net https://vite-et-gourmand-ecf-8adbd2933cc2.herokuapp.com https://geo.api.gouv.fr https://api-adresse.data.gouv.fr https://api.openrouteservice.org; frame-ancestors 'none';");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$router = new Router($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

//Route Admin
$router->register('POST', '/api/upload', SecurityController::class, 'upload');
$router->register('POST',   '/api/admin/employe/create',        EmployeController::class,  'create');
$router->register('GET',    '/api/admin/employe/readAll',        EmployeController::class,  'readAll');
$router->register('PUT',    '/api/admin/employe/update',         EmployeController::class,  'update');
$router->register('GET',    '/api/admin/employe/{id}',           EmployeController::class,  'read');
$router->register('PUT',    '/api/admin/employe/{id}/password',  EmployeController::class,  'updatePassword');
$router->register('DELETE', '/api/admin/employe/{id}',           EmployeController::class,  'delete');


// Route Allergene
$router->register('POST',   '/api/allergene/create',   AllergeneController::class, 'create');
$router->register('GET',    '/api/allergene/readAll',  AllergeneController::class, 'readAll');
$router->register('GET',    '/api/allergene/{id}',     AllergeneController::class, 'read');
$router->register('PUT',    '/api/allergene/{id}',     AllergeneController::class, 'update');
$router->register('DELETE', '/api/allergene/{id}',     AllergeneController::class, 'delete');

// Route Avis
$router->register('POST',   '/api/avis/create',                  AvisController::class, 'create');
$router->register('GET',    '/api/avis/readAll',                  AvisController::class, 'readAll');
$router->register('GET',    '/api/avis/{id}',                     AvisController::class, 'read');
$router->register('PUT',    '/api/avis/{id}',                     AvisController::class, 'update');
$router->register('PUT',    '/api/employe/avis/{id}/statut',      AvisController::class, 'updateStatut');
$router->register('DELETE', '/api/avis/{id}',                     AvisController::class, 'delete');

// Route Commande
$router->register('POST',   '/api/commande/create',                    CommandeController::class, 'create');
$router->register('POST',   '/api/commande/fraisLivraison',          CommandeController::class, 'calculerFrais');
$router->register('GET', '/api/commande/stats', CommandeController::class, 'stats');
$router->register('GET',    '/api/employe/commande/readAll',           CommandeController::class, 'readAll');
$router->register('GET',    '/api/commande/mesCommandes',                        CommandeController::class, 'readMyCommandes');
$router->register('GET', '/api/employe/commande/{id}', CommandeController::class, 'readById');
$router->register('GET',    '/api/commande/{id}',                      CommandeController::class, 'read');
$router->register('PUT',    '/api/commande/{id}',                      CommandeController::class, 'update');
$router->register('PUT',    '/api/employe/commande/{id}/statut',       CommandeController::class, 'updateStatut');
$router->register('DELETE', '/api/commande/{id}',                      CommandeController::class, 'delete');

// Route Contact
$router->register('POST', '/api/contact', ContactController::class, 'send');

// Route Horaire
$router->register('POST',   '/api/horaire/create',   HoraireController::class, 'create');
$router->register('GET',    '/api/horaire/readAll',  HoraireController::class, 'readAll');
$router->register('PUT',    '/api/horaire/update',   HoraireController::class, 'update');
$router->register('DELETE', '/api/horaire/{id}',     HoraireController::class, 'delete');

// Route Plat
$router->register('POST',   '/api/plat/create',   PlatController::class, 'create');
$router->register('GET',    '/api/plat/readAll',  PlatController::class, 'readAll');
$router->register('GET',    '/api/plat/{id}',     PlatController::class, 'read');
$router->register('PUT',    '/api/plat/{id}',     PlatController::class, 'update');
$router->register('DELETE', '/api/plat/{id}',     PlatController::class, 'delete');

// Route Menu
$router->register('POST',   '/api/menu/create',   MenuController::class, 'create');
$router->register('GET',    '/api/menu/readAll',  MenuController::class, 'readAll');
$router->register('GET',    '/api/menu/{id}',     MenuController::class, 'read');
$router->register('PUT',    '/api/menu/{id}',     MenuController::class, 'update');
$router->register('DELETE', '/api/menu/{id}',     MenuController::class, 'delete');

// Route Regime
$router->register('POST',   '/api/regime/create',   RegimeController::class, 'create');
$router->register('GET',    '/api/regime/readAll',  RegimeController::class, 'readAll');
$router->register('GET',    '/api/regime/{id}',     RegimeController::class, 'read');
$router->register('PUT',    '/api/regime/{id}',     RegimeController::class, 'update');
$router->register('DELETE', '/api/regime/{id}',     RegimeController::class, 'delete');

// Route Role
$router->register('POST',   '/api/role/create', RoleController::class, 'create');
$router->register('GET',    '/api/role/{id}',   RoleController::class, 'read');
$router->register('PUT',    '/api/role/{id}',   RoleController::class, 'update');
$router->register('DELETE', '/api/role/{id}',   RoleController::class, 'delete');

// Route Theme
$router->register('POST',   '/api/theme/create',   ThemeController::class, 'create');
$router->register('GET',    '/api/theme/readAll',  ThemeController::class, 'readAll');
$router->register('GET',    '/api/theme/{id}',     ThemeController::class, 'read');
$router->register('PUT',    '/api/theme/{id}',     ThemeController::class, 'update');
$router->register('DELETE', '/api/theme/{id}',     ThemeController::class, 'delete');

// Route Utilisateur
$router->register('POST',   '/api/utilisateur/registration',      SecurityController::class, 'registration');
$router->register('POST',   '/api/utilisateur/login',             SecurityController::class, 'login');
$router->register('GET', '/api/utilisateur/me', SecurityController::class, 'me');
$router->register('POST',   '/api/utilisateur/logout',            SecurityController::class, 'logout');
$router->register('POST', '/api/utilisateur/forgot-password', SecurityController::class, 'forgotPassword');
$router->register('POST', '/api/utilisateur/reset-password',  SecurityController::class, 'resetPassword');
$router->register('GET',    '/api/utilisateur/{id}',              SecurityController::class, 'read');
$router->register('PUT',    '/api/utilisateur/{id}',              SecurityController::class, 'update');
$router->register('PUT',    '/api/utilisateur/{id}/password',     SecurityController::class, 'updatePassword');
$router->register('DELETE', '/api/utilisateur/{id}',              SecurityController::class, 'delete');


//Roure crsf
$router->register('GET', '/api/csrf', SecurityController::class, 'csrf');

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


