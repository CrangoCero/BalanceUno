<?php

namespace App\Models;

use PDO;

class Balance extends BaseModel
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'incomes'); // Usa incomes como tabla principal heredada
    }

    public function getData(string $startDate, string $endDate): array
    {
        $companyId = $this->getCompanyId();

        // Ingresos
        $stmt = $this->db->prepare("SELECT * FROM incomes WHERE date BETWEEN :start AND :end AND company_id = :company_id AND deleted_at IS NULL");
        $stmt->execute([':start' => $startDate, ':end' => $endDate, ':company_id' => $companyId]);
        $incomes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gastos
        $stmt = $this->db->prepare("SELECT * FROM expenses WHERE date BETWEEN :start AND :end AND company_id = :company_id AND deleted_at IS NULL");
        $stmt->execute([':start' => $startDate, ':end' => $endDate, ':company_id' => $companyId]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Totales
        $totalIncomes = array_sum(array_column($incomes, 'amount'));
        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $netBalance = $totalIncomes - $totalExpenses;

        // Distribución por método de pago
        $paymentSummary = [
            'Ingresos' => [],
            'Gastos' => []
        ];

        foreach ($incomes as $income) {
            $method = $income['payment_method'];
            $paymentSummary['Ingresos'][$method] =
                ($paymentSummary['Ingresos'][$method] ?? 0) + $income['amount'];
        }

        foreach ($expenses as $expense) {
            $method = $expense['payment_method'];
            $paymentSummary['Gastos'][$method] =
                ($paymentSummary['Gastos'][$method] ?? 0) + $expense['amount'];
        }

        // Top 5 ingresos
        $topIncomes = $incomes;
        usort($topIncomes, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $topIncomes = array_slice($topIncomes, 0, 5);

        // Top 5 gastos
        $topExpenses = $expenses;
        usort($topExpenses, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $topExpenses = array_slice($topExpenses, 0, 5);

        return [
            'incomes' => $incomes,
            'expenses' => $expenses,
            'totalIncomes' => $totalIncomes,
            'totalExpenses' => $totalExpenses,
            'netBalance' => $netBalance,
            'paymentSummary' => $paymentSummary,
            'topIncomes' => $topIncomes,
            'topExpenses' => $topExpenses
        ];
    }
}
