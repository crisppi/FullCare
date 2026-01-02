<?php
$pageTitle = 'Readmissao - Rede Hospitalar';
$pageSlug = 'bi/rede-readmissao';
require_once("templates/bi_rede_bootstrap.php");

$readmFilters = biRedeBuildWhere($filterValues, 'al.data_alta_alt', 'i', true);
$readmWhere = $readmFilters['where'];
$readmParams = $readmFilters['params'];
$readmJoins = $readmFilters['joins'];

$summaryStmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_altas,
        SUM(
            CASE WHEN EXISTS (
                SELECT 1
                FROM tb_internacao i2
                WHERE i2.fk_paciente_int = i.fk_paciente_int
                  AND i2.data_intern_int > al.data_alta_alt
                  AND i2.data_intern_int <= DATE_ADD(al.data_alta_alt, INTERVAL 30 DAY)
            ) THEN 1 ELSE 0 END
        ) AS readm30,
        SUM(
            CASE WHEN EXISTS (
                SELECT 1
                FROM tb_internacao i3
                WHERE i3.fk_paciente_int = i.fk_paciente_int
                  AND i3.data_intern_int > al.data_alta_alt
                  AND i3.data_intern_int <= DATE_ADD(al.data_alta_alt, INTERVAL 7 DAY)
            ) THEN 1 ELSE 0 END
        ) AS readm7
    FROM tb_alta al
    JOIN tb_internacao i ON i.id_internacao = al.fk_id_int_alt
    {$readmJoins}
    WHERE {$readmWhere}
");
$summaryStmt->execute($readmParams);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$totalAltas = (int)($summary['total_altas'] ?? 0);
$readm30 = (int)($summary['readm30'] ?? 0);
$readm7 = (int)($summary['readm7'] ?? 0);
$readm30Pct = $totalAltas > 0 ? ($readm30 / $totalAltas) * 100 : 0.0;
$readm7Pct = $totalAltas > 0 ? ($readm7 / $totalAltas) * 100 : 0.0;

$rowsStmt = $conn->prepare("
    SELECT
        h.nome_hosp AS hospital,
        COUNT(*) AS total_altas,
        SUM(
            CASE WHEN EXISTS (
                SELECT 1
                FROM tb_internacao i2
                WHERE i2.fk_paciente_int = i.fk_paciente_int
                  AND i2.data_intern_int > al.data_alta_alt
                  AND i2.data_intern_int <= DATE_ADD(al.data_alta_alt, INTERVAL 30 DAY)
            ) THEN 1 ELSE 0 END
        ) AS readm30,
        SUM(
            CASE WHEN EXISTS (
                SELECT 1
                FROM tb_internacao i3
                WHERE i3.fk_paciente_int = i.fk_paciente_int
                  AND i3.data_intern_int > al.data_alta_alt
                  AND i3.data_intern_int <= DATE_ADD(al.data_alta_alt, INTERVAL 7 DAY)
            ) THEN 1 ELSE 0 END
        ) AS readm7
    FROM tb_alta al
    JOIN tb_internacao i ON i.id_internacao = al.fk_id_int_alt
    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int
    {$readmJoins}
    WHERE {$readmWhere}
    GROUP BY h.id_hospital
    HAVING h.id_hospital IS NOT NULL
    ORDER BY readm30 DESC
    LIMIT 12
");
$rowsStmt->execute($readmParams);
$readmRows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$maiorReadm = '-';
$menorReadm = '-';
$alertas = 0;
foreach ($readmRows as $row) {
    $total = (int)($row['total_altas'] ?? 0);
    $r30 = (int)($row['readm30'] ?? 0);
    $rate30 = $total > 0 ? ($r30 / $total) * 100 : 0.0;
    if ($maiorReadm === '-' && $r30 > 0) {
        $maiorReadm = $row['hospital'] ?? '-';
    }
    if ($menorReadm === '-') {
        $menorReadm = $row['hospital'] ?? '-';
    } elseif ($rate30 < 3) {
        $menorReadm = $row['hospital'] ?? $menorReadm;
    }
    if ($rate30 >= 10) {
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
            <h1 class="bi-title">Readmissao</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Retorno do paciente apos alta.</div>
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
                <small>Readmissao 30d</small>
                <strong><?= fmtPct($readm30Pct, 1) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Readmissao 7d</small>
                <strong><?= fmtPct($readm7Pct, 1) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Hospitais criticos</small>
                <strong><?= fmtInt($alertas) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Casos analisados</small>
                <strong><?= fmtInt($totalAltas) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Readmissao por hospital</h3>
        <div class="bi-split">
            <div class="bi-placeholder">Grafico de readmissao por hospital.</div>
            <div class="bi-list">
                <div class="bi-list-item">
                    <span>Maior readmissao</span>
                    <strong><?= e($maiorReadm) ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Menor readmissao</span>
                    <strong><?= e($menorReadm) ?></strong>
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
                    <th>Readmissao 30d</th>
                    <th>Readmissao 7d</th>
                    <th>Casos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$readmRows): ?>
                    <tr>
                        <td colspan="4" class="bi-empty">Sem dados com os filtros atuais.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($readmRows as $row): ?>
                        <?php
                        $total = (int)($row['total_altas'] ?? 0);
                        $r30 = (int)($row['readm30'] ?? 0);
                        $r7 = (int)($row['readm7'] ?? 0);
                        $rate30 = $total > 0 ? ($r30 / $total) * 100 : 0.0;
                        $rate7 = $total > 0 ? ($r7 / $total) * 100 : 0.0;
                        ?>
                        <tr>
                            <td><?= e($row['hospital'] ?? 'Sem informacoes') ?></td>
                            <td><?= fmtPct($rate30, 1) ?></td>
                            <td><?= fmtPct($rate7, 1) ?></td>
                            <td><?= fmtInt($total) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
