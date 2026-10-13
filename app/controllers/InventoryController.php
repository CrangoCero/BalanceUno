<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use PDO;

class InventoryController
{
    private Product $model;
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->model = new Product($db);
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

    public function index()
    {
        $products = $this->model->getAll();
        $lowStockProducts = $this->model->getLowStockProducts();

        include __DIR__ . '/../../views/inventory.php';
    }

    public function create(array $data)
    {
        if (empty($data['name'])) {
            $this->respondError('El nombre del producto es obligatorio.');
        }

        // Normalizar precios
        $costPrice = str_replace('.', '', $data['cost_price'] ?? '0');
        $costPrice = str_replace(',', '.', $costPrice);
        $data['cost_price'] = (float)$costPrice;

        $salePrice = str_replace('.', '', $data['sale_price'] ?? '0');
        $salePrice = str_replace(',', '.', $salePrice);
        $data['sale_price'] = (float)$salePrice;

        $this->model->create($data);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Producto creado correctamente']);
            exit;
        }

        header("Location: ?action=inventory");
        exit;
    }

    public function update(int|string $id, array $data)
    {
        if (empty($data['name'])) {
            $this->respondError('El nombre del producto es obligatorio.');
        }

        // Normalizar precios
        $costPrice = str_replace('.', '', $data['cost_price'] ?? '0');
        $costPrice = str_replace(',', '.', $costPrice);
        $data['cost_price'] = (float)$costPrice;

        $salePrice = str_replace('.', '', $data['sale_price'] ?? '0');
        $salePrice = str_replace(',', '.', $salePrice);
        $data['sale_price'] = (float)$salePrice;

        $this->model->update($id, $data);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente']);
            exit;
        }

        header("Location: ?action=inventory");
        exit;
    }

    public function delete(int|string $id)
    {
        $this->model->softDelete($id);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente']);
            exit;
        }

        header("Location: ?action=inventory");
        exit;
    }

    public function adjustStock(array $data)
    {
        $productId = $data['product_id'] ?? null;
        $quantity = $data['quantity'] ?? 0;
        $type = $data['type'] ?? ''; // entrada / salida
        $notes = $data['notes'] ?? '';

        if (!$productId || !$quantity || !$type) {
            $this->respondError('Todos los campos son obligatorios para el ajuste.');
        }

        try {
            $this->model->adjustStock($productId, $quantity, $type, 'ajuste', null, $notes);
            if ($this->isAjax()) {
                echo json_encode(['success' => true, 'message' => 'Stock ajustado correctamente']);
                exit;
            }
        } catch (\Exception $e) {
            $this->respondError($e->getMessage());
        }

        header("Location: ?action=inventory");
        exit;
    }

    public function getMovements(int|string $productId)
    {
        if (!$this->isAjax()) {
            header("Location: ?action=inventory");
            exit;
        }

        try {
            $smModel = new StockMovement($this->db);
            $movements = $smModel->getByProduct($productId);
            echo json_encode(['success' => true, 'data' => $movements]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function exportXls()
    {
        $products = $this->model->getAll();
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=inventario.xls");

        echo "Código/SKU\tProducto\tStock Actual\tStock Mínimo\tPrecio Costo\tPrecio Venta\tValor Inventario (Costo)\n";
        foreach ($products as $p) {
            $value = $p['current_stock'] * $p['cost_price'];
            echo "{$p['sku']}\t{$p['name']}\t{$p['current_stock']}\t{$p['min_stock']}\t{$p['cost_price']}\t{$p['sale_price']}\t{$value}\n";
        }
        exit;
    }
}
