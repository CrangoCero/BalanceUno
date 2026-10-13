<?php
$title = "Balance";
$useDataTables = true;
$pageTitle = "Balance";
$pageIcon = "assessment";
$navColor = "accent-color";

$data = $data ?? [
    'netBalance' => 0,
    'totalIncomes' => 0,
    'totalExpenses' => 0,
    'paymentSummary' => [
        'Ingresos' => [],
        'Gastos' => [],
    ],
    'incomes' => [],
    'expenses' => [],
];

$filters = $filters ?? [
    'startDate' => date('d/m/Y'),
    'endDate' => date('d/m/Y'),
];

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/navbar.php';
?>

<section id="main" class="container">
    <!-- Dashboard-box para filtros y resumen -->
    <div class="dashboard-box">
        <h5 style="font-weight: 700; color: var(--primary); margin-bottom: 25px;">Consulta de Balance</h5>

        <!-- Formulario de fechas -->
        <form method="POST" action="?action=balance" class="row" style="margin-bottom: 30px;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="input-field col s12 m4">
                <i class="material-icons prefix">calendar_today</i>
                <input type="text" id="startDate" name="startDate" class="datepicker" value="<?= htmlspecialchars($filters['startDate'] ?? date('d/m/Y')) ?>">
                <label for="startDate">Fecha inicio</label>
            </div>
            <div class="input-field col s12 m4">
                <i class="material-icons prefix">event</i>
                <input type="text" id="endDate" name="endDate" class="datepicker" value="<?= htmlspecialchars($filters['endDate'] ?? date('d/m/Y')) ?>">
                <label for="endDate">Fecha fin</label>
            </div>
            <div class="col s12 m4" style="margin-top: 15px;">
                <button class="btn accent-color waves-effect waves-light action-btn" type="submit" style="width: 100%;">
                    <i class="material-icons left">search</i> Filtrar
                </button>
                <button class="btn reports-color waves-effect waves-light action-btn" type="submit" formaction="?action=exportBalanceXls" style="width: 100%; margin-top: 10px;">
                    <i class="material-icons left">file_download</i> Exportar Excel
                </button>
            </div>
        </form>

        <!-- Totales -->
        <div class="row totals-row">
            <div class="col s12 m4">
                <div class="card-panel accent-color white-text">
                    <p>Balance Neto</p>
                    <h5>$<?= number_format($data['netBalance'], 0, ",", ".") ?></h5>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="card-panel secondary-color white-text">
                    <p>Ingresos</p>
                    <h5>$<?= number_format($data['totalIncomes'], 0, ",", ".") ?></h5>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="card-panel error-color white-text">
                    <p>Gastos</p>
                    <h5>$<?= number_format($data['totalExpenses'], 0, ",", ".") ?></h5>
                </div>
            </div>
        </div>

        <!-- Distribución por método de pago -->
        <div class="row" style="margin-top: 40px;">
            <div class="col s12 m6">
                <div class="card-panel white" style="padding: 20px;">
                    <h6 class="accent-color-text" style="font-weight: 700; margin-bottom: 20px;">
                        <i class="material-icons left">account_balance_wallet</i> Distribución Ingresos
                    </h6>
                    <ul class="collection" style="border: none;">
                        <?php foreach ($data['paymentSummary']['Ingresos'] as $method => $value): ?>
                            <li class="collection-item" style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-weight: 500;"><?= htmlspecialchars($method) ?></span>
                                <span class="accent-color-text" style="font-weight: 600;">$<?= number_format($value, 0, ",", ".") ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col s12 m6">
                <div class="card-panel white" style="padding: 20px;">
                    <h6 class="error-color-text" style="font-weight: 700; margin-bottom: 20px;">
                        <i class="material-icons left">payments</i> Distribución Gastos
                    </h6>
                    <ul class="collection" style="border: none;">
                        <?php foreach ($data['paymentSummary']['Gastos'] as $method => $value): ?>
                            <li class="collection-item" style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-weight: 500;"><?= htmlspecialchars($method) ?></span>
                                <span class="error-color-text" style="font-weight: 600;">$<?= number_format($value, 0, ",", ".") ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Detalle completo -->
        <div style="margin-top: 50px;">
            <h5 style="font-weight: 700; color: var(--primary); margin-bottom: 20px;">Detalle de Movimientos</h5>
            <table id="balanceTable" class="highlight display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th class="right-align">Monto</th>
                        <th>Método</th>
                        <th>Código</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Ingresos -->
                    <?php foreach ($data['incomes'] as $income): ?>
                        <tr>
                            <td style="font-weight: 500;"><?= date('d/m/Y', strtotime($income['date'])) ?></td>
                            <td><span class="badge accent-color white-text" style="float: none; border-radius: 4px;">Ingreso</span></td>
                            <td><?= htmlspecialchars($income['description']) ?></td>
                            <td class="right-align accent-color-text" style="font-weight: 600;">$<?= number_format($income['amount'], 0, ",", ".") ?></td>
                            <td><span class="badge grey lighten-3 black-text" style="float: none; border-radius: 4px;"><?= htmlspecialchars($income['payment_method']) ?></span></td>
                            <td class="grey-text"><?= $income['payment_method'] === 'Efectivo' ? '-' : htmlspecialchars($income['code']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <!-- Gastos -->
                    <?php foreach ($data['expenses'] as $expense): ?>
                        <tr>
                            <td style="font-weight: 500;"><?= date('d/m/Y', strtotime($expense['date'])) ?></td>
                            <td><span class="badge error-color white-text" style="float: none; border-radius: 4px;">Gasto</span></td>
                            <td><?= htmlspecialchars($expense['description']) ?></td>
                            <td class="right-align error-color-text" style="font-weight: 600;">$<?= number_format($expense['amount'], 0, ",", ".") ?></td>
                            <td><span class="badge grey lighten-3 black-text" style="float: none; border-radius: 4px;"><?= htmlspecialchars($expense['payment_method']) ?></span></td>
                            <td class="grey-text"><?= $expense['payment_method'] === 'Efectivo' ? '-' : htmlspecialchars($expense['code']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/partials/footer.php';
include __DIR__ . '/partials/scripts.php';
?>