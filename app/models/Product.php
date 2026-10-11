<?php

namespace App\Models;

use PDO;
use Exception;

class Product extends BaseModel
{
    public function __construct($db)
    {
        parent::__construct($db, 'products');
    }

    // Crear producto
    public function create($data)
    {
        $companyId = $this->getCompanyId();

        $stmt = $this->db->prepare(
            "INSERT INTO products (company_id, name, sku, description, current_stock, min_stock, cost_price, sale_price)
             VALUES (:company_id, :name, :sku, :description, :current_stock, :min_stock, :cost_price, :sale_price)"
        );

        $result = $stmt->execute([
            ':company_id'    => $companyId,
            ':name'          => $data['name'],
            ':sku'           => !empty($data['sku']) ? $data['sku'] : null,
            ':description'   => $data['description'] ?? null,
            ':current_stock' => (int)($data['current_stock'] ?? 0),
            ':min_stock'     => (int)($data['min_stock'] ?? 0),
            ':cost_price'    => (float)($data['cost_price'] ?? 0),
            ':sale_price'    => (float)($data['sale_price'] ?? 0)
        ]);

        if ($result && (int)($data['current_stock'] ?? 0) > 0) {
            $productId = $this->db->lastInsertId();
            // Registrar movimiento inicial de stock
            $this->logStockMovement($productId, 'entrada', (int)$data['current_stock'], 'ajuste', null, 'Stock inicial al crear producto');
        }

        return $result;
    }

    // Actualizar producto
    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE products 
             SET name = :name, sku = :sku, description = :description, min_stock = :min_stock, 
                 cost_price = :cost_price, sale_price = :sale_price
             WHERE id = :id AND company_id = :company_id"
        );

        return $stmt->execute([
            ':id'          => $id,
            ':company_id'  => $this->getCompanyId(),
            ':name'        => $data['name'],
            ':sku'         => !empty($data['sku']) ? $data['sku'] : null,
            ':description' => $data['description'] ?? null,
            ':min_stock'   => (int)($data['min_stock'] ?? 0),
            ':cost_price'  => (float)($data['cost_price'] ?? 0),
            ':sale_price'  => (float)($data['sale_price'] ?? 0)
        ]);
    }

    // Obtener productos con stock bajo o mínimo alcanzado
    public function getLowStockProducts()
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM products 
             WHERE company_id = :company_id 
               AND current_stock <= min_stock 
               AND deleted_at IS NULL"
        );
        $stmt->execute([':company_id' => $this->getCompanyId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ajustar stock (incrementar / decrementar) y registrar movimiento
    public function adjustStock($productId, $quantity, $type, $referenceType = 'ajuste', $referenceId = null, $notes = '')
    {
        $product = $this->findById($productId);
        if (!$product) {
            throw new Exception("El producto no existe o no pertenece a esta empresa.");
        }

        $quantity = (int)$quantity;
        if ($quantity <= 0) {
            throw new Exception("La cantidad debe ser mayor que cero.");
        }

        // Verificar si ya existe una transacción activa iniciada externamente
        $alreadyInTransaction = $this->db->inTransaction();
        if (!$alreadyInTransaction) {
            $this->db->beginTransaction();
        }

        try {
            // Calcular nuevo stock
            $currentStock = (int)$product['current_stock'];
            if ($type === 'entrada') {
                $newStock = $currentStock + $quantity;
            } elseif ($type === 'salida') {
                $newStock = $currentStock - $quantity;
                if ($newStock < 0) {
                    throw new Exception("Stock insuficiente para realizar esta salida. Stock actual: " . $currentStock);
                }
            } else {
                throw new Exception("Tipo de movimiento inválido.");
            }

            // Actualizar stock del producto
            $stmt = $this->db->prepare(
                "UPDATE products SET current_stock = :current_stock WHERE id = :id AND company_id = :company_id"
            );
            $stmt->execute([
                ':current_stock' => $newStock,
                ':id'            => $productId,
                ':company_id'    => $this->getCompanyId()
            ]);

            // Registrar movimiento de stock
            $this->logStockMovement($productId, $type, $quantity, $referenceType, $referenceId, $notes);

            // Confirmar transacción solo si fue iniciada por este método
            if (!$alreadyInTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (Exception $e) {
            // Revertir transacción solo si fue iniciada por este método
            if (!$alreadyInTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Helper interno para loguear movimiento de stock
    private function logStockMovement($productId, $type, $quantity, $referenceType, $referenceId, $notes)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_movements (company_id, product_id, type, quantity, reference_type, reference_id, notes)
             VALUES (:company_id, :product_id, :type, :quantity, :reference_type, :reference_id, :notes)"
        );
        $stmt->execute([
            ':company_id'     => $this->getCompanyId(),
            ':product_id'     => $productId,
            ':type'           => $type,
            ':quantity'       => $quantity,
            ':reference_type' => $referenceType,
            ':reference_id'   => $referenceId,
            ':notes'          => $notes
        ]);
    }
}
