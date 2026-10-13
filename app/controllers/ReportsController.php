<?php

namespace App\Controllers;

use App\Models\Report;
use DateTime;
use PDO;

class ReportsController
{
    private Report $report;

    public function __construct(PDO $db)
    {
        $this->report = new Report($db);
    }

    public function index(array $filters = [])
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

        $totals = $this->report->getTotals($startDate, $endDate);
        $incomes = $this->report->getIncomes($startDate, $endDate);
        $expenses = $this->report->getExpenses($startDate, $endDate);
        $paymentSummary = $this->report->getPaymentSummary($startDate, $endDate);

        $data = [
            'totals' => $totals,
            'incomes' => $incomes,
            'expenses' => $expenses,
            'paymentSummary' => $paymentSummary
        ];

        include  __DIR__ . '/../../views/reports.php';
    }

    public function exportXls(array $filters)
    {
        $startDate = $filters['startDate'] ?? date('Y-m-01');
        $endDate   = $filters['endDate'] ?? date('Y-m-t');

        $incomes = $this->report->getIncomes($startDate, $endDate);
        $expenses = $this->report->getExpenses($startDate, $endDate);

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=reportes.xls");
        echo "Fecha\tTipo\tDescripción\tMonto\tMétodo\n";

        foreach ($incomes as $i) {
            echo "{$i['date']}\tIngreso\t{$i['description']}\t{$i['amount']}\t{$i['payment_method']}\n";
        }
        foreach ($expenses as $e) {
            echo "{$e['date']}\tGasto\t{$e['description']}\t{$e['amount']}\t{$e['payment_method']}\n";
        }
        exit;
    }
}
