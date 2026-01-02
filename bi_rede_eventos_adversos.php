<?php
$pageTitle = 'Eventos Adversos - Rede Hospitalar';
$pageSlug = 'bi/rede-eventos-adversos';
require_once("templates/bi_rede_bootstrap.php");

$internFilters = biRedeBuildWhere($filterValues, 'i.data_intern_int', 'i', true);
$internWhere = $internFilters['where'];
$internParams = $internFilters['params'];
$internJoins = $internFilters['joins'];

$summaryStmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT i.id_internacao) AS casos,
        COUNT(DISTINCT g.fk_internacao_ges) AS eventos
    FROM tb_internacao i
    LEFT JOIN tb_gestao g ON g.fk_internacao_ges = i.id_internacao
    {$internJoins}
    WHERE {$internWhere}
      AND (g.id_gestao IS NULL OR LOWER(IFNULL(g.evento_adverso_ges,'')) IN ('s','n',''))
");
$summaryStmt->execute($internParams);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$casos = (int)($summary['casos'] ?? 0);
$eventos = (int)($summary['eventos'] ?? 0);
$eventosPct = $casos > 0 ? ($eventos / $casos) * 100 : 0.0;

$rowsStmt = $conn->prepare("
    SELECT
        h.nome_hosp AS hospital,
        COUNT(DISTINCT i.id_internacao) AS casos,
        COUNT(DISTINCT CASE WHEN LOWER(IFNULL(g.evento_adverso_ges,'')) = 's' THEN g.fk_internacao_ges END) AS eventos
    FROM tb_internacao i
    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int
    LEFT JOIN tb_gestao g ON g.fk_internacao_ges = i.id_internacao
    {$internJoins}
    WHERE {$internWhere}
    GROUP BY h.id_hospital
    HAVING h.id_hospital IS NOT NULL
    ORDER BY eventos DESC
    LIMIT 12
");
$rowsStmt->execute($internParams);
$eventoRows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$hospitalMaior = '-';
$hospitalSeguro = '-';
$alertas = 0;
foreach ($eventoRows as $row) {
    $rowCasos = (int)($row['casos'] ?? 0);
    $rowEventos = (int)($row['eventos'] ?? 0);
    $rate = $rowCasos > 0 ? ($rowEventos / $rowCasos) * 100 : 0.0;
    if ($hospitalMaior === '-' && $rowEventos > 0) {
        $hospitalMaior = $row['hospital'] ?? '-';
    }
    if ($hospitalSeguro === '-') {
        $hospitalSeguro = $row['hospital'] ?? '-';
    } elseif ($rate < 1) {
        $hospitalSeguro = $row['hospital'] ?? $hospitalSeguro;
    }
    if ($rate >= 5) {
        $alertas++;
    }
}
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260111">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260111"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>

<div class="bi-wrapper bi-theme">
    <div class="bi-header">
        <div>
            <h1 class="bi-title">Eventos Adversos</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Qualidade assistencial por hospital.</div>
        </div>
        <div class="bi-header-actions">
            <a class="bi-nav-icon" href="<?= $BASE_URL ?>bi/rede-comparativa" title="Comparativa da rede">
                <i class="bi bi-chevron-left"></i>
            </a>
            <a class="bi-nav-icon" href="<?= $BASE_URL ?>bi/navegacao" title="Navegacao BI">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        </div>
    </div>

    <?php include "templates/bi_rede_filters.php"; ?>

    <div class="bi-panel">
        <h3>Indicadores-chave</h3>
        <div class="bi-kpis kpi-grid-4">
            <div class="bi-kpi kpi-compact">
                <small>Taxa de eventos</small>
                <strong><?= fmtPct($eventosPct, 1) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Eventos graves</small>
                <strong><?= fmtInt($eventos) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Hospitais em alerta</small>
                <strong><?= fmtInt($alertas) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Casos analisados</small>
                <strong><?= fmtInt($casos) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Eventos por hospital</h3>
        <div class="bi-split">
            <div class="bi-placeholder">Grafico de eventos adversos por hospital.</div>
            <div class="bi-list">
                <div class="bi-list-item">
                    <span>Hospital com maior taxa</span>
                    <strong><?= e($hospitalMaior) ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Hospital mais seguro</span>
                    <strong><?= e($hospitalSeguro) ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Alertas ativos</span>
                    <strong><?= fmtInt($alertas) ?></strong>
                </div>
            </div>
        </div>
        <table class="bi-table" style="margin-top: 16px;">
            <thead>
                <tr>
                    <th>Hospital</th>
                    <th>Taxa de eventos</th>
                    <th>Eventos graves</th>
                    <th>Casos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$eventoRows): ?>
                    <tr>
                        <td colspan="4" class="bi-empty">Sem dados com os filtros atuais.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($eventoRows as $row): ?>
                        <?php
                        $rowCasos = (int)($row['casos'] ?? 0);
                        $rowEventos = (int)($row['eventos'] ?? 0);
                        $rate = $rowCasos > 0 ? ($rowEventos / $rowCasos) * 100 : 0.0;
                        ?>
                        <tr>
                            <td><?= e($row['hospital'] ?? 'Sem informacoes') ?></td>
                            <td><?= fmtPct($rate, 1) ?></td>
                            <td><?= fmtInt($rowEventos) ?></td>
                            <td><?= fmtInt($rowCasos) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
