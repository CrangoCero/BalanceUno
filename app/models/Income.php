<?php

namespace App\Models;

use Exception;
use PDO;
use PDOException;
use Throwable;

/**
 * Modelo de Ingresos (Incomes)
 *
 * Administra el registro, edición, eliminación lógica y sincronización
 * de ingresos financieros asociados a la empresa en sesión, incluyendo
 * la integridad referencial con el módulo de préstamos.
 */
class Income extends BaseModel
{
    /**
     * Constructor del modelo Income.
     *
     * @param PDO $db Instancia activa de conexión a la base de datos.
     */
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'incomes');
    }

    /**
     * Registra un nuevo ingreso en el sistema.
     *
     * @param array<string, mixed> $data Datos validados del ingreso:
     *                                   - date: string (Y-m-d)
     *                                   - description: string
     *                                   - amount: float
     *                                   - payment_method: string
     *                                   - code: string|null
     *                                   - category_id: int|string|null
     *                                   - payment_status: string
     * @return bool True si la inserción fue exitosa, False en caso contrario.
     */
    public function create(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO incomes (company_id, date, description, amount, payment_method, code, category_id, payment_status)
                 VALUES (:company_id, :date, :description, :amount, :payment_method, :code, :category_id, :payment_status)"
            );

            return $stmt->execute([
                ':company_id'      => $this->getCompanyId(),
                ':date'            => $data['date'],
                ':description'     => $data['description'],
                ':amount'          => (float)$data['amount'],
                ':payment_method'  => $data['payment_method'],
                ':code'            => !empty($data['code']) ? $data['code'] : null,
                ':category_id'     => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                ':payment_status'  => $data['payment_status'] ?? 'Pagado'
            ]);
        } catch (Throwable $e) {
            error_log("Error en Income::create: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza los datos de un ingreso existente.
     * Si el ingreso está vinculado a un préstamo (loan_id), valida que el monto
     * no sea menor a lo ya amortizado y sincroniza ambas tablas de forma transaccional.
     *
     * @param int|string $id Identificador del ingreso.
     * @param array<string, mixed> $data Datos a actualizar.
     * @return bool True si se actualizó correctamente.
     * @throws Exception Si el registro no existe o el monto es inferior a lo pagado.
     */
    public function update($id, array $data): bool
    {
        $existing = $this->findById($id);
        if (!$existing) {
            throw new Exception("El ingreso no existe o no pertenece a su empresa.");
        }

        $this->db->beginTransaction();

        try {
            // Si el ingreso proviene de un préstamo, sincronizar con la tabla loans
            if (!empty($existing['loan_id'])) {
                $loanId = (int)$existing['loan_id'];
                $data['description'] = $existing['description']; // Mantener descripción original de préstamo

                // Validar que el nuevo monto no sea inferior a lo ya pagado en gastos
                $stmt = $this->db->prepare(
                    "SELECT COALESCE(SUM(amount), 0) FROM expenses 
                     WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
                );
                $stmt->execute([':loan_id' => $loanId, ':company_id' => $this->getCompanyId()]);
                $pagado = (float)($stmt->fetchColumn() ?: 0);

                if ((float)$data['amount'] < $pagado) {
                    $formatoPagado = number_format($pagado, 0, ',', '.');
                    throw new Exception("El nuevo monto del préstamo no puede ser menor a lo que ya se ha amortizado ($formatoPagado COP).");
                }

                // Reflejar cambios en la tabla loans
                $stmtLoan = $this->db->prepare(
                    "UPDATE loans 
                     SET date = :date, amount = :amount, payment_method = :payment_method, code = :code
                     WHERE id = :id AND company_id = :company_id"
                );
                $stmtLoan->execute([
                    ':id'             => $loanId,
                    ':company_id'     => $this->getCompanyId(),
                    ':date'           => $data['date'],
                    ':amount'         => (float)$data['amount'],
                    ':payment_method' => $data['payment_method'],
                    ':code'           => !empty($data['code']) ? $data['code'] : null
                ]);
            }

            // Actualizar registro en incomes
            $stmt = $this->db->prepare(
                "UPDATE incomes 
                 SET date = :date, description = :description, amount = :amount, payment_method = :payment_method, 
                     code = :code, category_id = :category_id, payment_status = :payment_status
                 WHERE id = :id AND company_id = :company_id"
            );

            $result = $stmt->execute([
                ':id'              => (int)$id,
                ':company_id'      => $this->getCompanyId(),
                ':date'            => $data['date'],
                ':description'     => $data['description'],
                ':amount'          => (float)$data['amount'],
                ':payment_method'  => $data['payment_method'],
                ':code'            => !empty($data['code']) ? $data['code'] : null,
                ':category_id'     => !empty($data['category_id']) ? (int)$data['category_id'] : null,
                ':payment_status'  => $data['payment_status'] ?? 'Pagado'
            ]);

            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en Income::update [ID {$id}]: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Realiza un borrado lógico (soft delete) del ingreso.
     * Si corresponde a un préstamo, verifica que no tenga amortizaciones registradas
     * antes de proceder y elimina ambos registros atómicamente.
     *
     * @param int|string $id Identificador del ingreso.
     * @return bool True si se eliminó con éxito.
     * @throws Exception Si el préstamo asociado ya cuenta con pagos registrados.
     */
    public function softDelete($id): bool
    {
        $existing = $this->findById($id);
        if (!$existing) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            if (!empty($existing['loan_id'])) {
                $loanId = (int)$existing['loan_id'];

                // Verificar si tiene pagos asociados
                $stmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM expenses 
                     WHERE loan_id = :loan_id AND company_id = :company_id AND deleted_at IS NULL"
                );
                $stmt->execute([':loan_id' => $loanId, ':company_id' => $this->getCompanyId()]);
                $pagos = (int)($stmt->fetchColumn() ?: 0);

                if ($pagos > 0) {
                    throw new Exception("No se puede eliminar el préstamo porque ya tiene pagos registrados. Elimine los pagos primero.");
                }

                // Borrado lógico en loans
                $stmtLoan = $this->db->prepare(
                    "UPDATE loans SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id"
                );
                $stmtLoan->execute([':id' => $loanId, ':company_id' => $this->getCompanyId()]);
            }

            // Borrado lógico en incomes
            $stmt = $this->db->prepare(
                "UPDATE incomes SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id"
            );
            $result = $stmt->execute([
                ':id'         => (int)$id,
                ':company_id' => $this->getCompanyId()
            ]);

            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en Income::softDelete [ID {$id}]: " . $e->getMessage());
            throw $e;
        }
    }
}
