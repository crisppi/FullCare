<?php
$pageTitle = 'Custo por Caso - Rede Hospitalar';
$pageSlug = 'bi/rede-custo';
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
        SUM(COALESCE(ca.valor_final_capeante,0)) AS valor_final
    FROM tb_capeante ca
    JOIN tb_internacao i ON i.id_internacao = ca.fk_int_capeante
    {$capJoins}
    WHERE {$capWhere}
");
$summaryStmt->execute($capParams);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$casos = (int)($summary['casos'] ?? 0);
$valorApresentado = (float)($summary['valor_apresentado'] ?? 0);
$valorFinal = (float)($summary['valor_final'] ?? 0);
$custoAp = $casos > 0 ? ($valorApresentado / $casos) : 0.0;
$custoFin = $casos > 0 ? (($valorFinal > 0 ? $valorFinal : $valorApresentado) / $casos) : 0.0;
$deltaMedio = $custoAp - $custoFin;

$rowsStmt = $conn->prepare("
    SELECT
        h.nome_hosp AS hospital,
        COUNT(DISTINCT ca.fk_int_capeante) AS casos,
        SUM(COALESCE(ca.valor_apresentado_capeante,0)) AS valor_apresentado,
        SUM(COALESCE(ca.valor_final_capeante,0)) AS valor_final
    FROM tb_capeante ca
    JOIN tb_internacao i ON i.id_internacao = ca.fk_int_capeante
    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int
    {$capJoins}
    WHERE {$capWhere}
    GROUP BY h.id_hospital
    HAVING h.id_hospital IS NOT NULL
    ORDER BY valor_final DESC
    LIMIT 12
");
$rowsStmt->execute($capParams);
$costRows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$maiorDiff = 0.0;
$melhorCusto = null;
$acimaTeto = 0;
foreach ($costRows as $row) {
    $rowCasos = (int)($row['casos'] ?? 0);
    if ($rowCasos <= 0) {
        continue;
    }
    $ap = (float)($row['valor_apresentado'] ?? 0);
    $fin = (float)($row['valor_final'] ?? 0);
    $avgAp = $ap / $rowCasos;
    $avgFin = ($fin > 0 ? $fin : $ap) / $rowCasos;
    if ($avgAp > 0) {
        $pct = (($avgAp - $avgFin) / $avgAp) * 100;
        $maiorDiff = max($maiorDiff, $pct);
    }
    if ($melhorCusto === null || $avgFin < $melhorCusto) {
        $melhorCusto = $avgFin;
    }
    if ($custoFin > 0 && $avgFin > ($custoFin * 1.15)) {
        $acimaTeto++;
    }
}
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260111">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260111"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>

<div class="bi-wrapper bi-theme">
    <div class="bi-header">
        <div>
            <h1 class="bi-title">Custo por Caso</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Comparativo de valor apresentado vs. final autorizado.</div>
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
                <small>Custo apresentado medio</small>
                <strong><?= fmtMoney($custoAp) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Custo final medio</small>
                <strong><?= fmtMoney($custoFin) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Delta medio</small>
                <strong><?= fmtMoney($deltaMedio) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Casos analisados</small>
                <strong><?= fmtInt($casos) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Comparativo por hospital</h3>
        <div class="bi-split">
            <div class="bi-placeholder">Grafico comparativo sera exibido aqui.</div>
            <div class="bi-list">
                <div class="bi-list-item">
                    <span>Maior diferenca</span>
                    <strong><?= fmtPct($maiorDiff, 1) ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Melhor custo final</span>
                    <strong><?= $melhorCusto !== null ? fmtMoney($melhorCusto) : '-' ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Hospitais acima do teto</span>
                    <strong><?= fmtInt($acimaTeto) ?></strong>
                </div>
            </div>
        </div>
        <table class="bi-table" style="margin-top: 16px;">
            <thead>
                <tr>
                    <th>Hospital</th>
                    <th>Custo apresentado</th>
                    <th>Custo final</th>
                    <th>Delta</th>
                    <th>Casos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$costRows): ?>
                    <tr>
                        <td colspan="5" class="bi-empty">Sem dados com os filtros atuais.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($costRows as $row): ?>
                        <?php
                        $rowCasos = (int)($row['casos'] ?? 0);
                        $rowAp = (float)($row['valor_apresentado'] ?? 0);
                        $rowFin = (float)($row['valor_final'] ?? 0);
                        $avgAp = $rowCasos > 0 ? ($rowAp / $rowCasos) : 0.0;
                        $avgFin = $rowCasos > 0 ? (($rowFin > 0 ? $rowFin : $rowAp) / $rowCasos) : 0.0;
                        $delta = $avgAp - $avgFin;
                        ?>
                        <tr>
                            <td><?= e($row['hospital'] ?? 'Sem informacoes') ?></td>
                            <td><?= fmtMoney($avgAp) ?></td>
                            <td><?= fmtMoney($avgFin) ?></td>
                            <td><?= fmtMoney($delta) ?></td>
                            <td><?= fmtInt($rowCasos) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
