<?php

namespace App\Models;

use PDO;
use PDOException;
use Throwable;

/**
 * Modelo de Usuario
 *
 * Gestiona la consulta y autenticación de usuarios en el sistema.
 * Las búsquedas de credenciales son globales para resolver la empresa asociada.
 */
class User extends BaseModel
{
    /**
     * Constructor del modelo User.
     *
     * @param PDO $db Instancia activa de conexión a la base de datos.
     */
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'users');
    }

    /**
     * Busca un usuario por su nombre de usuario (username), obteniendo además
     * los datos de la empresa a la que pertenece (ID, nombre, tasa de impuesto).
     *
     * @param string $username Nombre de usuario a buscar.
     * @return array<string, mixed>|null Datos del usuario o null si no se encuentra o falla la consulta.
     */
    public function findByUsername(string $username): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT u.*, c.name AS company_name, c.tax_rate AS company_tax_rate
                 FROM users u
                 INNER JOIN companies c ON u.company_id = c.id
                 WHERE u.username = :username
                 LIMIT 1"
            );
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error PDO en User::findByUsername: " . $e->getMessage());
            return null;
        } catch (Throwable $e) {
            error_log("Error general en User::findByUsername: " . $e->getMessage());
            return null;
        }
    }
}
