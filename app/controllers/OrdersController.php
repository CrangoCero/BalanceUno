<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\Product;
use PDO;

class OrdersController
{
    private Order $model;
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->model = new Order($db);
    }

    private function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    private function respondError(string $message): void
    {
        if ($this->isAjax()) {
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            die($message);
        }
        exit;
    }

    // Listar pedidos
    public function index()
    {
        $orders = $this->model->getAll();

        // Cargar productos para el modal de creación
        $productModel = new Product($this->db);
        $products = $productModel->getAll();

        include __DIR__ . '/../../views/orders.php';
    }

    // Crear pedido
    public function create(array $data)
    {
        $customerName = $data['customer_name'] ?? 'Cliente General';
        $status = $data['status'] ?? 'Borrador';
        $notes = $data['notes'] ?? '';

        // Los items se reciben en arrays: products_ids[], quantities[], prices[]
        $productIds = $data['products_ids'] ?? [];
        $quantities = $data['quantities'] ?? [];
        $prices = $data['prices'] ?? [];

        $items = [];
        for ($i = 0; $i < count($productIds); $i++) {
            if (!empty($productIds[$i]) && (int)$quantities[$i] > 0) {
                $items[] = [
                    'product_id' => $productIds[$i],
                    'quantity'   => (int)$quantities[$i],
                    'unit_price' => !empty($prices[$i]) ? (float)str_replace(['.', ','], ['', '.'], $prices[$i]) : null
                ];
            }
        }

        try {
            $this->model->create([
                'customer_name' => $customerName,
                'status'        => $status,
                'notes'         => $notes
            ], $items);

            if ($this->isAjax()) {
                echo json_encode(['success' => true, 'message' => 'Pedido registrado correctamente']);
                exit;
            }
        } catch (\Exception $e) {
            $this->respondError($e->getMessage());
        }

        header("Location: ?action=orders");
        exit;
    }

    // Cambiar estado del pedido
    public function updateStatus(array $data): void
    {
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? '';

        if (!$id || !$status) {
            $this->respondError('ID y estado del pedido son requeridos.');
        }

        try {
            $this->model->updateStatus($id, $status);
            if ($this->isAjax()) {
                echo json_encode(['success' => true, 'message' => 'Estado del pedido actualizado correctamente']);
                exit;
            }
        } catch (\Exception $e) {
            $this->respondError($e->getMessage());
        }

        header("Location: ?action=orders");
        exit;
    }

    // Obtener detalles del pedido por AJAX
    public function getItems(int $id)
    {
        if (!$this->isAjax()) {
            header("Location: ?action=orders");
            exit;
        }

        try {
            $order = $this->model->getWithItems($id);
            if (!$order) {
                throw new \Exception("El pedido no existe.");
            }
            echo json_encode(['success' => true, 'data' => $order]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Eliminar pedido (solo Borrador)
    public function delete(int $id)
    {
        $order = $this->model->findById($id);
        if (!$order) {
            $this->respondError('El pedido no existe.');
        }

        if ($order['status'] !== 'Borrador') {
            $this->respondError('Solo se pueden eliminar pedidos en estado Borrador.');
        }

        $this->model->softDelete($id);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Pedido eliminado correctamente']);
            exit;
        }

        header("Location: ?action=orders");
        exit;
    }

    // Exportar pedidos a Excel
    public function exportXls()
    {
        $orders = $this->model->getAll();
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=pedidos.xls");

        echo "Número Pedido\tCliente\tFecha\tEstado\tSubtotal\tImpuesto\tTotal\n";
        foreach ($orders as $o) {
            $date = date('d/m/Y', strtotime($o['created_at']));
            echo "{$o['order_number']}\t{$o['customer_name']}\t{$date}\t{$o['status']}\t{$o['subtotal']}\t{$o['tax_amount']}\t{$o['total']}\n";
        }
        exit;
    }
}
