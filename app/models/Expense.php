<?php

namespace App\Models;

use Exception;
use PDO;
use Throwable;

/**
 * Modelo de Gastos (Expenses)
 *
 * Administra el registro, edición, eliminación lógica y cálculo de gastos,
 * integrando la sincronización transaccional con amortizaciones de préstamos.
 */
class Expense extends BaseModel
{
    /**
     * Constructor del modelo Expense.
     *
     * @param PDO $db Instancia activa de conexión a la base de datos.
     */
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'expenses');
    }

    /**
     * Obtiene el historial de pagos asociados a un préstamo específico de la empresa.
     *
     * @param int|string $loanId Identificador del préstamo.
     * @return array<int, array<string, mixed>> Lista de pagos registrados.
     */
    public function getPaymentsByLoanId($loanId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM expenses 
                 WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL 
                 ORDER BY date DESC, id DESC"
            );
            $stmt->execute([
                ':loan_id'    => (int)$loanId,
                ':company_id' => $this->getCompanyId()
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Error en Expense::getPaymentsByLoanId [Loan ID {$loanId}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registra un nuevo gasto o abono a préstamo con validación de saldo y control transaccional.
     *
     * @param array<string, mixed> $data Datos del gasto a registrar.
     * @return bool True si se registró con éxito.
     * @throws Exception Si el préstamo no existe o el abono supera el saldo pendiente.
     */
    public function create(array $data): bool
    {
        $companyId = $this->getCompanyId();

        // 1. Caso: Abono / Pago a Préstamo
        if (!empty($data['loan_id'])) {
            $loanId = (int)$data['loan_id'];
            $amount = (float)$data['amount'];

            $this->db->beginTransaction();

            try {
                // Obtener datos del préstamo activo
                $stmt = $this->db->prepare(
                    "SELECT loan, amount FROM loans 
                     WHERE id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
                );
                $stmt->execute([':loan_id' => $loanId, ':company_id' => $companyId]);
                $loanData = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$loanData) {
                    throw new Exception("El préstamo seleccionado no existe o pertenece a otra empresa.");
                }

                $loanName  = (string)$loanData['loan'];
                $prestado  = (float)$loanData['amount'];

                $data['description'] = "Pago Préstamo " . $loanName;

                // Total pagado hasta la fecha
                $stmtPaid = $this->db->prepare(
                    "SELECT COALESCE(SUM(amount), 0) FROM expenses 
                     WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
                );
                $stmtPaid->execute([':loan_id' => $loanId, ':company_id' => $companyId]);
                $pagadoActual = (float)($stmtPaid->fetchColumn() ?: 0);

                $pendiente = $prestado - $pagadoActual;

                // Validación de sobrepago con tolerancia de centavos
                if ($amount > ($pendiente + 0.01)) {
                    $formatoPendiente = number_format($pendiente, 0, ',', '.');
                    throw new Exception("El abono ($" . number_format($amount, 0, ',', '.') . ") no puede ser superior al saldo pendiente ($" . $formatoPendiente . " COP).");
                }

                // Insertar el gasto vinculado al préstamo
                $stmtInsert = $this->db->prepare(
                    "INSERT INTO expenses (company_id, date, description, amount, payment_method, code, loan_id, payment_status) 
                     VALUES (:company_id, :date, :description, :amount, :payment_method, :code, :loan_id, :payment_status)"
                );
                $stmtInsert->execute([
                    ':company_id'      => $companyId,
                    ':date'            => $data['date'],
                    ':description'     => $data['description'],
                    ':amount'          => $amount,
                    ':payment_method'  => $data['payment_method'],
                    ':code'            => !empty($data['code']) ? $data['code'] : null,
                    ':loan_id'         => $loanId,
                    ':payment_status'  => 'Pagado'
                ]);

                // Recalcular saldo y actualizar estado del préstamo
                $nuevoTotalPagado = $pagadoActual + $amount;
                $nuevoPendiente   = $prestado - $nuevoTotalPagado;
                $nuevoEstado      = ($nuevoPendiente <= 1) ? 'Pagado' : 'Pendiente';

                $stmtUpdateLoan = $this->db->prepare(
                    "UPDATE loans SET status = :status WHERE id = :loan_id AND company_id = :company_id"
                );
                $stmtUpdateLoan->execute([
                    ':status'     => $nuevoEstado,
                    ':loan_id'    => $loanId,
                    ':company_id' => $companyId
                ]);

                $this->db->commit();
                return true;
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                error_log("Error en Expense::create (Préstamo) [Loan ID {$loanId}]: " . $e->getMessage());
                throw $e;
            }
        }

        // 2. Caso: Gasto Ordinario
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO expenses (company_id, date, description, amount, payment_method, code, category_id, payment_status)
                 VALUES (:company_id, :date, :description, :amount, :payment_method, :code, :category_id, :payment_status)"
            );

            return $stmt->execute([
                ':company_id'      => $companyId,
                ':date'            => $data['date'],
                ':description'     => $data['description'],
                ':amount'          => (float)$data['amount'],
                ':payment_method'  => $data['payment_method'],
                ':code'            => !empty($data['code']) ? $data['code'] : null,
                ':category_id'     => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                ':payment_status'  => $data['payment_status'] ?? 'Pagado'
            ]);
        } catch (Throwable $e) {
            error_log("Error en Expense::create (Ordinario): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza un gasto existente, gestionando cambios de préstamo o modificaciones ordinarias de forma transaccional.
     *
     * @param int|string $id Identificador del gasto.
     * @param array<string, mixed> $data Datos modificados.
     * @return bool True si se actualizó con éxito.
     * @throws Exception Si el registro no existe o los montos exceden el saldo restante del préstamo.
     */
    public function update($id, array $data): bool
    {
        $existing = $this->findById($id);
        if (!$existing) {
            throw new Exception("El gasto no existe o no pertenece a su empresa.");
        }

        $companyId = $this->getCompanyId();
        $oldLoanId = !empty($existing['loan_id']) ? (int)$existing['loan_id'] : null;
        $newLoanId = !empty($data['loan_id']) ? (int)$data['loan_id'] : $oldLoanId;

        $this->db->beginTransaction();

        try {
            if ($oldLoanId) {
                $newAmount = (float)$data['amount'];

                if ($newLoanId !== $oldLoanId) {
                    // Caso A: Se reasignó el pago a otro préstamo
                    $stmt = $this->db->prepare(
                        "SELECT loan, amount FROM loans WHERE id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
                    );
                    $stmt->execute([':loan_id' => $newLoanId, ':company_id' => $companyId]);
                    $newLoanData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$newLoanData) {
                        throw new Exception("El nuevo préstamo seleccionado no existe.");
                    }

                    $data['description'] = "Pago Préstamo " . $newLoanData['loan'];
                    $prestado = (float)$newLoanData['amount'];

                    $stmtPaid = $this->db->prepare(
                        "SELECT COALESCE(SUM(amount), 0) FROM expenses 
                         WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
                    );
                    $stmtPaid->execute([':loan_id' => $newLoanId, ':company_id' => $companyId]);
                    $pagado = (float)($stmtPaid->fetchColumn() ?: 0);

                    $pendiente = $prestado - $pagado;
                    if ($newAmount > ($pendiente + 0.01)) {
                        throw new Exception("El nuevo monto ($" . number_format($newAmount, 0, ',', '.') . ") no puede ser superior al saldo pendiente del nuevo préstamo ($" . number_format($pendiente, 0, ',', '.') . " COP).");
                    }
                } else {
                    // Caso B: Modificación del mismo préstamo
                    $data['description'] = $existing['description'];

                    $stmt = $this->db->prepare(
                        "SELECT amount FROM loans WHERE id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
                    );
                    $stmt->execute([':loan_id' => $oldLoanId, ':company_id' => $companyId]);
                    $prestado = (float)($stmt->fetchColumn() ?: 0);

                    // Pagado excluyendo este mismo registro
                    $stmtPaidOthers = $this->db->prepare(
                        "SELECT COALESCE(SUM(amount), 0) FROM expenses 
                         WHERE loan_id = :loan_id AND id != :id AND company_id = :company_id AND deleted_at IS NULL"
                    );
                    $stmtPaidOthers->execute([':loan_id' => $oldLoanId, ':id' => (int)$id, ':company_id' => $companyId]);
                    $pagadoOtros = (float)($stmtPaidOthers->fetchColumn() ?: 0);

                    $pendienteSinEstePago = $prestado - $pagadoOtros;

                    if ($newAmount > ($pendienteSinEstePago + 0.01)) {
                        throw new Exception("El nuevo monto ($" . number_format($newAmount, 0, ',', '.') . ") no puede ser superior al saldo restante del préstamo ($" . number_format($pendienteSinEstePago, 0, ',', '.') . " COP).");
                    }
                }

                // Actualizar registro en expenses como pago de préstamo
                $stmtUpdate = $this->db->prepare(
                    "UPDATE expenses 
                     SET date = :date, description = :description, amount = :amount, payment_method = :payment_method, code = :code, loan_id = :loan_id
                     WHERE id = :id AND company_id = :company_id"
                );
                $result = $stmtUpdate->execute([
                    ':id'             => (int)$id,
                    ':company_id'     => $companyId,
                    ':date'           => $data['date'],
                    ':description'    => $data['description'],
                    ':amount'         => (float)$data['amount'],
                    ':payment_method' => $data['payment_method'],
                    ':code'           => !empty($data['code']) ? $data['code'] : null,
                    ':loan_id'        => $newLoanId
                ]);

                // Actualizar el estado de los préstamos involucrados
                $this->updateLoanStatus($oldLoanId);
                if ($newLoanId !== $oldLoanId) {
                    $this->updateLoanStatus($newLoanId);
                }
            } else {
                // Caso Gasto Ordinario
                $stmtUpdate = $this->db->prepare(
                    "UPDATE expenses 
                     SET date = :date, description = :description, amount = :amount, payment_method = :payment_method, 
                         code = :code, category_id = :category_id, payment_status = :payment_status
                     WHERE id = :id AND company_id = :company_id"
                );
                $result = $stmtUpdate->execute([
                    ':id'             => (int)$id,
                    ':company_id'     => $companyId,
                    ':date'           => $data['date'],
                    ':description'    => $data['description'],
                    ':amount'         => (float)$data['amount'],
                    ':payment_method' => $data['payment_method'],
                    ':code'           => !empty($data['code']) ? $data['code'] : null,
                    ':category_id'    => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                    ':payment_status' => $data['payment_status'] ?? 'Pagado'
                ]);
            }

            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en Expense::update [ID {$id}]: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Realiza un borrado lógico (soft delete) del gasto y sincroniza el estado del préstamo si aplica.
     *
     * @param int|string $id Identificador del gasto.
     * @return bool True si se eliminó con éxito.
     */
    public function softDelete($id): bool
    {
        $existing = $this->findById($id);
        if (!$existing) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $result = parent::softDelete($id);

            if (!empty($existing['loan_id']) && $result) {
                $this->updateLoanStatus((int)$existing['loan_id']);
            }

            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en Expense::softDelete [ID {$id}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recalcula el saldo restante y actualiza el estado de un préstamo (Pagado / Pendiente).
     *
     * @param int $loanId Identificador del préstamo.
     * @return void
     */
    private function updateLoanStatus(int $loanId): void
    {
        $companyId = $this->getCompanyId();

        $stmt = $this->db->prepare(
            "SELECT amount FROM loans WHERE id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
        );
        $stmt->execute([':loan_id' => $loanId, ':company_id' => $companyId]);
        $prestado = (float)($stmt->fetchColumn() ?: 0);

        $stmtPaid = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM expenses 
             WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
        );
        $stmtPaid->execute([':loan_id' => $loanId, ':company_id' => $companyId]);
        $pagado = (float)($stmtPaid->fetchColumn() ?: 0);

        $nuevoPendiente = $prestado - $pagado;
        $nuevoEstado = ($nuevoPendiente <= 1.0) ? 'Pagado' : 'Pendiente';

        $stmtUpdate = $this->db->prepare(
            "UPDATE loans SET status = :status WHERE id = :loan_id AND company_id = :company_id"
        );
        $stmtUpdate->execute([':status' => $nuevoEstado, ':loan_id' => $loanId, ':company_id' => $companyId]);
    }

    /**
     * Obtiene el listado de préstamos pendientes para la empresa activa,
     * calculando montos prestados, acumulados pagados y saldos pendientes.
     *
     * @return array<int, array<string, mixed>> Lista de préstamos con saldos calculados.
     */
    public function getLoans(): array
    {
        try {
            $companyId = $this->getCompanyId();

            $stmt = $this->db->prepare(
                "SELECT l.id, l.loan, l.amount, l.payment_method, l.code,
                        COALESCE(SUM(e.amount), 0) AS pagado,
                        (l.amount - COALESCE(SUM(e.amount), 0)) AS pendiente
                 FROM loans l
                 LEFT JOIN expenses e ON l.id = e.loan_id 
                      AND e.deleted_at IS NULL 
                      AND e.company_id = :company_id_expenses
                 WHERE l.deleted_at IS NULL 
                   AND l.company_id = :company_id_loans
                   AND l.status = 'Pendiente'
                 GROUP BY l.id, l.loan, l.amount, l.payment_method, l.code
                 ORDER BY l.date DESC, l.id DESC"
            );
            $stmt->execute([
                ':company_id_expenses' => $companyId,
                ':company_id_loans'    => $companyId
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Error en Expense::getLoans: " . $e->getMessage());
            return [];
        }
    }
}
