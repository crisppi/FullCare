<?php
$pageTitle = 'Taxa de Glosa - Rede Hospitalar';
$pageSlug = 'bi/rede-glosa';
require_once("templates/bi_rede_bootstrap.php");

$capeanteDateExpr = "COALESCE(NULLIF(ca.data_inicial_capeante,'0000-00-00'), NULLIF(ca.data_digit_capeante,'0000-00-00'), NULLIF(ca.data_fech_capeante,'0000-00-00'))";
$capFilters = biRedeBuildWhere($filterValues, $capeanteDateExpr, 'i', true);
$capWhere = $capFilters['where'];
$capParams = $capFilters['params'];
$capJoins = $capFilters['joins'];

$summaryStmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT ca.fk_int_capeante) AS casos,
        SUM(COALESCE(ca.valor_apresentado_capeante,0)) AS valor_apresentado,
        SUM(COALESCE(ca.valor_glosa_total,0)) AS valor_glosa
    FROM tb_capeante ca
    JOIN tb_internacao i ON i.id_internacao = ca.fk_int_capeante
    {$capJoins}
    WHERE {$capWhere}
");
$summaryStmt->execute($capParams);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$casos = (int)($summary['casos'] ?? 0);
$valorApresentado = (float)($summary['valor_apresentado'] ?? 0);
$valorGlosa = (float)($summary['valor_glosa'] ?? 0);
$glosaPct = $valorApresentado > 0 ? ($valorGlosa / $valorApresentado) * 100 : 0.0;
$conformidade = max(0, 100 - $glosaPct);

$rowsStmt = $conn->prepare("
    SELECT
        h.nome_hosp AS hospital,
        COUNT(DISTINCT ca.fk_int_capeante) AS casos,
        SUM(COALESCE(ca.valor_apresentado_capeante,0)) AS valor_apresentado,
        SUM(COALESCE(ca.valor_glosa_total,0)) AS valor_glosa
    FROM tb_capeante ca
    JOIN tb_internacao i ON i.id_internacao = ca.fk_int_capeante
    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int
    {$capJoins}
    WHERE {$capWhere}
    GROUP BY h.id_hospital
    HAVING h.id_hospital IS NOT NULL
    ORDER BY valor_glosa DESC
    LIMIT 12
");
$rowsStmt->execute($capParams);
$glosaRows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$hospitalMaiorGlosa = '-';
$hospitalMaisConforme = '-';
$alertas = 0;
foreach ($glosaRows as $row) {
    $rowAp = (float)($row['valor_apresentado'] ?? 0);
    $rowGl = (float)($row['valor_glosa'] ?? 0);
    $rate = $rowAp > 0 ? ($rowGl / $rowAp) * 100 : 0.0;
    if ($hospitalMaiorGlosa === '-' && $rowGl > 0) {
        $hospitalMaiorGlosa = $row['hospital'] ?? '-';
    }
    if ($hospitalMaisConforme === '-') {
        $hospitalMaisConforme = $row['hospital'] ?? '-';
    } elseif ($rate < 1) {
        $hospitalMaisConforme = $row['hospital'] ?? $hospitalMaisConforme;
    }
    if ($rate >= 15) {
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
            <h1 class="bi-title">Taxa de Glosa por Hospital</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Conformidade, perdas e impacto financeiro.</div>
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
                <small>Taxa media de glosa</small>
                <strong><?= fmtPct($glosaPct, 1) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Valor glosado</small>
                <strong><?= fmtMoney($valorGlosa) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Conformidade</small>
                <strong><?= fmtPct($conformidade, 1) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Casos analisados</small>
                <strong><?= fmtInt($casos) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Glosa por hospital</h3>
        <div class="bi-split">
            <div class="bi-placeholder">Grafico de glosa por hospital.</div>
            <div class="bi-list">
                <div class="bi-list-item">
                    <span>Hospital com maior glosa</span>
                    <strong><?= e($hospitalMaiorGlosa) ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Hospital mais conforme</span>
                    <strong><?= e($hospitalMaisConforme) ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Alertas criticos</span>
                    <strong><?= fmtInt($alertas) ?></strong>
                </div>
            </div>
        </div>
        <table class="bi-table" style="margin-top: 16px;">
            <thead>
                <tr>
                    <th>Hospital</th>
                    <th>Taxa de glosa</th>
                    <th>Valor glosado</th>
                    <th>Conformidade</th>
                    <th>Casos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$glosaRows): ?>
                    <tr>
                        <td colspan="5" class="bi-empty">Sem dados com os filtros atuais.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($glosaRows as $row): ?>
                        <?php
                        $rowAp = (float)($row['valor_apresentado'] ?? 0);
                        $rowGl = (float)($row['valor_glosa'] ?? 0);
                        $rowCasos = (int)($row['casos'] ?? 0);
                        $rate = $rowAp > 0 ? ($rowGl / $rowAp) * 100 : 0.0;
                        $conf = max(0, 100 - $rate);
                        ?>
                        <tr>
                            <td><?= e($row['hospital'] ?? 'Sem informacoes') ?></td>
                            <td><?= fmtPct($rate, 1) ?></td>
                            <td><?= fmtMoney($rowGl) ?></td>
                            <td><?= fmtPct($conf, 1) ?></td>
                            <td><?= fmtInt($rowCasos) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
