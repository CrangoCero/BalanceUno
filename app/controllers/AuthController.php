<?php

namespace App\Controllers;

use App\Models\User;
use PDO;
use Throwable;

/**
 * Controlador de Autenticación
 *
 * Administra el flujo de inicio y cierre de sesión de usuarios,
 * la validación de credenciales con password_verify y la protección
 * contra fijación de sesión mediante session_regenerate_id.
 */
class AuthController
{
    /**
     * @var User Instancia del modelo User
     */
    private User $model;

    /**
     * Constructor del controlador de autenticación.
     *
     * @param PDO $db Conexión activa a la base de datos.
     */
    public function __construct(PDO $db)
    {
        $this->model = new User($db);
    }

    /**
     * Renderiza la vista del formulario de inicio de sesión.
     * Si el usuario ya tiene sesión iniciada, lo redirige al dashboard.
     *
     * @return void
     */
    public function loginView(): void
    {
        if (isset($_SESSION['user_id'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        include __DIR__ . '/../../views/login.php';
    }

    /**
     * Procesa la solicitud de inicio de sesión validando credenciales.
     *
     * @param array<string, mixed> $data Datos enviados por POST (username, password).
     * @return void
     */
    public function login(array $data): void
    {
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = "Usuario y contraseña son requeridos.";
            include __DIR__ . '/../../views/login.php';
            return;
        }

        try {
            $user = $this->model->findByUsername($username);

            if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                // Regenerar ID de sesión para prevenir fijación de sesión (Session Fixation)
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                $_SESSION['user_id']          = (int)$user['id'];
                $_SESSION['username']         = (string)$user['username'];
                $_SESSION['user_name']        = (string)($user['name'] ?? $user['username']);
                $_SESSION['company_id']       = (int)$user['company_id'];
                $_SESSION['company_name']     = (string)($user['company_name'] ?? '');
                $_SESSION['company_tax_rate'] = $user['company_tax_rate'] !== null ? (float)$user['company_tax_rate'] : null;

                header("Location: ?action=dashboard");
                exit;
            }

            $error = "Credenciales incorrectas.";
        } catch (Throwable $e) {
            error_log("Error en AuthController::login: " . $e->getMessage());
            $error = "Ocurrió un error al procesar el inicio de sesión. Intente nuevamente.";
        }

        include __DIR__ . '/../../views/login.php';
    }

    /**
     * Cierra la sesión activa del usuario, borra los datos de sesión y cookies,
     * y redirige a la pantalla de login.
     *
     * @return void
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            session_destroy();
        }

        header("Location: ?action=login");
        exit;
    }
}
