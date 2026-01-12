<?php
include_once("check_logado.php");
require_once("templates/header.php");

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Conexão inválida.");
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$anoInput = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT);
$ano = ($anoInput !== null && $anoInput !== false) ? (int)$anoInput : null;
$mes = (int)(filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT) ?: 0);
$hospitalId = filter_input(INPUT_GET, 'hospital_id', FILTER_VALIDATE_INT) ?: null;

$hospitais = $conn->query("SELECT id_hospital, nome_hosp FROM tb_hospital ORDER BY nome_hosp")
    ->fetchAll(PDO::FETCH_ASSOC);

if ($ano === null && !filter_has_var(INPUT_GET, 'ano')) {
    $stmtAno = $conn->query("
        SELECT MAX(YEAR(data_inicio_neg)) AS ano
        FROM tb_negociacao
        WHERE data_inicio_neg IS NOT NULL
          AND data_inicio_neg <> '0000-00-00'
    ");
    $anoDb = $stmtAno->fetch(PDO::FETCH_ASSOC) ?: [];
    $ano = (int)($anoDb['ano'] ?? date('Y'));
}

$where = "YEAR(ng.data_inicio_neg) = :ano";
$params = [':ano' => $ano];
if ($mes > 0) {
    $where .= " AND MONTH(ng.data_inicio_neg) = :mes";
    $params[':mes'] = $mes;
}
if ($hospitalId) {
    $where .= " AND i.fk_hospital_int = :hospital_id";
    $params[':hospital_id'] = $hospitalId;
}

$sqlSummary = "
    SELECT
        SUM(ng.saving) AS total_saving,
        COUNT(*) AS total_registros
    FROM tb_negociacao ng
    INNER JOIN tb_internacao i ON i.id_internacao = ng.fk_id_int
    WHERE {$where}
";
$stmt = $conn->prepare($sqlSummary);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_INT);
}
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$totalSaving = (float)($summary['total_saving'] ?? 0);
$totalRegistros = (int)($summary['total_registros'] ?? 0);

$sqlAuditor = "
    SELECT
        COALESCE(u.usuario_user, 'Sem auditor') AS auditor,
        SUM(ng.saving) AS total_saving,
        COUNT(*) AS total_registros,
        AVG(ng.saving) AS media_saving
    FROM tb_negociacao ng
    INNER JOIN tb_internacao i ON i.id_internacao = ng.fk_id_int
    LEFT JOIN tb_user u ON u.id_usuario = ng.fk_usuario_neg
    WHERE {$where}
    GROUP BY auditor
    ORDER BY total_saving DESC
    LIMIT 12
";
$stmt = $conn->prepare($sqlAuditor);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_INT);
}
$stmt->execute();
$auditorRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$sqlMonthly = "
    SELECT
        DATE_FORMAT(ng.data_inicio_neg, '%Y-%m') AS mes,
        SUM(ng.saving) AS total_saving
    FROM tb_negociacao ng
    INNER JOIN tb_internacao i ON i.id_internacao = ng.fk_id_int
    WHERE {$where}
    GROUP BY mes
    ORDER BY mes ASC
";
$stmt = $conn->prepare($sqlMonthly);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_INT);
}
$stmt->execute();
$monthlyRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$months = array_map(fn($r) => $r['mes'], $monthlyRows);
$mensalSaving = array_map(fn($r) => (float)$r['total_saving'], $monthlyRows);
$auditorLabels = array_map(fn($r) => $r['auditor'] ?: 'Sem auditor', $auditorRows);
$auditorSaving = array_map(fn($r) => (float)$r['total_saving'], $auditorRows);

function fmtMoney($value)
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function fmtInt($value)
{
    return number_format((int)$value, 0, ',', '.');
}

?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260111">
<script src="diversos/chartjs/Chart.min.js"></script>
<script src="<?= $BASE_URL ?>js/bi.js?v=20260111"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>

<div class="bi-wrapper bi-theme">
    <div class="bi-header">
        <div>
            <h1 class="bi-title">Saving por Auditor</h1>
            <div class="text-muted">Comparativo de savings registrados por cada auditor.</div>
        </div>
        <div class="bi-header-actions">
            <a class="bi-nav-icon" href="<?= $BASE_URL ?>bi/navegacao" title="Navegação BI">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        </div>
    </div>

    <form class="bi-panel bi-filters" method="get">
        <div class="bi-filter">
            <label>Ano</label>
            <input type="number" name="ano" value="<?= e($ano) ?>">
        </div>
        <div class="bi-filter">
            <label>Mês</label>
            <select name="mes">
                <option value="0">Todos</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $mes === $m ? 'selected' : '' ?>><?= $m ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label>Hospital</label>
            <select name="hospital_id">
                <option value="">Todos</option>
                <?php foreach ($hospitais as $h): ?>
                    <option value="<?= (int)$h['id_hospital'] ?>" <?= $hospitalId == $h['id_hospital'] ? 'selected' : '' ?>>
                        <?= e($h['nome_hosp']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-actions">
            <button class="bi-btn" type="submit">Aplicar</button>
        </div>
    </form>

    <div class="bi-panel">
        <div class="bi-kpis">
            <div class="bi-kpi">
                <small>Total saving</small>
                <strong><?= fmtMoney($totalSaving) ?></strong>
            </div>
            <div class="bi-kpi">
                <small>Registros</small>
                <strong><?= fmtInt($totalRegistros) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Evolução mensal do saving</h3>
        <div class="bi-chart">
            <canvas id="chartSavingMensalAuditor"></canvas>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Saving agregado por auditor</h3>
        <div class="bi-chart">
            <canvas id="chartSavingPorAuditor"></canvas>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Top auditors</h3>
        <div class="table-responsive">
            <table class="bi-table">
                <thead>
                    <tr>
                        <th>Auditor</th>
                        <th>Saving (R$)</th>
                        <th>Registros</th>
                        <th>Média</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$auditorRows): ?>
                        <tr>
                            <td colspan="4" class="bi-empty">Sem dados com os filtros atuais.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($auditorRows as $row): ?>
                            <tr>
                                <td><?= e($row['auditor']) ?></td>
                                <td><?= fmtMoney((float)($row['total_saving'] ?? 0)) ?></td>
                                <td><?= fmtInt((int)($row['total_registros'] ?? 0)) ?></td>
                                <td><?= fmtMoney((float)($row['media_saving'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const labelsAuditor = <?= json_encode($auditorLabels) ?>;
    const savingPerAuditor = <?= json_encode($auditorSaving) ?>;
    const mensalLabels = <?= json_encode($months) ?>;
    const mensalSaving = <?= json_encode($mensalSaving) ?>;

    function barChart(ctx, labels, data, color) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: color,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: window.biChartScales ? window.biChartScales() : undefined,
                legend: { display: false },
            },
        });
    }

    function lineChart(ctx, labels, data, color) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Saving (R$)',
                    data,
                    borderColor: color,
                    backgroundColor: 'rgba(0,0,0,0)',
                    fill: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: window.biChartScales ? window.biChartScales() : undefined,
                legend: { display: false },
            },
        });
    }

    barChart(document.getElementById('chartSavingPorAuditor'), labelsAuditor, savingPerAuditor, 'rgba(122, 180, 255, 0.7)');
    lineChart(document.getElementById('chartSavingMensalAuditor'), mensalLabels, mensalSaving, 'rgba(85, 209, 194, 0.9)');
</script>

<?php require_once("templates/footer.php"); ?>
