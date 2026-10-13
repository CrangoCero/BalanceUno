<?php

namespace App\Controllers;

use App\Models\Balance;
use PDO;
use DateTime;

class BalanceController
{
    private Balance $model;

    public function __construct(PDO $db)
    {
        $this->model = new Balance($db);
    }

    public function index($filters = [])
    {
        $startDateInput = $_POST['startDate'] ?? null;
        $endDateInput   = $_POST['endDate'] ?? null;

        if ($startDateInput && $endDateInput) {
            $startDate = DateTime::createFromFormat('d/m/Y', $startDateInput)->format('Y-m-d');
            $endDate   = DateTime::createFromFormat('d/m/Y', $endDateInput)->format('Y-m-d');
        } else {
            // Por defecto: solo la fecha actual
            $today = date('Y-m-d');
            $startDate = $today;
            $endDate   = $today;
        }

        $data = $this->model->getData($startDate, $endDate);
        include __DIR__ . '/../../views/balance.php';
    }

    public function exportXls()
    {
        $startDate = $_POST['startDate'] ?? date('Y-m-01');
        $endDate = $_POST['endDate'] ?? date('Y-m-t');
        $data = $this->model->getData($startDate, $endDate);

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=balance.xls");

        echo "Fecha\tTipo\tDescripción\tMonto\tMétodo\tCódigo\n";
        foreach ($data['incomes'] as $income) {
            echo "{$income['date']}\tIngreso\t{$income['description']}\t{$income['amount']}\t{$income['payment_method']}\t{$income['code']}\n";
        }
        foreach ($data['expenses'] as $expense) {
            echo "{$expense['date']}\tGasto\t{$expense['description']}\t-{$expense['amount']}\t{$expense['payment_method']}\t{$expense['code']}\n";
        }

        echo "\nResumen:\n";
        echo "Total Ingresos\t{$data['totalIncomes']}\n";
        echo "Total Gastos\t{$data['totalExpenses']}\n";
        echo "Balance Neto\t{$data['netBalance']}\n";

        echo "\nDistribución por método de pago:\n";
        foreach ($data['paymentSummary'] as $method => $value) {
            echo "{$method}\t{$value}\n";
        }

        echo "\nTop 5 Ingresos:\n";
        foreach ($data['topIncomes'] as $income) {
            echo "{$income['description']}\t{$income['amount']}\n";
        }

        echo "\nTop 5 Gastos:\n";
        foreach ($data['topExpenses'] as $expense) {
            echo "{$expense['description']}\t{$expense['amount']}\n";
        }
        exit;
    }
}
