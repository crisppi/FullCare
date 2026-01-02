<?php
$pageTitle = 'Permanencia Media - Rede Hospitalar';
$pageSlug = 'bi/rede-permanencia';
require_once("templates/bi_rede_bootstrap.php");

$internFilters = biRedeBuildWhere($filterValues, 'i.data_intern_int', 'i', true);
$internWhere = $internFilters['where'];
$internParams = $internFilters['params'];
$internJoins = $internFilters['joins'];

$summaryStmt = $conn->prepare("
    SELECT
        COUNT(*) AS casos,
        AVG(GREATEST(1, DATEDIFF(COALESCE(al.data_alta_alt, CURDATE()), i.data_intern_int) + 1)) AS mp
    FROM tb_internacao i
    LEFT JOIN (
        SELECT fk_id_int_alt, MAX(data_alta_alt) AS data_alta_alt
        FROM tb_alta
        GROUP BY fk_id_int_alt
    ) al ON al.fk_id_int_alt = i.id_internacao
    {$internJoins}
    WHERE {$internWhere}
");
$summaryStmt->execute($internParams);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$casos = (int)($summary['casos'] ?? 0);
$media = isset($summary['mp']) ? (float)$summary['mp'] : 0.0;

$rowsStmt = $conn->prepare("
    SELECT
        h.nome_hosp AS hospital,
        COUNT(*) AS casos,
        AVG(GREATEST(1, DATEDIFF(COALESCE(al.data_alta_alt, CURDATE()), i.data_intern_int) + 1)) AS mp
    FROM tb_internacao i
    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int
    LEFT JOIN (
        SELECT fk_id_int_alt, MAX(data_alta_alt) AS data_alta_alt
        FROM tb_alta
        GROUP BY fk_id_int_alt
    ) al ON al.fk_id_int_alt = i.id_internacao
    {$internJoins}
    WHERE {$internWhere}
    GROUP BY h.id_hospital
    HAVING h.id_hospital IS NOT NULL
    ORDER BY mp DESC
    LIMIT 12
");
$rowsStmt->execute($internParams);
$permRows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$maiorPermanencia = '-';
$melhorEficiencia = '-';
$alertas = 0;
foreach ($permRows as $row) {
    $rowMp = isset($row['mp']) ? (float)$row['mp'] : 0.0;
    if ($maiorPermanencia === '-' && $rowMp > 0) {
        $maiorPermanencia = $row['hospital'] ?? '-';
    }
    if ($melhorEficiencia === '-') {
        $melhorEficiencia = $row['hospital'] ?? '-';
    } elseif ($rowMp > 0 && $rowMp < $media) {
        $melhorEficiencia = $row['hospital'] ?? $melhorEficiencia;
    }
    if ($media > 0 && $rowMp > ($media * 1.2)) {
        $alertas++;
    }
}

$desvio = $permRows && $media > 0 ? (max(array_map(fn($r) => (float)$r['mp'], $permRows)) - $media) : 0.0;
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260111">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260111"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>

<div class="bi-wrapper bi-theme">
    <div class="bi-header">
        <div>
            <h1 class="bi-title">Permanencia Media</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Variacao entre hospitais e gargalos de eficiencia.</div>
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
                <small>Permanencia media</small>
                <strong><?= fmtFloat($media, 1) ?> d</strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Desvio vs. rede</small>
                <strong><?= fmtFloat($desvio, 1) ?> d</strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Hospitais acima do alvo</small>
                <strong><?= fmtInt($alertas) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Casos analisados</small>
                <strong><?= fmtInt($casos) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Permanencia por hospital</h3>
        <div class="bi-split">
            <div class="bi-placeholder">Grafico de permanencia por hospital.</div>
            <div class="bi-list">
                <div class="bi-list-item">
                    <span>Maior permanencia media</span>
                    <strong><?= e($maiorPermanencia) ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Melhor eficiencia</span>
                    <strong><?= e($melhorEficiencia) ?></strong>
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
                    <th>Permanencia media</th>
                    <th>Desvio</th>
                    <th>Casos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$permRows): ?>
                    <tr>
                        <td colspan="4" class="bi-empty">Sem dados com os filtros atuais.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($permRows as $row): ?>
                        <?php
                        $rowMp = isset($row['mp']) ? (float)$row['mp'] : 0.0;
                        $rowCasos = (int)($row['casos'] ?? 0);
                        $dev = $media > 0 ? ($rowMp - $media) : 0.0;
                        ?>
                        <tr>
                            <td><?= e($row['hospital'] ?? 'Sem informacoes') ?></td>
                            <td><?= fmtFloat($rowMp, 1) ?> d</td>
                            <td><?= fmtFloat($dev, 1) ?> d</td>
                            <td><?= fmtInt($rowCasos) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
