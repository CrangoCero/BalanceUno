<?php

namespace App\Models;

use PDO;

class StockMovement extends BaseModel
{
    public function __construct(PDO $db)
    {
        parent::__construct($db, 'stock_movements');
    }

    // Obtener movimientos de stock por producto (filtrando inquilino)
    public function getByProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT sm.*, p.name AS product_name 
             FROM stock_movements sm
             INNER JOIN products p ON sm.product_id = p.id
             WHERE sm.product_id = :product_id 
               AND sm.company_id = :company_id
             ORDER BY sm.created_at DESC"
        );
        $stmt->execute([
            ':product_id' => $productId,
            ':company_id' => $this->getCompanyId()
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
