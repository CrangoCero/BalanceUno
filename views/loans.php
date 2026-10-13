<?php
$title = "Prestamos";
$useDataTables = true;
$pageTitle = "Prestamos";
$pageIcon = "sync";
$navColor = "loans-color";


include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/navbar.php';
?>

<section id="main" class="container">
    <!-- Dashboard-box para la tabla -->
    <div class="dashboard-box">
        <div class="row" style="margin-bottom: 30px;">
            <div class="col s12 m6">
                <h5 style="font-weight: 700; color: var(--primary);">Gestión de Préstamos</h5>
                <p class="grey-text">Seguimiento de deudas y saldos pendientes</p>
            </div>
        </div>

        <table id="loansTable" class="highlight display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Préstamo</th>
                    <th class="right-align">Monto</th>
                    <th class="right-align">Saldo</th>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th>Código</th>
                    <th class="center-align">Estado</th>
                    <th class="center-align">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $loans = $loans ?? []; ?>
                <?php foreach ($loans as $loan): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($loan['loan']) ?></td>
                        <td class="right-align accent-color-text" style="font-weight: 500;">
                            $<?= number_format($loan['amount'], 0, ",", ".") ?>
                        </td>
                        <td class="right-align error-color-text" style="font-weight: 700;">
                            $<?= number_format($loan['saldo'], 0, ",", ".") ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($loan['date'])) ?></td>
                        <td>
                            <span class="badge grey lighten-3 black-text" style="float: none; border-radius: 4px;">
                                <?= htmlspecialchars($loan['payment_method']) ?>
                            </span>
                        </td>
                        <td class="grey-text"><?= $loan['payment_method'] === 'Efectivo' ? '-' : htmlspecialchars($loan['code']) ?></td>
                        <td class="center-align">
                            <?php if ($loan['status'] === 'Pendiente'): ?>
                                <span class="badge red lighten-4 red-text text-darken-4" style="float: none; border-radius: 20px; padding: 0 12px; font-weight: 600;">Pendiente</span>
                            <?php else: ?>
                                <span class="badge green lighten-4 green-text text-darken-4" style="float: none; border-radius: 20px; padding: 0 12px; font-weight: 600;">Pagado</span>
                            <?php endif; ?>
                        </td>
                        <td class="center-align">
                            <a href="#modalLoanHistory" class="btn-flat waves-effect modal-trigger btn-view-history" style="color: var(--loans);"
                                data-id="<?= $loan['id'] ?>"
                                data-loan="<?= htmlspecialchars($loan['loan']) ?>">
                                <i class="material-icons">list_alt</i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Modal Historial de Pagos -->
<div id="modalLoanHistory" class="modal bottom-sheet" style="max-height: 80%; border-radius: 20px 20px 0 0 !important;">
    <div class="modal-content">
        <h5 class="loans-color-text" style="margin-bottom: 20px; font-weight: 700;">
            <i class="material-icons left">history</i> Historial de Pagos: <span id="historyLoanName"></span>
        </h5>
        
        <div class="table-container" style="overflow-x: auto;">
            <table id="loanHistoryTable" class="highlight display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th>Código</th>
                        <th class="right-align">Monto Abonado</th>
                    </tr>
                </thead>
                <tbody id="loanHistoryBody">
                    <!-- Los datos se insertarán aquí por AJAX -->
                </tbody>
            </table>
        </div>
        
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
    </div>
</div>

<?php
include __DIR__ . '/partials/footer.php';
include __DIR__ . '/partials/scripts.php';
?>