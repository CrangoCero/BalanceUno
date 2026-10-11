<?php

namespace App\Models;

use PDO;
use Exception;

class Order extends BaseModel
{
    public function __construct($db)
    {
        parent::__construct($db, 'orders');
    }

    // Listar todos los pedidos
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM orders WHERE company_id = :company_id AND deleted_at IS NULL ORDER BY created_at DESC"
        );
        $stmt->execute([':company_id' => $this->getCompanyId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Obtener pedido con sus detalles
    public function getWithItems($id)
    {
        $order = $this->findById($id);
        if (!$order) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT oi.*, p.sku 
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :order_id"
        );
        $stmt->execute([':order_id' => $id]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    // Crear pedido
    public function create($data, $items)
    {
        if (empty($items)) {
            throw new Exception("El pedido debe contener al menos un producto.");
        }

        $companyId = $this->getCompanyId();

        // Control de transacciones anidadas
        $alreadyInTransaction = $this->db->inTransaction();
        if (!$alreadyInTransaction) {
            $this->db->beginTransaction();
        }

        try {
            // Generar número de pedido único para la empresa
            $orderNumber = $this->generateOrderNumber($companyId);

            // Calcular totales
            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = (int)$item['quantity'];

                if ($quantity <= 0) {
                    throw new Exception("La cantidad debe ser mayor que cero.");
                }

                // Cargar datos de producto
                $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id AND company_id = :company_id AND deleted_at IS NULL");
                $stmt->execute([':id' => $productId, ':company_id' => $companyId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("El producto seleccionado no es válido.");
                }

                // Si se crea como Confirmado/Entregado de inmediato, validar stock
                $status = $data['status'] ?? 'Borrador';
                if (($status === 'Confirmado' || $status === 'Entregado') && $product['current_stock'] < $quantity) {
                    throw new Exception("Stock insuficiente para el producto: " . $product['name'] . ". Disponible: " . $product['current_stock']);
                }

                $unitPrice = (float)($item['unit_price'] ?? $product['sale_price']);
                $itemSubtotal = $unitPrice * $quantity;
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'product_id'   => $productId,
                    'product_name' => $product['name'],
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPrice,
                    'subtotal'     => $itemSubtotal
                ];
            }

            // Impuesto
            $taxRate = $_SESSION['company_tax_rate'] ?? null;
            $taxAmount = 0;
            if ($taxRate !== null && $taxRate > 0) {
                $taxAmount = $subtotal * ($taxRate / 100);
            }
            $total = $subtotal + $taxAmount;

            // Insertar pedido
            $stmt = $this->db->prepare(
                "INSERT INTO orders (company_id, order_number, customer_name, status, subtotal, tax_amount, total, notes)
                 VALUES (:company_id, :order_number, :customer_name, :status, :subtotal, :tax_amount, :total, :notes)"
            );
            $stmt->execute([
                ':company_id'    => $companyId,
                ':order_number'  => $orderNumber,
                ':customer_name' => $data['customer_name'] ?? 'Cliente General',
                ':status'        => $data['status'] ?? 'Borrador',
                ':subtotal'      => $subtotal,
                ':tax_amount'    => $taxAmount,
                ':total'         => $total,
                ':notes'         => $data['notes'] ?? null
            ]);

            $orderId = $this->db->lastInsertId();

            // Insertar ítems del pedido
            $stmtItem = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal)
                 VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price, :subtotal)"
            );

            $productModel = new Product($this->db);

            foreach ($itemsData as $item) {
                $stmtItem->execute([
                    ':order_id'     => $orderId,
                    ':product_id'   => $item['product_id'],
                    ':product_name' => $item['product_name'],
                    ':quantity'     => $item['quantity'],
                    ':unit_price'   => $item['unit_price'],
                    ':subtotal'     => $item['subtotal']
                ]);

                // Si es Confirmado o Entregado, descontar stock de inmediato
                $status = $data['status'] ?? 'Borrador';
                if ($status === 'Confirmado' || $status === 'Entregado') {
                    $productModel->adjustStock(
                        $item['product_id'],
                        $item['quantity'],
                        'salida',
                        'pedido',
                        $orderId,
                        "Descuento automático por pedido " . $orderNumber
                    );
                }
            }

            // Si es Entregado de una vez, registrar también el ingreso financiero
            if ($status === 'Entregado') {
                $this->registerFinancialIncome($orderId, $orderNumber, $total, $data['customer_name'] ?? 'Cliente General');
            }

            if (!$alreadyInTransaction) {
                $this->db->commit();
            }

            return $orderId;
        } catch (Exception $e) {
            if (!$alreadyInTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Actualizar estado del pedido (Máquina de estados de existencias y finanzas)
    public function updateStatus($id, $newStatus)
    {
        $order = $this->getWithItems($id);
        if (!$order) {
            throw new Exception("El pedido no existe.");
        }

        $oldStatus = $order['status'];
        if ($oldStatus === $newStatus) {
            return true;
        }

        $companyId = $this->getCompanyId();

        // Control de transacciones anidadas
        $alreadyInTransaction = $this->db->inTransaction();
        if (!$alreadyInTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $productModel = new Product($this->db);

            // 1. Manejo del Stock
            // Si pasa a Confirmado/Entregado desde Borrador/Cancelado: Descontar stock
            if (($newStatus === 'Confirmado' || $newStatus === 'Entregado') && ($oldStatus === 'Borrador' || $oldStatus === 'Cancelado')) {
                foreach ($order['items'] as $item) {
                    $productModel->adjustStock(
                        $item['product_id'],
                        $item['quantity'],
                        'salida',
                        'pedido',
                        $id,
                        "Descuento automático por pedido " . $order['order_number']
                    );
                }
            }
            // Si pasa a Cancelado/Borrador desde Confirmado/Entregado: Devolver stock
            elseif (($newStatus === 'Cancelado' || $newStatus === 'Borrador') && ($oldStatus === 'Confirmado' || $oldStatus === 'Entregado')) {
                foreach ($order['items'] as $item) {
                    $productModel->adjustStock(
                        $item['product_id'],
                        $item['quantity'],
                        'entrada',
                        'pedido',
                        $id,
                        "Retorno automático por cancelación del pedido " . $order['order_number']
                    );
                }
            }

            // 2. Manejo de Finanzas (Registrar Ingreso)
            // Si pasa a Entregado desde cualquier otro estado: Registrar ingreso financiero
            if ($newStatus === 'Entregado' && $oldStatus !== 'Entregado') {
                $this->registerFinancialIncome($id, $order['order_number'], $order['total'], $order['customer_name']);
            }
            // Si sale de Entregado a cualquier otro estado (ej. Cancelado): Eliminar o revertir ingreso financiero
            elseif ($oldStatus === 'Entregado' && $newStatus !== 'Entregado') {
                $this->revertFinancialIncome($id);
            }

            // 3. Actualizar estado en orders
            $stmt = $this->db->prepare("UPDATE orders SET status = :status WHERE id = :id AND company_id = :company_id");
            $stmt->execute([
                ':status'     => $newStatus,
                ':id'         => $id,
                ':company_id' => $companyId
            ]);

            if (!$alreadyInTransaction) {
                $this->db->commit();
            }

            return true;
        } catch (Exception $e) {
            if (!$alreadyInTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Helper para generar consecutivo único de pedido por empresa
    private function generateOrderNumber($companyId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE company_id = :company_id");
        $stmt->execute([':company_id' => $companyId]);
        $count = (int)$stmt->fetchColumn() + 1;
        return 'PED-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Helper para registrar ingreso financiero por pedido
    private function registerFinancialIncome($orderId, $orderNumber, $total, $customerName)
    {
        // Obtener una categoría de ingreso predeterminada o la primera disponible para ventas
        $stmtCat = $this->db->prepare("SELECT id FROM categories WHERE company_id = :company_id AND (type = 'ingreso' OR type = 'ambos') AND deleted_at IS NULL LIMIT 1");
        $stmtCat->execute([':company_id' => $this->getCompanyId()]);
        $categoryId = $stmtCat->fetchColumn() ?: null;

        $stmt = $this->db->prepare(
            "INSERT INTO incomes (company_id, date, description, amount, payment_method, category_id, payment_status, code)
             VALUES (:company_id, :date, :description, :amount, :payment_method, :category_id, :payment_status, :code)"
        );

        $stmt->execute([
            ':company_id'     => $this->getCompanyId(),
            ':date'           => date('Y-m-d'),
            ':description'    => "Venta Pedido " . $orderNumber . " (" . $customerName . ")",
            ':amount'         => $total,
            ':payment_method' => 'Efectivo',
            ':category_id'    => $categoryId,
            ':payment_status' => 'Pagado',
            ':code'           => 'PED-' . $orderId // Referencia para poder revertirlo
        ]);
    }

    // Helper para revertir ingreso financiero por pedido
    private function revertFinancialIncome($orderId)
    {
        $stmt = $this->db->prepare(
            "UPDATE incomes 
             SET deleted_at = NOW() 
             WHERE company_id = :company_id 
               AND code = :code 
               AND deleted_at IS NULL"
        );
        $stmt->execute([
            ':company_id' => $this->getCompanyId(),
            ':code'       => 'PED-' . $orderId
        ]);
    }
}
