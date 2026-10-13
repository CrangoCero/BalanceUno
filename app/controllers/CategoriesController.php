<?php

namespace App\Controllers;

use App\Models\Category;
use PDO;

class CategoriesController
{
    private Category $model;

    public function __construct(PDO $db)
    {
        $this->model = new Category($db);
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
        $categories = $this->model->getAll();
        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'data' => $categories]);
            exit;
        }
        include __DIR__ . '/../../views/categories.php';
    }

    public function create(array $data)
    {
        if (empty($data['name'])) {
            $this->respondError('El nombre de la categoría es obligatorio.');
        }

        $this->model->create($data);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Categoría creada correctamente']);
            exit;
        }

        header("Location: ?action=categories");
        exit;
    }

    public function update(int|string $id, array $data)
    {
        if (empty($data['name'])) {
            $this->respondError('El nombre de la categoría es obligatorio.');
        }

        $this->model->update($id, $data);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada correctamente']);
            exit;
        }

        header("Location: ?action=categories");
        exit;
    }

    public function delete(int|string $id)
    {
        $this->model->softDelete($id);

        if ($this->isAjax()) {
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada correctamente']);
            exit;
        }

        header("Location: ?action=categories");
        exit;
    }
}
