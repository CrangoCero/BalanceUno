<?php
$title = "Inventario";
$useDataTables = true;
$pageTitle = "Inventario";
$pageIcon = "store";
$navColor = "teal";

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/navbar.php';

$products = $products ?? [];
$lowStockProducts = $lowStockProducts ?? [];
?>

<section id="main" class="container">
    <!-- Alertas de Stock Bajo -->
    <?php if (!empty($lowStockProducts)): ?>
        <div class="card-panel red lighten-4 red-text text-darken-4" style="border-radius: 8px; margin-bottom: 25px; border: 1px solid #ffcdd2;">
            <div style="display: flex; align-items: center;">
                <i class="material-icons" style="margin-right: 15px; font-size: 2rem;">warning</i>
                <div>
                    <h6 style="margin: 0; font-weight: 600;">¡Alerta de Stock Mínimo Alcanzado!</h6>
                    <p style="margin: 5px 0 0 0; font-size: 0.9rem;">
                        Los siguientes productos tienen existencias iguales o inferiores al stock mínimo:
                        <strong>
                            <?php
                            $lowNames = array_map(fn($p) => htmlspecialchars($p['name']) . ' (' . $p['current_stock'] . ')', $lowStockProducts);
                            echo implode(', ', $lowNames);
                            ?>
                        </strong>.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contenedor Principal de Inventario -->
    <div class="dashboard-box">
        <div class="row" style="margin-bottom: 30px;">
            <div class="col s12 m6">
                <h5 style="font-weight: 700; color: var(--primary);">Existencias y Precios</h5>
                <p class="grey-text">Lleva el control de stock, precios de costo y venta de tus productos</p>
            </div>
            <div class="col s12 m6 right-align">
                <a href="#modalCreateProduct" class="btn teal modal-trigger action-btn">
                    <i class="material-icons left">add</i> Nuevo Producto
                </a>
                <a href="#modalAdjustStock" class="btn orange modal-trigger action-btn">
                    <i class="material-icons left">swap_vert</i> Ajustar Stock
                </a>
                <a href="?action=exportInventoryXls" class="btn reports-color action-btn">
                    <i class="material-icons left">file_download</i> Excel
                </a>
            </div>
        </div>

        <table id="inventoryTable" class="highlight display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Código/SKU</th>
                    <th>Producto</th>
                    <th class="right-align">Stock</th>
                    <th class="right-align">Stock Mín</th>
                    <th class="right-align">Precio Costo</th>
                    <th class="right-align">Precio Venta</th>
                    <th class="center-align">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr class="<?= $p['current_stock'] <= $p['min_stock'] ? 'red lighten-5 red-text text-darken-4' : '' ?>">
                        <td style="font-weight: 600;"><?= htmlspecialchars($p['sku'] ?? '-') ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($p['name']) ?></td>
                        <td class="right-align" style="font-weight: 700;">
                            <?= $p['current_stock'] ?>
                            <?php if ($p['current_stock'] <= $p['min_stock']): ?>
                                <span class="badge red white-text" style="float: none; margin-left: 5px; font-size: 0.8rem; border-radius: 4px;">Bajo</span>
                            <?php endif; ?>
                        </td>
                        <td class="right-align grey-text"><?= $p['min_stock'] ?></td>
                        <td class="right-align">$<?= number_format($p['cost_price'], 0, ",", ".") ?></td>
                        <td class="right-align accent-color-text" style="font-weight: 600;">$<?= number_format($p['sale_price'], 0, ",", ".") ?></td>
                        <td class="center-align">
                            <a href="#modalEditProduct" class="btn-flat waves-effect modal-trigger edit-product-btn" style="color: var(--info);"
                                data-id="<?= $p['id'] ?>"
                                data-sku="<?= htmlspecialchars($p['sku'] ?? '') ?>"
                                data-name="<?= htmlspecialchars($p['name']) ?>"
                                data-description="<?= htmlspecialchars($p['description'] ?? '') ?>"
                                data-min_stock="<?= $p['min_stock'] ?>"
                                data-cost_price="<?= htmlspecialchars($p['cost_price']) ?>"
                                data-sale_price="<?= htmlspecialchars($p['sale_price']) ?>">
                                <i class="material-icons">edit</i>
                            </a>
                            <a href="#modalViewMovements" class="btn-flat waves-effect modal-trigger btn-view-movements-history" style="color: var(--warning);"
                                data-id="<?= $p['id'] ?>"
                                data-name="<?= htmlspecialchars($p['name']) ?>">
                                <i class="material-icons">history</i>
                            </a>
                            <a href="#modalDeleteProduct<?= $p['id'] ?>" class="btn-flat waves-effect modal-trigger" style="color: var(--error);">
                                <i class="material-icons">delete</i>
                            </a>
                        </td>
                    </tr>
                    <!-- Modal Eliminar Producto -->
                    <div id="modalDeleteProduct<?= $p['id'] ?>" class="modal">
                        <div class="modal-content center-align">
                            <div style="background: #fee2e2; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <i class="material-icons error-color-text" style="font-size: 35px;">warning</i>
                            </div>
                            <h5 style="font-weight: 700;">¿Eliminar Producto?</h5>
                            <p class="grey-text">Estás a punto de borrar del catálogo: <br><strong><?= htmlspecialchars($p['name']) ?></strong></p>
                            <form method="POST" action="?action=deleteProduct" style="margin-top: 30px;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn error-color">Confirmar Eliminación</button>
                                <a href="#!" class="modal-close btn-flat grey-text">Cancelar</a>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal Crear Producto -->
<div id="modalCreateProduct" class="modal">
    <div class="modal-content">
        <h5 class="center-align teal-text">
            <i class="material-icons left">add_shopping_cart</i> Nuevo Producto
        </h5>
        <form method="POST" action="?action=createProduct">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="input-field">
                <input type="text" name="name" required id="create_prod_name">
                <label for="create_prod_name">Nombre del Producto</label>
            </div>
            <div class="input-field">
                <input type="text" name="sku" id="create_prod_sku">
                <label for="create_prod_sku">Código / SKU</label>
            </div>
            <div class="input-field">
                <textarea name="description" class="materialize-textarea" id="create_prod_desc"></textarea>
                <label for="create_prod_desc">Descripción (Opcional)</label>
            </div>
            <div class="row">
                <div class="input-field col s6">
                    <input type="number" name="current_stock" value="0" min="0" required id="create_prod_stock">
                    <label for="create_prod_stock">Stock Inicial</label>
                </div>
                <div class="input-field col s6">
                    <input type="number" name="min_stock" value="0" min="0" required id="create_prod_min">
                    <label for="create_prod_min">Stock Mínimo</label>
                </div>
            </div>
            <div class="row">
                <div class="input-field col s6">
                    <input type="text" name="cost_price" value="0" required id="create_prod_cost">
                    <label for="create_prod_cost">Precio Costo ($)</label>
                </div>
                <div class="input-field col s6">
                    <input type="text" name="sale_price" value="0" required id="create_prod_sale">
                    <label for="create_prod_sale">Precio Venta ($)</label>
                </div>
            </div>
            <div class="center-align" style="margin-top: 20px;">
                <button type="submit" class="btn teal">Guardar Producto</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Producto -->
<div id="modalEditProduct" class="modal">
    <div class="modal-content">
        <h5 class="center-align teal-text">
            <i class="material-icons left">edit</i> Editar Producto
        </h5>
        <form method="POST" action="?action=updateProduct">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id">
            <div class="input-field">
                <input type="text" name="name" required>
                <label class="active">Nombre del Producto</label>
            </div>
            <div class="input-field">
                <input type="text" name="sku">
                <label class="active">Código / SKU</label>
            </div>
            <div class="input-field">
                <textarea name="description" class="materialize-textarea"></textarea>
                <label class="active">Descripción (Opcional)</label>
            </div>
            <div class="input-field">
                <input type="number" name="current_stock" required>
                <label for="create_prod_stock">Stock Inicial</label>
            </div>

            <div class="input-field">
                <input type="number" name="min_stock" min="0" required>
                <label class="active">Stock Mínimo</label>
            </div>
            <div class="row">
                <div class="input-field col s6">
                    <input type="text" name="cost_price" required>
                    <label class="active">Precio Costo ($)</label>
                </div>
                <div class="input-field col s6">
                    <input type="text" name="sale_price" required>
                    <label class="active">Precio Venta ($)</label>
                </div>
            </div>
            <div class="center-align" style="margin-top: 20px;">
                <button type="submit" class="btn teal">Actualizar Producto</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajustar Stock -->
<div id="modalAdjustStock" class="modal">
    <div class="modal-content">
        <h5 class="center-align orange-text">
            <i class="material-icons left">swap_vert</i> Ajuste Manual de Existencias
        </h5>
        <form method="POST" action="?action=adjustStock">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="input-field">
                <select name="product_id" required>
                    <option value="" disabled selected>Elige un producto</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Actual: <?= $p['current_stock'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <label>Seleccionar Producto</label>
            </div>
            <div class="input-field">
                <select name="type" required>
                    <option value="entrada" selected>Entrada (Compra, Regalo, Devolución)</option>
                    <option value="salida">Salida (Venta manual, Daño, Pérdida)</option>
                </select>
                <label>Tipo de Ajuste</label>
            </div>
            <div class="input-field">
                <input type="number" name="quantity" min="1" value="1" required id="adjust_quantity">
                <label for="adjust_quantity">Cantidad</label>
            </div>
            <div class="input-field">
                <input type="text" name="notes" placeholder="Ej: Compra a proveedor, Torta dañada" id="adjust_notes">
                <label for="adjust_notes">Notas / Justificación</label>
            </div>
            <div class="center-align" style="margin-top: 20px;">
                <button type="submit" class="btn orange">Ejecutar Ajuste</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver Historial de Movimientos -->
<div id="modalViewMovements" class="modal modal-fixed-footer">
    <div class="modal-content">
        <h5 class="orange-text"><i class="material-icons left">history</i> Historial de Movimientos: <span id="movementsProductName" class="black-text" style="font-weight: 600;">-</span></h5>
        <p class="grey-text">Registro de todas las entradas y salidas de este producto</p>

        <table id="movementsHistoryTable" class="highlight display nowrap" style="width:100%; margin-top:20px;">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Referencia</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                <!-- Cargado dinámicamente vía AJAX -->
            </tbody>
        </table>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close btn grey">Cerrar</a>
    </div>
</div>

<?php
include __DIR__ . '/partials/footer.php';
include __DIR__ . '/partials/scripts.php';
?>