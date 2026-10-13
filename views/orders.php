<?php
$title = "Pedidos";
$useDataTables = true;
$pageTitle = "Pedidos / Ventas";
$pageIcon = "assignment";
$navColor = "blue";

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/navbar.php';
?>

<section id="main" class="container">
    <div class="dashboard-box">
        <div class="row" style="margin-bottom: 30px;">
            <div class="col s12 m6">
                <h5 style="font-weight: 700; color: var(--primary);">Pedidos y Ventas</h5>
                <p class="grey-text">Gestión de órdenes, facturación y control de entrega</p>
            </div>
            <div class="col s12 m6 right-align">
                <a href="#modalCreateOrder" class="btn blue modal-trigger action-btn">
                    <i class="material-icons left">add</i> Nuevo Pedido
                </a>
                <a href="?action=exportOrdersXls" class="btn reports-color action-btn">
                    <i class="material-icons left">file_download</i> Excel
                </a>
            </div>
        </div>

        <table id="ordersTable" class="highlight display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th class="right-align">Total</th>
                    <th class="center-align">Estado</th>
                    <th class="center-align">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($orders ?? []) as $order): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($order['order_number']) ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                        <td class="right-align accent-color-text" style="font-weight: 700;">
                            $<?= number_format($order['total'], 0, ",", ".") ?>
                        </td>
                        <td class="center-align">
                            <?php if ($order['status'] === 'Borrador'): ?>
                                <span class="badge grey white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Borrador</span>
                            <?php elseif ($order['status'] === 'Confirmado'): ?>
                                <span class="badge blue white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Confirmado</span>
                            <?php elseif ($order['status'] === 'Entregado'): ?>
                                <span class="badge green white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Entregado</span>
                            <?php else: ?>
                                <span class="badge red white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Cancelado</span>
                            <?php endif; ?>
                        </td>
                        <td class="center-align" style="white-space: nowrap;">
                            <a href="#modalViewOrderDetails" class="btn-flat waves-effect modal-trigger btn-view-order-details" style="color: var(--info);"
                                data-id="<?= $order['id'] ?>">
                                <i class="material-icons">visibility</i>
                            </a>

                            <?php if ($order['status'] === 'Borrador'): ?>
                                <a href="#!" class="btn-flat waves-effect btn-change-order-status" style="color: var(--secondary);"
                                    data-id="<?= $order['id'] ?>" data-status="Confirmado" title="Confirmar Pedido">
                                    <i class="material-icons">check_circle_outline</i>
                                </a>
                                <a href="#!" class="btn-flat waves-effect btn-change-order-status" style="color: var(--error);"
                                    data-id="<?= $order['id'] ?>" data-status="Cancelado" title="Cancelar Pedido">
                                    <i class="material-icons">cancel</i>
                                </a>
                                <a href="#modalDeleteOrder<?= $order['id'] ?>" class="btn-flat waves-effect modal-trigger" style="color: var(--error);" title="Eliminar">
                                    <i class="material-icons">delete</i>
                                </a>
                            <?php elseif ($order['status'] === 'Confirmado'): ?>
                                <a href="#!" class="btn-flat waves-effect btn-change-order-status" style="color: var(--success);"
                                    data-id="<?= $order['id'] ?>" data-status="Entregado" title="Entregar Pedido">
                                    <i class="material-icons">done_all</i>
                                </a>
                                <a href="#!" class="btn-flat waves-effect btn-change-order-status" style="color: var(--error);"
                                    data-id="<?= $order['id'] ?>" data-status="Cancelado" title="Cancelar Pedido">
                                    <i class="material-icons">cancel</i>
                                </a>
                            <?php elseif ($order['status'] === 'Entregado'): ?>
                                <a href="#!" class="btn-flat waves-effect btn-change-order-status" style="color: var(--error);"
                                    data-id="<?= $order['id'] ?>" data-status="Cancelado" title="Cancelar Pedido y Revertir Stock/Ingresos">
                                    <i class="material-icons">settings_backup_restore</i>
                                </a>
                            <?php else: ?>
                                <span class="grey-text">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Modal Eliminar Pedido -->
                    <?php if ($order['status'] === 'Borrador'): ?>
                        <div id="modalDeleteOrder<?= $order['id'] ?>" class="modal">
                            <div class="modal-content center-align">
                                <div style="background: #fee2e2; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                    <i class="material-icons error-color-text" style="font-size: 35px;">warning</i>
                                </div>
                                <h5 style="font-weight: 700;">¿Eliminar Borrador?</h5>
                                <p class="grey-text">Estás a punto de borrar definitivamente el pedido borrador: <br><strong><?= htmlspecialchars($order['order_number']) ?></strong></p>
                                <form method="POST" action="?action=deleteOrder" style="margin-top: 30px;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn error-color">Confirmar Eliminación</button>
                                    <a href="#!" class="modal-close btn-flat grey-text">Cancelar</a>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal Crear Pedido -->
<div id="modalCreateOrder" class="modal modal-fixed-footer">
    <form method="POST" action="?action=createOrder" id="formCreateOrder">
        <div class="modal-content">
            <h5 class="blue-text" style="font-weight: 600;"><i class="material-icons left">shopping_cart</i> Registrar Nuevo Pedido</h5>
            <p class="grey-text" style="margin-bottom: 25px;">Agrega productos y cantidades para generar la orden</p>

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="row">
                <div class="input-field col s12 m6">
                    <input type="text" name="customer_name" id="order_cust_name" required value="Cliente General">
                    <label for="order_cust_name" class="active">Nombre del Cliente</label>
                </div>
                <div class="input-field col s12 m6">
                    <select name="status" required>
                        <option value="Borrador" selected>Borrador (No afecta stock)</option>
                        <option value="Confirmado">Confirmado (Descuenta stock de inmediato)</option>
                        <option value="Entregado">Entregado (Descuenta stock y crea Ingreso Financiero)</option>
                    </select>
                    <label>Estado Inicial</label>
                </div>
            </div>

            <!-- Tabla de Ítems Dinámica -->
            <div style="margin-top: 20px;">
                <h6 style="font-weight: 600; color: #555;">Productos del Pedido</h6>
                <table class="striped highlight" id="orderItemsCreationTable" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Producto</th>
                            <th style="width: 15%;" class="right-align">Precio Unit. ($)</th>
                            <th style="width: 15%;" class="center-align">Cantidad</th>
                            <th style="width: 15%;" class="right-align">Subtotal ($)</th>
                            <th style="width: 5%;" class="center-align"></th>
                        </tr>
                    </thead>
                    <tbody id="orderItemsContainer">
                        <tr class="item-row">
                            <td>
                                <select name="products_ids[]" class="browser-default select-order-product" required style="display: block; width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 5px;">
                                    <option value="" disabled selected>Selecciona un producto</option>
                                    <?php foreach (($products ?? []) as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-price="<?= $p['sale_price'] ?>" data-stock="<?= $p['current_stock'] ?>">
                                            <?= htmlspecialchars($p['name']) ?> (Stock: <?= $p['current_stock'] ?> | Price: $<?= number_format($p['sale_price'], 0, ",", ".") ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="right-align">
                                <input type="text" name="prices[]" class="right-align input-item-price" style="margin: 0; height: 2rem;" required readonly>
                            </td>
                            <td class="center-align">
                                <input type="number" name="quantities[]" min="1" value="1" class="center-align input-item-qty" style="margin: 0; height: 2rem; width: 60px;" required>
                            </td>
                            <td class="right-align" style="font-weight: 600;">
                                $<span class="label-item-subtotal">0</span>
                            </td>
                            <td class="center-align">
                                <a href="#!" class="btn-flat btn-remove-item red-text" style="padding: 0;"><i class="material-icons">delete</i></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top: 15px;">
                    <a href="#!" class="btn-flat blue-text waves-effect" id="btnAddNewOrderItem" style="font-weight: 600;">
                        <i class="material-icons left">add</i> Agregar Producto
                    </a>
                </div>
            </div>

            <!-- Notas del Pedido -->
            <div class="input-field" style="margin-top: 30px;">
                <textarea name="notes" class="materialize-textarea" id="order_notes"></textarea>
                <label for="order_notes">Notas o Instrucciones Especiales</label>
            </div>

            <!-- Resumen de Totales -->
            <div class="row right-align" style="margin-top: 30px; background: #f5f5f5; padding: 15px; border-radius: 6px;">
                <div class="col s12">
                    <span class="grey-text" style="font-size: 1.1rem; margin-right: 15px;">Subtotal: <strong class="black-text">$<span id="orderSubtotalVal">0</span></strong></span>
                    <?php if (isset($_SESSION['company_tax_rate']) && $_SESSION['company_tax_rate'] > 0): ?>
                        <span class="grey-text" style="font-size: 1.1rem; margin-right: 15px;">IVA (<?= $_SESSION['company_tax_rate'] ?>%): <strong class="black-text">$<span id="orderTaxVal">0</span></strong></span>
                    <?php endif; ?>
                    <span style="font-size: 1.3rem; font-weight: 700; color: var(--primary);">Total Pedido: $<span id="orderTotalVal">0</span></span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn blue">Registrar Pedido</button>
            <a href="#!" class="modal-close btn grey">Cancelar</a>
        </div>
    </form>
</div>

<!-- Modal Ver Detalles del Pedido -->
<div id="modalViewOrderDetails" class="modal modal-fixed-footer">
    <div class="modal-content">
        <div class="row">
            <div class="col s12 m6">
                <h5 class="blue-text"><i class="material-icons left">receipt</i> Pedido: <span id="detailsOrderNumber" class="black-text" style="font-weight: 600;">-</span></h5>
                <p class="grey-text" style="margin: 0;">Cliente: <strong id="detailsCustomerName" class="black-text">-</strong></p>
                <p class="grey-text" style="margin: 0;">Fecha: <span id="detailsOrderDate" class="black-text">-</span></p>
            </div>
            <div class="col s12 m6 right-align">
                <span id="detailsOrderStatusBadge" class="badge" style="float: none; font-size: 1rem; border-radius: 4px; padding: 4px 10px; color: white;">-</span>
            </div>
        </div>

        <table class="striped highlight" style="margin-top: 25px;">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="right-align">Precio Unit. ($)</th>
                    <th class="center-align">Cantidad</th>
                    <th class="right-align">Subtotal ($)</th>
                </tr>
            </thead>
            <tbody id="detailsItemsContainer">
                <!-- Cargado dinámicamente vía AJAX -->
            </tbody>
        </table>

        <!-- Notas del pedido -->
        <div id="detailsNotesContainer" style="margin-top: 30px; padding: 15px; border-left: 4px solid var(--secondary); background: #fcfcfc; display: none;">
            <h6 style="margin: 0 0 5px 0; font-weight: 600;">Notas del Pedido:</h6>
            <p id="detailsNotesText" style="margin: 0;"></p>
        </div>

        <div class="row right-align" style="margin-top: 35px; border-top: 1px solid #ddd; padding-top: 15px;">
            <div class="col s12">
                <p style="margin: 0; font-size: 1rem;" class="grey-text">Subtotal: <strong class="black-text" id="detailsSubtotalText">$0</strong></p>
                <?php if (isset($_SESSION['company_tax_rate']) && $_SESSION['company_tax_rate'] > 0): ?>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;" class="grey-text">IVA (<?= $_SESSION['company_tax_rate'] ?>%): <strong class="black-text" id="detailsTaxText">$0</strong></p>
                <?php endif; ?>
                <h5 style="margin: 10px 0 0 0; font-weight: 700; color: var(--primary);">Total: <span id="detailsTotalText">$0</span></h5>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close btn grey">Cerrar</a>
    </div>
</div>

<!-- Form oculto de cambio de estado -->
<form id="formChangeOrderStatus" action="?action=updateOrderStatus" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="id" id="changeStatusOrderId">
    <input type="hidden" name="status" id="changeStatusOrderVal">
</form>

<?php
include __DIR__ . '/partials/footer.php';
include __DIR__ . '/partials/scripts.php';
?>