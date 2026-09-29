<?php

namespace App\Controllers;

use App\Models\Expense;
use App\Models\Category;
use PDO;
use Throwable;

/**
 * Controlador de Gastos (Expenses)
 *
 * Coordina la visualización de gastos, registro de gastos ordinarios,
 * abonos a préstamos, edición, borrado lógico y exportación a Excel.
 */
class ExpensesController
{
    /**
     * @var Expense Instancia del modelo Expense
     */
    private Expense $model;

    /**
     * @var PDO Conexión activa a la base de datos MySQL
     */
    private PDO $db;

    /**
     * Constructor del controlador de gastos.
     *
     * @param PDO $db Instancia de conexión a la base de datos.
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->model = new Expense($db);
    }

    /**
     * Comprueba si la solicitud actual proviene de una llamada AJAX.
     *
     * @return bool True si es AJAX, False en caso contrario.
     */
    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }

    /**
     * Envía una respuesta de error en JSON o interrumpe con mensaje seguro.
     *
     * @param string $message Mensaje descriptivo del error.
     * @param int $statusCode Código de estado HTTP (por defecto 400).
     * @return void
     */
    private function respondError(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);

        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            die(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        }
        exit;
    }

    /**
     * Envía una respuesta de éxito en formato JSON para AJAX o redirige.
     *
     * @param string $message Mensaje de éxito.
     * @return void
     */
    private function respondSuccess(string $message): void
    {
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        }

        header("Location: ?action=expenses");
        exit;
    }

    /**
     * Despliega la vista principal del listado de gastos, préstamos pendientes y categorías.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $expenses = $this->model->getAll();
            $loans    = $this->model->getLoans();

            // Cargar categorías disponibles para gastos
            $categoryModel = new Category($this->db);
            $categories    = $categoryModel->getByType('gasto');

            // Mapear categorías para visualización rápida en la tabla
            $categoriesMap = [];
            foreach ($categories as $cat) {
                $categoriesMap[$cat['id']] = $cat['name'];
            }
        } catch (Throwable $e) {
            error_log("Error en ExpensesController::index: " . $e->getMessage());
            $expenses      = [];
            $loans         = [];
            $categories    = [];
            $categoriesMap = [];
        }

        include __DIR__ . '/../../views/expenses.php';
    }

    /**
     * Procesa la creación de un nuevo gasto o abono a préstamo.
     *
     * @param array<string, mixed> $data Datos enviados por POST.
     * @return void
     */
    public function create(array $data): void
    {
        // 1. Validación de campos obligatorios
        $isLoanPayment = !empty($data['loan_id']);
        $description   = trim((string)($data['description'] ?? ''));
        $rawAmount     = trim((string)($data['amount'] ?? ''));
        $paymentMethod = trim((string)($data['payment_method'] ?? ''));

        if (!$isLoanPayment && $description === '') {
            $this->respondError('La descripción es obligatoria para gastos ordinarios.');
        }

        if ($rawAmount === '' || $paymentMethod === '') {
            $this->respondError('El monto y el método de pago son obligatorios.');
        }

        // 2. Normalizar fecha
        if (empty($data['date'])) {
            $data['date'] = date('Y-m-d');
        } else {
            $parts = explode('/', (string)$data['date']);
            if (count($parts) === 3) {
                $data['date'] = sprintf('%04d-%02d-%02d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
            }
        }

        // 3. Normalizar monto
        $cleanAmount = str_replace('.', '', $rawAmount);
        $cleanAmount = str_replace(',', '.', $cleanAmount);
        $data['amount'] = (float)$cleanAmount;

        if ($data['amount'] <= 0) {
            $this->respondError('El monto debe ser un valor positivo mayor que cero.');
        }

        $data['description']    = $description;
        $data['payment_method'] = $paymentMethod;

        // 4. Ejecutar creación
        try {
            $this->model->create($data);
            $msg = $isLoanPayment ? 'Abono al préstamo registrado correctamente.' : 'Gasto registrado correctamente.';
            $this->respondSuccess($msg);
        } catch (Throwable $e) {
            error_log("Error en ExpensesController::create: " . $e->getMessage());
            $this->respondError($e->getMessage());
        }
    }

    /**
     * Procesa la actualización de un gasto o abono existente.
     *
     * @param int|string $id Identificador del gasto.
     * @param array<string, mixed> $data Datos enviados por POST.
     * @return void
     */
    public function update($id, array $data): void
    {
        $expenseId = (int)$id;
        if ($expenseId <= 0) {
            $this->respondError('Identificador de gasto inválido.');
        }

        try {
            // Verificar existencia
            $existing = $this->model->findById($expenseId);
            if (!$existing) {
                $this->respondError('El gasto no existe o fue eliminado.');
            }

            // Normalizar fecha si viene en la petición
            if (!empty($data['date'])) {
                $parts = explode('/', (string)$data['date']);
                if (count($parts) === 3) {
                    $data['date'] = sprintf('%04d-%02d-%02d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
                }
            } else {
                $data['date'] = $existing['date'];
            }

            // Normalizar monto
            $rawAmount = (string)($data['amount'] ?? '');
            $cleanAmount = str_replace('.', '', $rawAmount);
            $cleanAmount = str_replace(',', '.', $cleanAmount);
            $data['amount'] = (float)$cleanAmount;

            if ($data['amount'] <= 0) {
                $this->respondError('El monto debe ser un valor positivo mayor que cero.');
            }

            $this->model->update($expenseId, $data);
            $this->respondSuccess('Gasto actualizado correctamente.');
        } catch (Throwable $e) {
            error_log("Error en ExpensesController::update [ID {$expenseId}]: " . $e->getMessage());
            $this->respondError($e->getMessage());
        }
    }

    /**
     * Procesa la eliminación lógica (soft delete) de un gasto.
     *
     * @param int|string $id Identificador del gasto.
     * @return void
     */
    public function delete($id): void
    {
        $expenseId = (int)$id;
        if ($expenseId <= 0) {
            $this->respondError('Identificador de gasto inválido.');
        }

        try {
            $this->model->softDelete($expenseId);
            $this->respondSuccess('Gasto eliminado correctamente.');
        } catch (Throwable $e) {
            error_log("Error en ExpensesController::delete [ID {$expenseId}]: " . $e->getMessage());
            $this->respondError($e->getMessage());
        }
    }

    /**
     * Exporta el listado completo de gastos activos en formato tabular compatible con Excel (TSV).
     *
     * @return void
     */
    public function exportXls(): void
    {
        try {
            $expenses = $this->model->getAll();

            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=expenses_" . date('Ymd_His') . ".xls");
            header("Pragma: no-cache");
            header("Expires: 0");

            // BOM UTF-8 para apertura directa en Excel
            echo "\xEF\xBB\xBF";
            echo "Fecha\tDescripción\tMonto\tMétodo de Pago\tCódigo\tEstado Pago\n";

            foreach ($expenses as $expense) {
                $fecha  = date('d/m/Y', strtotime($expense['date']));
                $desc   = str_replace(["\t", "\r", "\n"], ' ', (string)$expense['description']);
                $monto  = (float)$expense['amount'];
                $metodo = (string)$expense['payment_method'];
                $codigo = (string)($expense['code'] ?? '-');
                $estado = (string)($expense['payment_status'] ?? 'Pagado');

                echo "{$fecha}\t{$desc}\t{$monto}\t{$metodo}\t{$codigo}\t{$estado}\n";
            }
        } catch (Throwable $e) {
            error_log("Error en ExpensesController::exportXls: " . $e->getMessage());
            $this->respondError('No fue posible generar la exportación de gastos.');
        }
        exit;
    }
}
