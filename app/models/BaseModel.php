<?php

namespace App\Models;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Clase BaseModel
 *
 * Modelo base que proporciona la funcionalidad común para todos los modelos
 * de la aplicación en una arquitectura multi-tenant (multi-empresa), incluyendo:
 * - Aislamiento de datos estricto por empresa (company_id).
 * - Eliminación lógica (soft delete con deleted_at).
 * - Métodos genéricos de consulta (getAll, findById, softDelete).
 */
class BaseModel
{
    /**
     * Instancia de conexión PDO activa.
     *
     * @var PDO
     */
    protected PDO $db;

    /**
     * Nombre de la tabla asociada en la base de datos.
     *
     * @var string
     */
    protected string $table;

    /**
     * ID de la empresa vinculada a la sesión actual.
     *
     * @var int|null
     */
    protected ?int $companyId;

    /**
     * Constructor del modelo base.
     *
     * @param PDO $db Instancia de conexión a la base de datos.
     * @param string $table Nombre de la tabla asociada al modelo hijo.
     */
    public function __construct(PDO $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
        $this->companyId = isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null;
    }

    /**
     * Obtiene el company_id de la sesión actual de forma segura.
     * Si no estaba cargado inicialmente, intenta leerlo de la sesión activa.
     *
     * @return int Identificador único de la empresa.
     * @throws RuntimeException Si no existe una empresa asociada a la sesión.
     */
    protected function getCompanyId(): int
    {
        if ($this->companyId === null && !empty($_SESSION['company_id'])) {
            $this->companyId = (int)$_SESSION['company_id'];
        }

        if (!$this->companyId) {
            throw new RuntimeException('No hay empresa asociada a la sesión actual.');
        }

        return $this->companyId;
    }

    /**
     * Lista todos los registros activos (sin eliminar) de la tabla,
     * filtrados exclusivamente por la empresa de la sesión actual.
     *
     * @return array<int, array<string, mixed>> Lista asociativa de registros.
     */
    public function getAll(): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE company_id = :company_id AND deleted_at IS NULL ORDER BY date DESC"
            );
            $stmt->execute([':company_id' => $this->getCompanyId()]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error PDO en BaseModel::getAll [{$this->table}]: " . $e->getMessage());
            return [];
        } catch (Throwable $e) {
            error_log("Error general en BaseModel::getAll [{$this->table}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Realiza un borrado lógico (soft delete) marcando el campo deleted_at con la fecha/hora actual,
     * validando estrictamente que el registro pertenezca a la empresa en sesión.
     *
     * @param int|string $id Identificador del registro a eliminar.
     * @return bool True si se eliminó con éxito, False en caso contrario.
     */
    public function softDelete($id): bool
    {
        $recordId = (int)$id;
        if ($recordId <= 0) {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id AND company_id = :company_id"
            );
            return $stmt->execute([
                ':id'         => $recordId,
                ':company_id' => $this->getCompanyId()
            ]);
        } catch (Throwable $e) {
            error_log("Error en BaseModel::softDelete [{$this->table}, ID {$recordId}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca un registro específico por su ID garantizando que pertenezca a la empresa
     * y no haya sido marcado como eliminado lógicamente.
     *
     * @param int|string $id Identificador del registro.
     * @return array<string, mixed>|null Datos del registro o null si no se encuentra.
     */
    public function findById($id): ?array
    {
        $recordId = (int)$id;
        if ($recordId <= 0) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([
                ':id'         => $recordId,
                ':company_id' => $this->getCompanyId()
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Throwable $e) {
            error_log("Error en BaseModel::findById [{$this->table}, ID {$recordId}]: " . $e->getMessage());
            return null;
        }
    }
}
