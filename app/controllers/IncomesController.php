<?php

namespace App\Controllers;

use App\Models\Income;
use App\Models\Category;
use PDO;
use Throwable;

/**
 * Controlador de Ingresos (Incomes)
 *
 * Coordina la visualización del listado de ingresos, la creación, edición,
 * eliminación lógica y la exportación de reportes tabulares en formato Excel.
 */
class IncomesController
{
    /**
     * @var Income Instancia del modelo Income
     */
    private Income $model;

    /**
     * @var PDO Conexión activa a la base de datos MySQL
     */
    private PDO $db;

    /**
     * Constructor del controlador de ingresos.
     *
     * @param PDO $db Instancia de conexión a la base de datos.
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->model = new Income($db);
    }

    /**
     * Comprueba si la solicitud HTTP actual proviene de una llamada asíncrona (AJAX).
     *
     * @return bool True si es AJAX, False en caso contrario.
     */
    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }

    /**
     * Envía una respuesta de error en JSON si es AJAX o interrumpe con mensaje seguro si es HTTP convencional.
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
     * Envía una respuesta de éxito en formato JSON para peticiones AJAX.
     *
     * @param string $message Mensaje informativo de éxito.
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

        header("Location: ?action=incomes");
        exit;
    }

    /**
     * Despliega la vista principal del listado de ingresos.
     * Consulta ingresos y categorías de tipo 'ingreso' y 'ambos' para el selector.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $incomes = $this->model->getAll();

            // Cargar categorías disponibles para ingresos
            $categoryModel = new Category($this->db);
            $categories = $categoryModel->getByType('ingreso');

            // Mapear IDs de categoría a nombres para optimizar la visualización en tabla
            $categoriesMap = [];
            foreach ($categories as $cat) {
                $categoriesMap[$cat['id']] = $cat['name'];
            }
        } catch (Throwable $e) {
            error_log("Error en IncomesController::index: " . $e->getMessage());
            $incomes = [];
            $categories = [];
            $categoriesMap = [];
        }

        include __DIR__ . '/../../views/incomes.php';
    }

    /**
     * Procesa la creación de un nuevo ingreso validando campos y normalizando montos.
     *
     * @param array<string, mixed> $data Datos enviados por POST.
     * @return void
     */
    public function create(array $data): void
    {
        // 1. Validación de campos obligatorios
        $description = trim((string)($data['description'] ?? ''));
        $rawAmount   = trim((string)($data['amount'] ?? ''));
        $paymentMethod = trim((string)($data['payment_method'] ?? ''));

        if ($description === '' || $rawAmount === '' || $paymentMethod === '') {
            $this->respondError('Todos los campos obligatorios deben ser completados.');
        }

        // 2. Normalizar fecha (convertir de dd/mm/yyyy a yyyy-mm-dd)
        if (empty($data['date'])) {
            $data['date'] = date('Y-m-d');
        } else {
            $parts = explode('/', (string)$data['date']);
            if (count($parts) === 3) {
                $data['date'] = sprintf('%04d-%02d-%02d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
            }
        }

        // 3. Normalizar monto numérico (remover separadores de miles y sustituir coma por punto)
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
            $this->respondSuccess('Ingreso registrado correctamente.');
        } catch (Throwable $e) {
            error_log("Error en IncomesController::create: " . $e->getMessage());
            $this->respondError('Error al guardar el ingreso: ' . $e->getMessage());
        }
    }

    /**
     * Procesa la actualización de un ingreso existente.
     *
     * @param int|string $id Identificador del ingreso.
     * @param array<string, mixed> $data Datos enviados por POST.
     * @return void
     */
    public function update($id, array $data): void
    {
        $incomeId = (int)$id;
        if ($incomeId <= 0) {
            $this->respondError('Identificador de ingreso inválido.');
        }

        try {
            // Verificar existencia previa
            $existing = $this->model->findById($incomeId);
            if (!$existing) {
                $this->respondError('El ingreso no existe o fue eliminado.');
            }

            // Normalizar fecha si viene especificada
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

            $this->model->update($incomeId, $data);
            $this->respondSuccess('Ingreso actualizado correctamente.');
        } catch (Throwable $e) {
            error_log("Error en IncomesController::update [ID {$incomeId}]: " . $e->getMessage());
            $this->respondError($e->getMessage());
        }
    }

    /**
     * Procesa la eliminación lógica (soft delete) de un ingreso.
     *
     * @param int|string $id Identificador del ingreso a eliminar.
     * @return void
     */
    public function delete($id): void
    {
        $incomeId = (int)$id;
        if ($incomeId <= 0) {
            $this->respondError('Identificador de ingreso inválido.');
        }

        try {
            $this->model->softDelete($incomeId);
            $this->respondSuccess('Ingreso eliminado correctamente.');
        } catch (Throwable $e) {
            error_log("Error en IncomesController::delete [ID {$incomeId}]: " . $e->getMessage());
            $this->respondError($e->getMessage());
        }
    }

    /**
     * Exporta el listado completo de ingresos activos en formato tabular compatible con Excel (TSV).
     *
     * @return void
     */
    public function exportXls(): void
    {
        try {
            $incomes = $this->model->getAll();

            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=incomes_" . date('Ymd_His') . ".xls");
            header("Pragma: no-cache");
            header("Expires: 0");

            // Cabecera con codificación UTF-8 BOM para apertura directa en Excel sin caracteres extraños
            echo "\xEF\xBB\xBF";
            echo "Fecha\tDescripción\tMonto\tMétodo de Pago\tCódigo\tEstado Pago\n";

            foreach ($incomes as $income) {
                $fecha  = date('d/m/Y', strtotime($income['date']));
                $desc   = str_replace(["\t", "\r", "\n"], ' ', (string)$income['description']);
                $monto  = (float)$income['amount'];
                $metodo = (string)$income['payment_method'];
                $codigo = (string)($income['code'] ?? '-');
                $estado = (string)($income['payment_status'] ?? 'Pagado');

                echo "{$fecha}\t{$desc}\t{$monto}\t{$metodo}\t{$codigo}\t{$estado}\n";
            }
        } catch (Throwable $e) {
            error_log("Error en IncomesController::exportXls: " . $e->getMessage());
            $this->respondError('No fue posible generar la exportación en este momento.');
        }
        exit;
    }
}
