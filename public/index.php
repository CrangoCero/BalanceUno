<?php

/**
 * Front Controller principal de la aplicación BalanceUno.
 *
 * Punto de entrada único para todas las solicitudes HTTP.
 * Gestiona el autoloading, la sesión con protección de cookies,
 * la generación del token CSRF, las variables de entorno y el despacho del Router.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Configuración e inicio seguro de sesión
if (session_status() === PHP_SESSION_NONE) {
    // Proteger cookies de sesión contra accesos desde scripts cliente (mitigación XSS)
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

// Generar token CSRF criptográficamente seguro si no existe en la sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Carga tolerante a fallos de variables de entorno (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

use App\Router;

// Inicializar enrutador y despachar la acción solicitada (por defecto 'dashboard')
$router = new Router();
$action = $_GET['action'] ?? 'dashboard';
$router->handleRequest($action);
