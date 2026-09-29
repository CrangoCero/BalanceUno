<?php
$title = "Gastos";
$useDataTables = true;
$pageTitle = "Gastos";
$pageIcon = "trending_down";
$navColor = "error-color";

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/navbar.php';
?>

<section id="main" class="container">
    <!-- Dashboard-box para la tabla -->
    <div class="dashboard-box">
        <div class="row" style="margin-bottom: 30px;">
            <div class="col s12 m6">
                <h5 style="font-weight: 700; color: var(--primary);">Historial de Gastos</h5>
                <p class="grey-text">Controla todas tus salidas de dinero</p>
            </div>
            <div class="col s12 m6 right-align">
                <a href="#modalCreateExpense" class="btn error-color modal-trigger action-btn">
                    <i class="material-icons left">add_circle</i> Nuevo
                </a>
                <a href="#modalCreateLoanPayment" class="btn loans-color modal-trigger action-btn">
                    <i class="material-icons left">payments</i> Pago Préstamo
                </a>
                <a href="?action=exportExpensesXls" class="btn reports-color action-btn">
                    <i class="material-icons left">file_download</i> Excel
                </a>
            </div>
        </div>

        <table id="expensesTable" class="highlight display nowrap" style="width:100%">
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
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td style="font-weight: 500;"><?= !empty($expense['date']) ? date('d/m/Y', strtotime($expense['date'])) : '-' ?></td>
                        <td><?= htmlspecialchars($expense['description'] ?? '') ?></td>
                        <td>
                            <span class="grey-text text-darken-1" style="font-size: 0.9rem;">
                                <?= isset($expense['category_id'], $categoriesMap[$expense['category_id']]) ? htmlspecialchars($categoriesMap[$expense['category_id']]) : '-' ?>
                            </span>
                        </td>
                        <td class="right-align error-color-text" style="font-weight: 600;">
                            $<?= number_format((float)($expense['amount'] ?? 0), 0, ",", ".") ?>
                        </td>
                        <td>
                            <span class="badge grey lighten-3 black-text" style="float: none; border-radius: 4px;">
                                <?= htmlspecialchars($expense['payment_method'] ?? 'Efectivo') ?>
                            </span>
                        </td>
                        <td class="grey-text"><?= ($expense['payment_method'] ?? '') === 'Efectivo' ? '-' : htmlspecialchars($expense['code'] ?? '-') ?></td>
                        <td>
                            <?php if (($expense['payment_status'] ?? 'Pagado') === 'Pagado'): ?>
                                <span class="badge green white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Pagado</span>
                            <?php else: ?>
                                <span class="badge orange white-text" style="float: none; border-radius: 4px; padding: 2px 6px;">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="center-align">
                            <a href="<?= !empty($expense['loan_id']) ? '#modalEditLoanPayment' : '#modalEditExpense' ?>" class="btn-flat waves-effect modal-trigger edit-expense-btn" style="color: var(--info);"
                                data-id="<?= (int)$expense['id'] ?>"
                                data-date="<?= !empty($expense['date']) ? date('d/m/Y', strtotime($expense['date'])) : '' ?>"
                                data-description="<?= htmlspecialchars($expense['description'] ?? '') ?>"
                                data-amount="<?= htmlspecialchars((string)($expense['amount'] ?? 0)) ?>"
                                data-payment_method="<?= htmlspecialchars($expense['payment_method'] ?? 'Efectivo') ?>"
                                data-code="<?= htmlspecialchars($expense['code'] ?? '') ?>"
                                data-loan_id="<?= htmlspecialchars((string)($expense['loan_id'] ?? '')) ?>"
                                data-category_id="<?= htmlspecialchars((string)($expense['category_id'] ?? '')) ?>"
                                data-payment_status="<?= htmlspecialchars($expense['payment_status'] ?? 'Pagado') ?>">
                                <i class="material-icons">edit</i>
                            </a>
                            <a href="#modalDeleteExpense<?= $expense['id'] ?>" class="btn-flat waves-effect modal-trigger" style="color: var(--error);">
                                <i class="material-icons">delete</i>
                            </a>
                        </td>
                    </tr>
                    <!-- Modal Eliminar -->
                    <div id="modalDeleteExpense<?= $expense['id'] ?>" class="modal">
                        <div class="modal-content center-align">
                            <div style="background: #fee2e2; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <i class="material-icons error-color-text" style="font-size: 35px;">warning</i>
                            </div>
                            <h5 style="font-weight: 700;">¿Eliminar Gasto?</h5>
                            <p class="grey-text">Estás a punto de borrar: <br><strong><?= htmlspecialchars($expense['description']) ?></strong></p>
                            <form method="POST" action="?action=deleteExpense" style="margin-top: 30px;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id" value="<?= $expense['id'] ?>">
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

<!-- Modal Crear Gasto -->
<div id="modalCreateExpense" class="modal">
    <div class="modal-content">
        <h5 class="center-align error-color-text">
            <i class="material-icons left">add</i> Nuevo Gasto
        </h5>
        <form method="POST" action="?action=createExpense">
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
            <div class="input-field">
                <input type="text" name="code">
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
                <button type="submit" class="btn error-color">Guardar</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pago Préstamo-->
<div id="modalCreateLoanPayment" class="modal">
    <div class="modal-content">
        <h5 class="center-align loans-color-text">
            <i class="material-icons left">add</i> Pago a Préstamo
        </h5>
        <form method="POST" action="?action=createExpense">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="input-field">
                <select name="loan_id" required>
                    <option value="" disabled selected>Seleccione un préstamo</option>
                    <?php foreach ($loans as $loan): ?>
                        <option value="<?= $loan['id'] ?>"><?= $loan['loan'] ?> - $<?= number_format($loan['pendiente'], 0, ",", ".") ?> COP</option>
                    <?php endforeach ?>
                </select>
                <label>Seleccionar Préstamo</label>
            </div>
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
            <div class="input-field">
                <input type="text" name="code">
                <label>Código (Nequi o Transferencia)</label>
            </div>
            <div class="center-align">
                <button type="submit" class="btn loans-color">Guardar</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Gasto -->
<div id="modalEditExpense" class="modal">
    <div class="modal-content">
        <h5 class="center-align error-color-text">
            <i class="material-icons left">edit</i> Editar Gasto
        </h5>
        <form method="POST" action="?action=updateExpense">
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
                <select name="category_id" id="edit_expense_category_id">
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
            <div class="input-field">
                <input type="text" name="code">
                <label class="active">Código (Nequi o Transferencia)</label>
            </div>
            <div class="input-field">
                <select name="payment_status" id="edit_expense_payment_status" required>
                    <option value="Pagado">Pagado</option>
                    <option value="Pendiente">Pendiente</option>
                </select>
                <label>Estado de Pago</label>
            </div>
            <div class="center-align" style="margin-top: 20px;">
                <button type="submit" class="btn error-color">Actualizar</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Pago Préstamo -->
<div id="modalEditLoanPayment" class="modal">
    <div class="modal-content">
        <h5 class="center-align loans-color-text">
            <i class="material-icons left">edit</i> Editar Pago Préstamo
        </h5>
        <form method="POST" action="?action=updateExpense">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id">

            <div class="input-field">
                <input type="hidden" name="loan_id" id="edit_loan_id_hidden">
                <select id="modal_edit_loan_id" required>
                    <option value="" disabled>Seleccione un préstamo</option>
                    <?php foreach ($loans as $loan): ?>
                        <option value="<?= $loan['id'] ?>"><?= htmlspecialchars($loan['loan']) ?> - $<?= number_format($loan['pendiente'], 0, ",", ".") ?> COP</option>
                    <?php endforeach ?>
                </select>
                <label>Préstamo al que se abona</label>
            </div>
            <div class="input-field">
                <input type="text" class="datepicker" name="date" required>
                <label class="active">Fecha</label>
            </div>
            <div class="input-field">
                <input type="text" name="amount" required>
                <label class="active">Monto (COP)</label>
            </div>

            <!-- Hidden que sí se envía -->
            <input type="hidden" name="payment_method" id="edit_loan_payment_method_hidden">
            <div class="input-field">
                <select id="modal_edit_loan_payment_method" required>
                    <option value="" disabled>Elige método</option>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Nequi">Nequi</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
                <label>Método de pago</label>
            </div>
            <div class="input-field">
                <input type="text" name="code">
                <label class="active">Código (Nequi o Transferencia)</label>
            </div>
            <div class="center-align">
                <button type="submit" class="btn loans-color">Actualizar Pago</button>
                <a href="#!" class="modal-close btn grey">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . '/partials/footer.php';
include __DIR__ . '/partials/scripts.php';
?>