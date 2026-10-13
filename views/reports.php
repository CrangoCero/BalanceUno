<?php
$title = "Reportes";
$useDataTables = true;
$pageTitle = "Reportes";
$pageIcon = "bar_chart";
$navColor = "reports-color";

$data = $data ?? [];
$data['incomes'] = $data['incomes'] ?? [];
$data['expenses'] = $data['expenses'] ?? [];
$data['totals'] = $data['totals'] ?? [];
$data['paymentSummary'] = $data['paymentSummary'] ?? [];

$extraStyles = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/navbar.php';
?>

<section id="main" class="container">
    <!-- Dashboard-box para filtros y resumen -->
    <div class="dashboard-box">
        <h5 style="font-weight: 700; color: var(--primary); margin-bottom: 25px;">Generación de Reportes</h5>

        <!-- Formulario de fechas -->
        <form method="POST" action="?action=reports" class="row" style="margin-bottom: 30px;">
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
                <button class="btn reports-color waves-effect waves-light action-btn" type="submit" style="width: 100%;">
                    <i class="material-icons left">analytics</i> Generar Reporte
                </button>
                <button class="btn green waves-effect waves-light action-btn" type="submit" formaction="?action=exportReportsXls" style="width: 100%; margin-top: 10px;">
                    <i class="material-icons left">file_download</i> Exportar Excel
                </button>
            </div>
        </form>

        <!-- Totales -->
        <div class="row totals-row">
            <div class="col s12 m6">
                <div class="card-panel secondary-color white-text">
                    <p>Total Ingresos</p>
                    <h5>$<?= number_format($data['totals']['totalIncomes'] ?? 0, 0, ",", ".") ?></h5>
                </div>
            </div>
            <div class="col s12 m6">
                <div class="card-panel error-color white-text">
                    <p>Total Gastos</p>
                    <h5>$<?= number_format($data['totals']['totalExpenses'] ?? 0, 0, ",", ".") ?></h5>
                </div>
            </div>
        </div>

        <!-- Gráficas -->
        <div class="row" style="margin-top: 40px;">
            <div class="col s12 l6">
                <div class="card-panel white" style="padding: 20px;">
                    <h6 class="reports-color-text" style="font-weight: 700; margin-bottom: 25px; text-align: center;">Distribución General</h6>
                    <canvas id="reportChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
            <div class="col s12 l6">
                <div class="card-panel white" style="padding: 20px;">
                    <h6 class="reports-color-text" style="font-weight: 700; margin-bottom: 25px; text-align: center;">Por Método de Pago</h6>
                    <canvas id="reportChartPaymentMethod" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla de detalle -->
        <div style="margin-top: 50px;">
            <h5 style="font-weight: 700; color: var(--primary); margin-bottom: 20px;">Desglose de Movimientos</h5>
            <table id="reportsTable" class="highlight display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th class="right-align">Monto</th>
                        <th>Método</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['incomes'] as $i): ?>
                        <tr>
                            <td style="font-weight: 500;"><?= date('d/m/Y', strtotime($i['date'])) ?></td>
                            <td><span class="badge accent-color white-text" style="float: none; border-radius: 4px;">Ingreso</span></td>
                            <td><?= htmlspecialchars($i['description']) ?></td>
                            <td class="right-align accent-color-text" style="font-weight: 600;">$<?= number_format($i['amount'], 0, ",", ".") ?></td>
                            <td><span class="badge grey lighten-3 black-text" style="float: none; border-radius: 4px;"><?= htmlspecialchars($i['payment_method']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($data['expenses'] as $e): ?>
                        <tr>
                            <td style="font-weight: 500;"><?= date('d/m/Y', strtotime($e['date'])) ?></td>
                            <td><span class="badge error-color white-text" style="float: none; border-radius: 4px;">Gasto</span></td>
                            <td><?= htmlspecialchars($e['description']) ?></td>
                            <td class="right-align error-color-text" style="font-weight: 600;">$<?= number_format($e['amount'], 0, ",", ".") ?></td>
                            <td><span class="badge grey lighten-3 black-text" style="float: none; border-radius: 4px;"><?= htmlspecialchars($e['payment_method']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/partials/footer.php';
?>
<script>
    const totalsData = {
        incomes: <?= json_encode($data['totals']['totalIncomes'] ?? 0) ?>,
        expenses: <?= json_encode($data['totals']['totalExpenses'] ?? 0) ?>
    };
    const paymentSummary = <?= json_encode($data['paymentSummary']) ?>;
</script>
<?php
include __DIR__ . '/partials/scripts.php';
?>