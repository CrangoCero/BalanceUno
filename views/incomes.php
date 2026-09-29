<?php
$title = "Ingresos";
$useDataTables = true;
$pageTitle = "Ingresos";
$pageIcon = "trending_up";

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/navbar.php';
?>

<section id="main" class="container">
    <!-- Dashboard-box para la tabla -->
    <div class="dashboard-box">
        <div class="row" style="margin-bottom: 30px;">
            <div class="col s12 m6">
                <h5 style="font-weight: 700; color: var(--primary);">Historial de Ingresos</h5>
                <p class="grey-text">Gestiona todas tus entradas de dinero</p>
            </div>
            <div class="col s12 m6 right-align">
                <a href="#modalCreateIncome" class="btn secondary-color modal-trigger action-btn">
                    <i class="material-icons left">add</i> Nuevo
                </a>
                <a href="#modalCreateIncomeLoan" class="btn loans-color modal-trigger action-btn">
                    <i class="material-icons left">sync</i> Préstamo
                </a>
                <a href="?action=exportIncomesXls" class="btn reports-color action-btn">
                    <i class="material-icons left">file_download</i> Excel
                </a>
            </div>
        </div>

        <table id="incomesTable" class="highlight display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Categoría</th>
                    <th class="right-align">Monto</th>
                    <th>Método</th>
                    <th>Código</th>
                    <th>Estado</th>
                    <th class="center-align">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incomes as $income): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= !empty($income['date']) ? date('d/m/Y', strtotime($income['date'])) : '-' ?></td>
                        <td><?= htmlspecialchars($income['description'] ?? '') ?></td>
                        <td>
                            <span class="grey-text text-darken-1" style="font-size: 0.9rem;">
                                <?= isset($income['category_id'], $categoriesMap[$income['category_id']]) ? htmlspecialchars($categoriesMap[$income['category_id']]) : '-' ?>
                            </span>
                        </td>
                        <td class="right-align accent-color-text" style="font-weight: 600;">
                            $<?= number_format((float)($income['amount'] ?? 0), 0, ",", ".") ?>
                        </td>
                        <td>
                            <span class="badge grey lighten-3 black-text" style="float: none; border-radius: 4px;">
                                <?= htmlspecialchars($income['payment_method'] ?? 'Efectivo') ?>
                            </span>
                        </td>
                        <td class="grey-text"><?= ($income['payment_method'] ?? '') === 'Efectivo' ? '-' : htmlspecialchars($income['code'] ?? '-') ?></td>
                        <td>
                            <?php if (($income['payment_status'] ?? 'Pagado') === 'Pagado'): ?>
                                <span class="badge green white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Pagado</span>
                            <?php else: ?>
                                <span class="badge orange white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="center-align">
                            <a href="#modalEditIncome" class="btn-flat waves-effect modal-trigger edit-income-btn" style="color: var(--info);"
                                data-id="<?= (int)$income['id'] ?>"
                                data-date="<?= !empty($income['date']) ? date('d/m/Y', strtotime($income['date'])) : '' ?>"
                                data-description="<?= htmlspecialchars($income['description'] ?? '') ?>"
                                data-amount="<?= htmlspecialchars((string)($income['amount'] ?? 0)) ?>"
                                data-payment_method="<?= htmlspecialchars($income['payment_method'] ?? 'Efectivo') ?>"
                                data-code="<?= htmlspecialchars($income['code'] ?? '') ?>"
                                data-category_id="<?= htmlspecialchars((string)($income['category_id'] ?? '')) ?>"
                                data-payment_status="<?= htmlspecialchars($income['payment_status'] ?? 'Pagado') ?>">
                                <i class="material-icons">edit</i>
                            </a>
                            <a href="#modalDeleteIncome<?= $income['id'] ?>" class="btn-flat waves-effect modal-trigger" style="color: var(--error);">
                                <i class="material-icons">delete</i>
                            </a>
                        </td>
                    </tr>
                    <!-- Modal Eliminar -->
                    <div id="modalDeleteIncome<?= $income['id'] ?>" class="modal">
                        <div class="modal-content center-align">
                            <div style="background: #fee2e2; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <i class="material-icons error-color-text" style="font-size: 35px;">warning</i>
                            </div>
                            <h5 style="font-weight: 700;">¿Eliminar Ingreso?</h5>
                            <p class="grey-text">Estás a punto de borrar: <br><strong><?= htmlspecialchars($income['description']) ?></strong></p>
                            <form method="POST" action="?action=deleteIncome" style="margin-top: 30px;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id" value="<?= $income['id'] ?>">
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

<!-- Modal Crear Ingreso -->
<div id="modalCreateIncome" class="modal">
    <div class="modal-content">
        <h5 class="center-align secondary-color-text">
            <i class="material-icons left">add</i> Nuevo Ingreso
        </h5>
        <form method="POST" action="?action=createIncome">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="input-field">
                <input type="text" class="datepicker" name="date" value="<?= date('d/m/Y') ?>" required>
                <label>Fecha</label>
            </div>
            <div class="input-field">
                <input type="text" name="description" required>
                <label>Descripción</label>
            </div>
            <div class="input-field">
                <select name="category_id">
                    <option value="" selected>Ninguna</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Categoría</label>
            </div>
            <div class="input-field">
                <input type="text" name="amount" required>
                <label>Monto (COP)</label>
            </div>
            <div class="input-field">
                <select name="payment_method" required>
                    <option value="" disabled selected>Método de pago</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Nequi">Nequi</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
                <label>Método de pago</label>
            </div>
            <div class="input-field" style="z-index: 99 !important;">
                <input type="text" name="code" style="z-index: 99 !important;">
                <label>Código (Nequi o Transferencia)</label>
            </div>
            <div class="input-field">
                <select name="payment_status" required>
                    <option value="Pagado" selected>Pagado</option>
                    <option value="Pendiente">Pendiente</option>
                </select>
                <label>Estado de Pago</label>
            </div>
            <div class="center-align" style="margin-top: 20px;">
                <button type="submit" class="btn secondary-color">Guardar</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Crear Ingreso Préstamo -->
<div id="modalCreateIncomeLoan" class="modal">
    <div class="modal-content">
        <h5 class="center-align loans-color-text">
            <i class="material-icons left">add</i> Ingreso Préstamo
        </h5>
        <form method="POST" action="?action=createLoan">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="input-field">
                <input type="text" class="datepicker" name="date" value="<?= date('d/m/Y') ?>" required>
                <label>Fecha</label>
            </div>
            <div class="input-field">
                <input type="text" name="amount" required>
                <label>Monto (COP)</label>
            </div>
            <div class="input-field">
                <select name="payment_method" required>
                    <option value="" disabled selected>Método de pago</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Nequi">Nequi</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
                <label>Método de pago</label>
            </div>
            <div class="input-field" style="z-index: 99 !important;">
                <input type="text" name="code" style="z-index: 99 !important;">
                <label>Código (Nequi o Transferencia)</label>
            </div>
            <div class="center-align">
                <button type="submit" class="btn loans-color">Guardar</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Ingreso -->
<div id="modalEditIncome" class="modal">
    <div class="modal-content">
        <h5 class="center-align secondary-color-text">
            <i class="material-icons left">edit</i> Editar Ingreso
        </h5>
        <form method="POST" action="?action=updateIncome">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id">
            <div class="input-field">
                <input type="text" class="datepicker" name="date" required>
                <label class="active">Fecha</label>
            </div>
            <div class="input-field">
                <input type="text" name="description" required>
                <label class="active">Descripción</label>
            </div>
            <div class="input-field">
                <select name="category_id" id="edit_income_category_id">
                    <option value="">Ninguna</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Categoría</label>
            </div>
            <div class="input-field">
                <input type="text" name="amount" required>
                <label class="active">Monto (COP)</label>
            </div>
            <!-- Hidden que sí se envía -->
            <input type="hidden" name="payment_method" id="payment_method_hidden">
            <div class="input-field">
                <select id="modal_payment_method" required>
                    <option value="" disabled>Elige método</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Nequi">Nequi</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
                <label>Método de pago</label>
            </div>
            <div class="input-field" style="z-index: 99 !important;">
                <input type="text" name="code" style="z-index: 99 !important;">
                <label class="active">Código (Nequi o Transferencia)</label>
            </div>
            <div class="input-field">
                <select name="payment_status" id="edit_income_payment_status" required>
                    <option value="Pagado">Pagado</option>
                    <option value="Pendiente">Pendiente</option>
                </select>
                <label>Estado de Pago</label>
            </div>
            <div class="center-align" style="margin-top: 20px;">
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