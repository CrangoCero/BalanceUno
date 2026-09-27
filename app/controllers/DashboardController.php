<?php

namespace App\Controllers;

use App\Models\Dashboard;
use PDO;
use Throwable;

/**
 * Controlador del Dashboard
 *
 * Se encarga de coordinar la obtención de métricas financieras globales
 * y renderizar la vista principal del panel de administración.
 */
class DashboardController
{
    /**
     * @var Dashboard Instancia del modelo Dashboard
     */
    private Dashboard $model;

    /**
     * Constructor del controlador.
     *
     * @param PDO $db Instancia de conexión a la base de datos.
     */
    public function __construct(PDO $db)
    {
        $this->model = new Dashboard($db);
    }

    /**
     * Muestra la pantalla principal del Dashboard.
     * Consulta las métricas consolidadas y carga la vista correspondiente.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $data = $this->model->getData();
        } catch (Throwable $e) {
            error_log('Error en DashboardController::index: ' . $e->getMessage());
            $data = [
                'incomes'  => 0.0,
                'expenses' => 0.0,
                'balance'  => 0.0,
            ];
        }

        include __DIR__ . '/../../views/dashboard.php';
    }
}
