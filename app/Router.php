<?php

namespace App;

use App\Config\Database;
use PDO;
use Throwable;

/**
 * Clase Router
 *
 * Enrutador y despachador central de peticiones HTTP.
 * Valida la autenticación del usuario, la integridad del token CSRF en peticiones POST,
 * resuelve la correspondencia de rutas hacia controladores y métodos específicos,
 * y suministra la instancia activa de base de datos a los controladores.
 */
class Router
{
    /**
     * @var PDO Conexión activa a la base de datos MySQL
     */
    private PDO $db;

    /**
     * Tabla de mapeo de rutas registradas en la aplicación.
     * Cada entrada define el controlador, método y parámetros esperados.
     *
     * @var array<string, array{controller: string, method: string, params?: array<int, string>}>
     */
    private array $routes = [
        'login'            => ['controller' => 'App\Controllers\AuthController', 'method' => 'loginView'],
        'doLogin'          => ['controller' => 'App\Controllers\AuthController', 'method' => 'login', 'params' => ['$_POST']],
        'logout'           => ['controller' => 'App\Controllers\AuthController', 'method' => 'logout'],

        'dashboard' => ['controller' => 'App\Controllers\DashboardController', 'method' => 'index'],

        // Incomes
        'incomes'          => ['controller' => 'App\Controllers\IncomesController', 'method' => 'index'],
        'createIncome'     => ['controller' => 'App\Controllers\IncomesController', 'method' => 'create', 'params' => ['$_POST']],
        'updateIncome'     => ['controller' => 'App\Controllers\IncomesController', 'method' => 'update', 'params' => ['$_POST[id]', '$_POST']],
        'deleteIncome'     => ['controller' => 'App\Controllers\IncomesController', 'method' => 'delete', 'params' => ['$_POST[id]']],
        'exportIncomesXls' => ['controller' => 'App\Controllers\IncomesController', 'method' => 'exportXls'],

        // Expenses
        'expenses'          => ['controller' => 'App\Controllers\ExpensesController', 'method' => 'index'],
        'createExpense'     => ['controller' => 'App\Controllers\ExpensesController', 'method' => 'create', 'params' => ['$_POST']],
        'updateExpense'     => ['controller' => 'App\Controllers\ExpensesController', 'method' => 'update', 'params' => ['$_POST[id]', '$_POST']],
        'deleteExpense'     => ['controller' => 'App\Controllers\ExpensesController', 'method' => 'delete', 'params' => ['$_POST[id]']],
        'exportExpensesXls' => ['controller' => 'App\Controllers\ExpensesController', 'method' => 'exportXls'],

        // Balance
        'balance'          => ['controller' => 'App\Controllers\BalanceController', 'method' => 'index', 'params' => ['$_POST']],
        'exportBalanceXls' => ['controller' => 'App\Controllers\BalanceController', 'method' => 'exportXls', 'params' => ['$_POST']],

        // Reports
        'reports'          => ['controller' => 'App\Controllers\ReportsController', 'method' => 'index', 'params' => ['$_POST']],
        'exportReportsXls' => ['controller' => 'App\Controllers\ReportsController', 'method' => 'exportXls', 'params' => ['$_POST']],

        // Loans
        'loans'          => ['controller' => 'App\Controllers\LoansController', 'method' => 'index'],
        'createLoan'     => ['controller' => 'App\Controllers\LoansController', 'method' => 'create', 'params' => ['$_POST']],
        'updateLoan'     => ['controller' => 'App\Controllers\LoansController', 'method' => 'update', 'params' => ['$_POST[id]', '$_POST']],
        'deleteLoan'     => ['controller' => 'App\Controllers\LoansController', 'method' => 'delete', 'params' => ['$_POST[id]']],
        'exportLoansXls' => ['controller' => 'App\Controllers\LoansController', 'method' => 'exportXls'],
        'getLoanPayments' => ['controller' => 'App\Controllers\LoansController', 'method' => 'getPayments', 'params' => ['$_GET[id]']],

        // Categories
        'categories'        => ['controller' => 'App\Controllers\CategoriesController', 'method' => 'index'],
        'createCategory'    => ['controller' => 'App\Controllers\CategoriesController', 'method' => 'create', 'params' => ['$_POST']],
        'updateCategory'    => ['controller' => 'App\Controllers\CategoriesController', 'method' => 'update', 'params' => ['$_POST[id]', '$_POST']],
        'deleteCategory'    => ['controller' => 'App\Controllers\CategoriesController', 'method' => 'delete', 'params' => ['$_POST[id]']],

        // Inventory
        'inventory'          => ['controller' => 'App\Controllers\InventoryController', 'method' => 'index'],
        'createProduct'      => ['controller' => 'App\Controllers\InventoryController', 'method' => 'create', 'params' => ['$_POST']],
        'updateProduct'      => ['controller' => 'App\Controllers\InventoryController', 'method' => 'update', 'params' => ['$_POST[id]', '$_POST']],
        'deleteProduct'      => ['controller' => 'App\Controllers\InventoryController', 'method' => 'delete', 'params' => ['$_POST[id]']],
        'adjustStock'        => ['controller' => 'App\Controllers\InventoryController', 'method' => 'adjustStock', 'params' => ['$_POST']],
        'getProductMovements' => ['controller' => 'App\Controllers\InventoryController', 'method' => 'getMovements', 'params' => ['$_GET[id]']],
        'exportInventoryXls' => ['controller' => 'App\Controllers\InventoryController', 'method' => 'exportXls'],

        // Orders
        'orders'             => ['controller' => 'App\Controllers\OrdersController', 'method' => 'index'],
        'createOrder'        => ['controller' => 'App\Controllers\OrdersController', 'method' => 'create', 'params' => ['$_POST']],
        'updateOrder'        => ['controller' => 'App\Controllers\OrdersController', 'method' => 'update', 'params' => ['$_POST[id]', '$_POST']],
        'deleteOrder'        => ['controller' => 'App\Controllers\OrdersController', 'method' => 'delete', 'params' => ['$_POST[id]']],
        'updateOrderStatus'  => ['controller' => 'App\Controllers\OrdersController', 'method' => 'updateStatus', 'params' => ['$_POST']],
        'getOrderItems'      => ['controller' => 'App\Controllers\OrdersController', 'method' => 'getItems', 'params' => ['$_GET[id]']],
        'exportOrdersXls'    => ['controller' => 'App\Controllers\OrdersController', 'method' => 'exportXls'],
    ];

    /**
     * Constructor del Router.
     * Inicializa la conexión a la base de datos a través de Database::getConnection().
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Procesa la solicitud HTTP entrante y ejecuta el controlador correspondiente.
     *
     * @param string $action Nombre de la acción/ruta a despachar.
     * @return void
     */
    public function handleRequest(string $action): void
    {
        // 1. Definir rutas que no requieren sesión de usuario activa
        $publicActions = ['login', 'doLogin'];

        // Si no está autenticado y no es una ruta pública, redirigir al login
        if (!isset($_SESSION['user_id']) && !in_array($action, $publicActions, true)) {
            header("Location: ?action=login");
            exit;
        }

        // 2. Validación CSRF para todas las peticiones con método POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submittedToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $sessionToken   = $_SESSION['csrf_token'] ?? '';

            if (empty($submittedToken) || empty($sessionToken) || !hash_equals($sessionToken, $submittedToken)) {
                $this->handleError('Error de seguridad: Token CSRF inválido o expirado.');
            }
        }

        // 3. Si la acción solicitada no existe en la tabla de rutas, redirigir por defecto al dashboard
        if (!array_key_exists($action, $this->routes)) {
            $action = 'dashboard';
        }

        $route = $this->routes[$action];
        $controllerName = $route['controller'];
        $methodName = $route['method'];

        // 4. Validar existencia del controlador y método
        if (!class_exists($controllerName)) {
            $this->handleError("Error interno: El controlador '{$controllerName}' no existe.");
        }

        // Instanciar el controlador inyectando la conexión a la base de datos
        $controller = new $controllerName($this->db);

        if (!method_exists($controller, $methodName)) {
            $this->handleError("Error interno: El método '{$methodName}' no existe en '{$controllerName}'.");
        }

        // 5. Resolver argumentos configurados en la ruta
        $args = [];
        if (isset($route['params']) && is_array($route['params'])) {
            foreach ($route['params'] as $param) {
                if ($param === '$_POST') {
                    $args[] = $_POST;
                } elseif ($param === '$_POST[id]') {
                    $args[] = $_POST['id'] ?? null;
                } elseif ($param === '$_GET[id]') {
                    $args[] = $_GET['id'] ?? null;
                }
            }
        }

        // 6. Ejecutar el método del controlador capturando posibles errores no controlados
        try {
            call_user_func_array([$controller, $methodName], $args);
        } catch (Throwable $e) {
            error_log("Excepción no controlada en Router [{$action} -> {$controllerName}@{$methodName}]: " . $e->getMessage());
            $this->handleError($e->getMessage());
        }
    }

    /**
     * Gestiona las respuestas de error distinguiendo peticiones AJAX de peticiones estándar.
     *
     * @param string $message Mensaje descriptivo del error.
     * @return void
     */
    private function handleError(string $message): void
    {
        // Detectar si la petición proviene de AJAX
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            // Mostrar error amigable en navegación normal
            http_response_code(400);
            die(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        }
        exit;
    }
}
