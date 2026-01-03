<?php
$pageTitle = 'Padrao de Negociacao Suspeito';
$pageSlug = 'bi/anomalias-negociacao';
require_once("templates/bi_rede_bootstrap.php");

$internFilters = biRedeBuildWhere($filterValues, 'i.data_intern_int', 'i', true);
$internWhere = $internFilters['where'];
$internParams = $internFilters['params'];
$internJoins = $internFilters['joins'];

$summaryStmt = $conn->prepare("\n    SELECT\n        COUNT(DISTINCT i.id_internacao) AS internacoes,\n        COUNT(DISTINCT ng.id_negociacao) AS negociacoes,\n        SUM(COALESCE(ng.saving,0)) AS saving_total,\n        COUNT(DISTINCT pr.id_prorrogacao) AS prorrogacoes\n    FROM tb_internacao i\n    LEFT JOIN tb_negociacao ng ON ng.fk_id_int = i.id_internacao\n    LEFT JOIN tb_prorrogacao pr ON pr.fk_internacao_pror = i.id_internacao\n    {$internJoins}\n    WHERE {$internWhere}\n");
biBindParams($summaryStmt, $internParams);
$summaryStmt->execute();
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$internacoes = (int)($summary['internacoes'] ?? 0);
$negociacoes = (int)($summary['negociacoes'] ?? 0);
$savingTotal = (float)($summary['saving_total'] ?? 0);
$prorrogacoes = (int)($summary['prorrogacoes'] ?? 0);

$rowsStmt = $conn->prepare("\n    SELECT\n        h.nome_hosp AS hospital,\n        COUNT(DISTINCT i.id_internacao) AS internacoes,\n        COUNT(DISTINCT ng.id_negociacao) AS negociacoes,\n        SUM(COALESCE(ng.saving,0)) AS saving_total,\n        COUNT(DISTINCT pr.id_prorrogacao) AS prorrogacoes\n    FROM tb_internacao i\n    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int\n    LEFT JOIN tb_negociacao ng ON ng.fk_id_int = i.id_internacao\n    LEFT JOIN tb_prorrogacao pr ON pr.fk_internacao_pror = i.id_internacao\n    {$internJoins}\n    WHERE {$internWhere}\n    GROUP BY h.id_hospital\n    HAVING h.id_hospital IS NOT NULL\n    ORDER BY negociacoes DESC, saving_total DESC\n    LIMIT 10\n");
biBindParams($rowsStmt, $internParams);
$rowsStmt->execute();
$rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260111">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260111"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>

<div class="bi-wrapper bi-theme">
    <div class="bi-header">
        <div>
            <h1 class="bi-title">Padrao de Negociacao Suspeito</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Hospitais com excesso de negociacoes ou prorrogações.</div>
        </div>
        <div class="bi-header-actions">
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
                <small>Negociacoes</small>
                <strong><?= fmtInt($negociacoes) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Prorrogacoes</small>
                <strong><?= fmtInt($prorrogacoes) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Saving total</small>
                <strong><?= fmtMoney($savingTotal) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Internacoes analisadas</small>
                <strong><?= fmtInt($internacoes) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Top 10 hospitais com maior intensidade de negociacao</h3>
        <table class="bi-table">
            <thead>
                <tr>
                    <th>Hospital</th>
                    <th>Negociacoes</th>
                    <th>Prorrogacoes</th>
                    <th>Saving total</th>
                    <th>Indice por internacao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="5" class="bi-empty">Sem dados com os filtros atuais.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $ints = (int)($row['internacoes'] ?? 0);
                        $neg = (int)($row['negociacoes'] ?? 0);
                        $pr = (int)($row['prorrogacoes'] ?? 0);
                        $indice = $ints > 0 ? (($neg + $pr) / $ints) : 0.0;
                        ?>
                        <tr>
                            <td><?= e($row['hospital'] ?? 'Sem informacoes') ?></td>
                            <td><?= fmtInt($neg) ?></td>
                            <td><?= fmtInt($pr) ?></td>
                            <td><?= fmtMoney((float)($row['saving_total'] ?? 0)) ?></td>
                            <td><?= fmtFloat($indice, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
