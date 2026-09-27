<?php

namespace App\Models;

use PDO;
use PDOException;
use Throwable;

/**
 * Clase Dashboard
 *
 * Gestiona el cálculo y consolidación de las métricas principales
 * (ingresos totales, gastos totales y balance neto) para el panel de control.
 */
class Dashboard extends BaseModel
{
    /**
     * Constructor del modelo Dashboard.
     *
     * @param PDO $db Instancia activa de conexión a la base de datos.
     */
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'incomes');
    }

    /**
     * Obtiene los totales consolidados de ingresos, gastos y balance neto
     * filtrados por la empresa en sesión y excluyendo registros eliminados.
     *
     * @return array{incomes: float, expenses: float, balance: float}
     */
    public function getData(): array
    {
        try {
            $companyId = $this->getCompanyId();

            // 1. Ingresos Totales
            $stmtIncomes = $this->db->prepare(
                "SELECT SUM(amount) AS total FROM incomes WHERE company_id = :company_id AND deleted_at IS NULL"
            );
            $stmtIncomes->execute([':company_id' => $companyId]);
            $rawIncomes = $stmtIncomes->fetchColumn();
            $incomes = (float)($rawIncomes !== false ? $rawIncomes : 0);

            // 2. Gastos Totales
            $stmtExpenses = $this->db->prepare(
                "SELECT SUM(amount) AS total FROM expenses WHERE company_id = :company_id AND deleted_at IS NULL"
            );
            $stmtExpenses->execute([':company_id' => $companyId]);
            $rawExpenses = $stmtExpenses->fetchColumn();
            $expenses = (float)($rawExpenses !== false ? $rawExpenses : 0);

            // 3. Balance Neto
            $balance = $incomes - $expenses;

            return [
                'incomes' => $incomes,
                'expenses' => $expenses,
                'balance' => $balance,
            ];
        } catch (PDOException $e) {
            error_log('Error PDO en Dashboard::getData: ' . $e->getMessage());
            return [
                'incomes' => 0.0,
                'expenses' => 0.0,
                'balance' => 0.0,
            ];
        } catch (Throwable $e) {
            error_log('Error general en Dashboard::getData: ' . $e->getMessage());
            return [
                'incomes' => 0.0,
                'expenses' => 0.0,
                'balance' => 0.0,
            ];
        }
    }
}
