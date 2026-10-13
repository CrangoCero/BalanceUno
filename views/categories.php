<?php
$title = "Categorías";
$useDataTables = true;
$pageTitle = "Categorías de Movimiento";
$pageIcon = "folder_special";

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/navbar.php';
?>

<section id="main" class="container">
    <div class="dashboard-box">
        <div class="row" style="margin-bottom: 30px;">
            <div class="col s12 m6">
                <h5 style="font-weight: 700; color: var(--primary);">Categorías</h5>
                <p class="grey-text">Configura las categorías para tus ingresos y gastos</p>
            </div>
            <div class="col s12 m6 right-align">
                <a href="#modalCreateCategory" class="btn secondary-color modal-trigger action-btn">
                    <i class="material-icons left">add</i> Nueva Categoría
                </a>
            </div>
        </div>

        <table id="categoriesTable" class="highlight display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo de Movimiento</th>
                    <th class="center-align">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($categories ?? []) as $category): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= htmlspecialchars($category['name']) ?></td>
                        <td>
                            <?php if ($category['type'] === 'ingreso'): ?>
                                <span class="badge green white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Ingreso</span>
                            <?php elseif ($category['type'] === 'gasto'): ?>
                                <span class="badge red white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Gasto</span>
                            <?php else: ?>
                                <span class="badge blue white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Ambos</span>
                            <?php endif; ?>
                        </td>
                        <td class="center-align">
                            <a href="#modalEditCategory" class="btn-flat waves-effect modal-trigger edit-category-btn" style="color: var(--info);"
                                data-id="<?= $category['id'] ?>"
                                data-name="<?= htmlspecialchars($category['name']) ?>"
                                data-type="<?= htmlspecialchars($category['type']) ?>">
                                <i class="material-icons">edit</i>
                            </a>
                            <form action="?action=deleteCategory" method="POST" style="display:inline;" class="delete-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                <button type="submit" class="btn-flat waves-effect delete-btn" style="color: var(--error);">
                                    <i class="material-icons">delete</i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal Crear Categoría -->
<div id="modalCreateCategory" class="modal">
    <div class="modal-content">
        <h5 class="center-align secondary-color-text">
            <i class="material-icons left">folder_open</i> Nueva Categoría
        </h5>
        <form method="POST" action="?action=createCategory">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="input-field">
                <input type="text" name="name" required id="create_category_name">
                <label for="create_category_name">Nombre de Categoría</label>
            </div>
            <div class="input-field">
                <select name="type" required>
                    <option value="ambos" selected>Ambos (Ingreso y Gasto)</option>
                    <option value="ingreso">Ingreso</option>
                    <option value="gasto">Gasto</option>
                </select>
                <label>Tipo de Movimiento</label>
            </div>
            <div class="center-align">
                <button type="submit" class="btn secondary-color">Guardar</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Categoría -->
<div id="modalEditCategory" class="modal">
    <div class="modal-content">
        <h5 class="center-align secondary-color-text">
            <i class="material-icons left">edit</i> Editar Categoría
        </h5>
        <form method="POST" action="?action=updateCategory">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id">
            <div class="input-field">
                <input type="text" name="name" required>
                <label class="active">Nombre de Categoría</label>
            </div>
            <div class="input-field">
                <select name="type" id="edit_category_type" required>
                    <option value="ambos">Ambos (Ingreso y Gasto)</option>
                    <option value="ingreso">Ingreso</option>
                    <option value="gasto">Gasto</option>
                </select>
                <label>Tipo de Movimiento</label>
            </div>
            <div class="center-align">
                <button type="submit" class="btn secondary-color">Actualizar</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/partials/footer.php';
include __DIR__ . '/partials/scripts.php';
?>
