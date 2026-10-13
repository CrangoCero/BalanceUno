<?php

namespace App\Models;

use PDO;

class Category extends BaseModel
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'categories');
    }

    // Listar categorías filtradas por tipo e inquilino
    public function getByType(string $type)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM categories 
             WHERE company_id = :company_id 
               AND (type = :type OR type = 'ambos') 
               AND deleted_at IS NULL 
             ORDER BY name ASC"
        );
        $stmt->execute([
            ':company_id' => $this->getCompanyId(),
            ':type' => $type
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear categoría
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO categories (company_id, name, type) 
             VALUES (:company_id, :name, :type)"
        );
        return $stmt->execute([
            ':company_id' => $this->getCompanyId(),
            ':name' => $data['name'],
            ':type' => $data['type'] ?? 'ambos'
        ]);
    }

    // Actualizar categoría
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE categories 
             SET name = :name, type = :type 
             WHERE id = :id AND company_id = :company_id"
        );
        return $stmt->execute([
            ':id' => $id,
            ':company_id' => $this->getCompanyId(),
            ':name' => $data['name'],
            ':type' => $data['type'] ?? 'ambos'
        ]);
    }
}
