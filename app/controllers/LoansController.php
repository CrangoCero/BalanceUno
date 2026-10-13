<?php

namespace App\Controllers;

use App\Models\Loan;
use App\Models\Expense;
use PDO;

class LoansController
{
    private Loan $model;
    private Expense $expenseModel;

    public function __construct(PDO $db)
    {
        $this->model = new Loan($db);
        $this->expenseModel = new Expense($db);
    }

    private function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    // Listar gastos
    public function index()
    {
        $loans = $this->model->getAll();
        include __DIR__ . '/../../views/loans.php';
    }

    // Obtener historial de pagos por AJAX
    public function getPayments(?int $id)
    {
        if (!$this->isAjax()) {
            header("Location: ?action=loans");
            exit;
        }

        try {
            if (!$id) {
                throw new \Exception("ID de préstamo no proporcionado");
            }
            $payments = $this->expenseModel->getPaymentsByLoanId($id);
            echo json_encode(['success' => true, 'data' => $payments]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Crear gasto
    public function create(array $data)
    {
        $data['loan'] = $this->generateLoanCode();
        $data['description'] = "Préstamo " . $data['loan'];

        if (empty($data['date'])) {
            $data['date'] = date('Y-m-d'); // formato ISO
        } else {
            $parts = explode('/', $data['date']);
            if (count($parts) === 3) {
                $data['date'] = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }

        // Recibir datos del formulario
        $amount = $data['amount'];

        // Normalizar: quitar puntos de miles y convertir coma en punto decimal
        $amount = str_replace('.', '', $amount);
        $amount = str_replace(',', '.', $amount);

        // Convertir a número
        $data['amount'] = (float) $amount;

        $this->model->create($data);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Préstamo registrado correctamente']);
            exit;
        }

        header("Location: ?action=incomes");
        exit;
    }


    // Editar gasto
    public function update(int $id, array $data)
    {
        $existing = $this->model->findById($id);

        if (!empty($data['date'])) {
            $parts = explode('/', $data['date']);
            if (count($parts) === 3) {
                $data['date'] = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        } else {
            $data['date'] = $existing['date'];
        }

        // Recibir datos del formulario
        $amount = $data['amount'];

        // Normalizar: quitar puntos de miles y convertir coma en punto decimal
        $amount = str_replace('.', '', $amount);
        $amount = str_replace(',', '.', $amount);

        // Convertir a número
        $data['amount'] = (float) $amount;

        $this->model->update($id, $data);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Préstamo actualizado correctamente']);
            exit;
        }

        header("Location: ?action=expenses");
        exit;
    }

    // Eliminar gasto (soft delete)
    public function delete(int $id)
    {
        $this->model->softDelete($id);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Préstamo eliminado correctamente']);
            exit;
        }

        header("Location: ?action=expenses");
        exit;
    }

    // Exportar a Excel
    public function exportXls()
    {
        $expenses = $this->model->getAll();
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=expenses.xls");

        echo "Fecha\tDescripción\tMonto\tMétodo\tCódigo\n";
        foreach ($expenses as $expense) {
            echo "{$expense['date']}\t{$expense['description']}\t{$expense['amount']}\t{$expense['payment_method']}\t{$expense['code']}\n";
        }
        exit;
    }

    private function generateLoanCode()
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    }
}
