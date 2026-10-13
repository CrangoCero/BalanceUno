<?php

namespace App\Models;

use PDO;

class Loan extends BaseModel
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'loans');
    }

    // Listar todos los préstamos con sus pagos (Se agrega : array para cumplir con BaseModel)
    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT l.id, l.loan, l.amount, l.date, l.payment_method, l.code, l.status,
               COALESCE(SUM(e.amount), 0) AS pagado,
               (l.amount - COALESCE(SUM(e.amount),0)) AS saldo
        FROM loans l
        LEFT JOIN expenses e 
               ON l.id = e.loan_id AND e.deleted_at IS NULL AND e.company_id = :company_id2
        WHERE l.deleted_at IS NULL AND l.company_id = :company_id
        GROUP BY l.id, l.loan, l.amount, l.payment_method, l.code, l.status
        ORDER BY l.created_at DESC;");
        $stmt->execute([
            ':company_id' => $this->getCompanyId(),
            ':company_id2' => $this->getCompanyId()
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Crear préstamo
    public function create(array $data): bool
    {
        $description = $data['description'];
        $companyId = $this->getCompanyId();

        $stmt = $this->db->prepare("INSERT INTO loans (company_id, loan, date, amount, payment_method, code) 
        VALUES (:company_id, :loan, :date, :amount, :payment_method, :code)");

        $stmt->execute([
            ':company_id'     => $companyId,
            ':loan'           => $data['loan'],
            ':date'           => $data['date'],
            ':amount'         => $data['amount'],
            ':payment_method' => $data['payment_method'],
            ':code'           => $data['code'] ?? null
        ]);

        $loanId = $this->db->lastInsertId();

        // Registrar también en incomes
        $stmt = $this->db->prepare("INSERT INTO incomes (company_id, date, description, amount, payment_method, code, loan_id) 
        VALUES (:company_id, :date, :description, :amount, :payment_method, :code, :loan_id);");

        return $stmt->execute([
            ':company_id'     => $companyId,
            ':date'           => $data['date'],
            ':description'    => $description,
            ':amount'         => $data['amount'],
            ':payment_method' => $data['payment_method'],
            ':code'           => $data['code'] ?? null,
            ':loan_id'        => $loanId
        ]);
    }

    // Actualizar préstamo
    public function update(int $id, array $data): bool
    {
        $companyId = $this->getCompanyId();

        // Validar que el nuevo monto no sea inferior a lo ya pagado
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
        );
        $stmt->execute([':loan_id' => $id, ':company_id' => $companyId]);
        $pagado = $stmt->fetchColumn() ?? 0;

        if ($data['amount'] < $pagado) {
            throw new \Exception("El nuevo monto del préstamo no puede ser menor a lo que ya se ha pagado ($pagado).");
        }

        $stmt = $this->db->prepare("UPDATE loans 
            SET date = :date, loan = :loan, amount = :amount, payment_method = :payment_method, code = :code
            WHERE id = :id AND company_id = :company_id");

        $result = $stmt->execute([
            ':id'             => $id,
            ':company_id'     => $companyId,
            ':date'           => $data['date'],
            ':loan'           => $data['loan'],
            ':amount'         => $data['amount'],
            ':payment_method' => $data['payment_method'],
            ':code'           => $data['code'] ?? null
        ]);

        if ($result) {
            $stmt = $this->db->prepare("UPDATE incomes 
                SET date = :date, amount = :amount, payment_method = :payment_method, code = :code
                WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL");
            $stmt->execute([
                ':loan_id'        => $id,
                ':company_id'     => $companyId,
                ':date'           => $data['date'],
                ':amount'         => $data['amount'],
                ':payment_method' => $data['payment_method'],
                ':code'           => $data['code'] ?? null
            ]);

            $this->updateLoanStatus($id);
        }

        return $result;
    }

    public function softDelete($id): bool
    {
        $companyId = $this->getCompanyId();

        // Verificar si tiene pagos asociados
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM expenses WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
        );
        $stmt->execute([':loan_id' => $id, ':company_id' => $companyId]);
        $pagos = $stmt->fetchColumn();

        if ($pagos > 0) {
            throw new \Exception("No se puede eliminar el préstamo porque ya tiene pagos registrados. Elimine los pagos primero.");
        }

        $result = parent::softDelete($id);

        if ($result) {
            $stmt = $this->db->prepare("UPDATE incomes SET deleted_at = NOW() WHERE loan_id = :loan_id AND company_id = :company_id");
            $stmt->execute([':loan_id' => $id, ':company_id' => $companyId]);
        }

        return $result;
    }

    private function updateLoanStatus(int $loanId)
    {
        $companyId = $this->getCompanyId();

        $stmt = $this->db->prepare("SELECT amount FROM loans WHERE id = :loan_id AND company_id = :company_id");
        $stmt->execute([':loan_id' => $loanId, ':company_id' => $companyId]);
        $prestado = $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
        );
        $stmt->execute([':loan_id' => $loanId, ':company_id' => $companyId]);
        $pagado = $stmt->fetchColumn() ?? 0;

        $nuevoPendiente = $prestado - $pagado;
        $nuevoEstado = ($nuevoPendiente <= 1) ? 'Pagado' : 'Pendiente';

        $stmt = $this->db->prepare("UPDATE loans SET status = :status WHERE id = :loan_id AND company_id = :company_id");
        $stmt->execute([':status' => $nuevoEstado, ':loan_id' => $loanId, ':company_id' => $companyId]);
    }

    public function loanPayment(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO expenses (company_id, date, description, amount, payment_method, code, loan_id) 
        VALUES (:company_id, :date, :description, :amount, :payment_method, :code, :loan_id)");

        return $stmt->execute([
            ':company_id'     => $this->getCompanyId(),
            ':date'           => $data['date'],
            ':description'    => $data['description'],
            ':amount'         => $data['amount'],
            ':payment_method' => $data['payment_method'],
            ':code'           => $data['code'] ?? null,
            ':loan_id'        => $data['loan_id']
        ]);
    }
}
