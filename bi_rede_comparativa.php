<?php
$pageTitle = 'Performance Comparativa da Rede Hospitalar';
$pageSlug = 'bi/rede-comparativa';
require_once("templates/bi_rede_bootstrap.php");

$metricLinks = [
    [
        'label' => 'Custo por caso',
        'href' => 'bi/rede-custo',
        'desc' => 'Apresentado vs. final autorizado por hospital.',
    ],
    [
        'label' => 'Taxa de glosa',
        'href' => 'bi/rede-glosa',
        'desc' => 'Conformidade e perdas por hospital.',
    ],
    [
        'label' => 'Contas paradas',
        'href' => 'bi/rede-paradas-capeante',
        'desc' => 'Divergencias vs. padrao esperado.',
    ],
    [
        'label' => 'Permanencia media',
        'href' => 'bi/rede-permanencia',
        'desc' => 'Variacao entre hospitais e eficiencia.',
    ],
    [
        'label' => 'Eventos adversos',
        'href' => 'bi/rede-eventos-adversos',
        'desc' => 'Qualidade assistencial por hospital.',
    ],
    [
        'label' => 'Readmissao',
        'href' => 'bi/rede-readmissao',
        'desc' => 'Retorno do paciente apos alta.',
    ],
    [
        'label' => 'Ranking custo x qualidade',
        'href' => 'bi/rede-ranking',
        'desc' => 'Indice combinado para comparacao direta.',
    ],
];
$metricPage = max(1, (int)($_GET['metric_page'] ?? 1));
$metricPerPage = 1;
$metricTotal = count($metricLinks);
$metricPages = max(1, (int)ceil($metricTotal / $metricPerPage));
if ($metricPage > $metricPages) {
    $metricPage = $metricPages;
}
$metricSlice = array_slice($metricLinks, ($metricPage - 1) * $metricPerPage, $metricPerPage);

function buildMetricPageUrl(int $page, string $baseUrl): string
{
    $params = $_GET;
    $params['metric_page'] = $page;
    $query = http_build_query($params);
    return $baseUrl . 'bi/rede-comparativa' . ($query ? ('?' . $query) : '');
}

$internFilters = biRedeBuildWhere($filterValues, 'i.data_intern_int', 'i', true);
$internWhere = $internFilters['where'];
$internParams = $internFilters['params'];
$internJoins = $internFilters['joins'];

$internStatsStmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_casos,
        COUNT(DISTINCT i.fk_hospital_int) AS total_hospitais
    FROM tb_internacao i
    {$internJoins}
    WHERE {$internWhere}
");
$internStatsStmt->execute($internParams);
$internStats = $internStatsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$totalCasos = (int)($internStats['total_casos'] ?? 0);
$totalHospitais = (int)($internStats['total_hospitais'] ?? 0);

$capeanteDateExpr = "COALESCE(NULLIF(ca.data_inicial_capeante,'0000-00-00'), NULLIF(ca.data_digit_capeante,'0000-00-00'), NULLIF(ca.data_fech_capeante,'0000-00-00'))";
$capFilters = biRedeBuildWhere($filterValues, $capeanteDateExpr, 'i', true);
$capWhere = $capFilters['where'];
$capParams = $capFilters['params'];
$capJoins = $capFilters['joins'];

$capStatsStmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT ca.fk_int_capeante) AS total_casos,
        SUM(COALESCE(ca.valor_apresentado_capeante,0)) AS valor_apresentado,
        SUM(COALESCE(ca.valor_final_capeante,0)) AS valor_final,
        SUM(COALESCE(ca.valor_glosa_total,0)) AS valor_glosa
    FROM tb_capeante ca
    JOIN tb_internacao i ON i.id_internacao = ca.fk_int_capeante
    {$capJoins}
    WHERE {$capWhere}
");
$capStatsStmt->execute($capParams);
$capStats = $capStatsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$capCasos = (int)($capStats['total_casos'] ?? 0);
$valorApresentado = (float)($capStats['valor_apresentado'] ?? 0);
$valorFinal = (float)($capStats['valor_final'] ?? 0);
$valorGlosa = (float)($capStats['valor_glosa'] ?? 0);
$custoMedio = $capCasos > 0 ? ($valorFinal > 0 ? ($valorFinal / $capCasos) : ($valorApresentado / $capCasos)) : 0.0;
$glosaPct = $valorApresentado > 0 ? ($valorGlosa / $valorApresentado) * 100 : 0.0;

$permStmt = $conn->prepare("
    SELECT AVG(GREATEST(1, DATEDIFF(COALESCE(al.data_alta_alt, CURDATE()), i.data_intern_int) + 1)) AS mp
    FROM tb_internacao i
    LEFT JOIN (
        SELECT fk_id_int_alt, MAX(data_alta_alt) AS data_alta_alt
        FROM tb_alta
        GROUP BY fk_id_int_alt
    ) al ON al.fk_id_int_alt = i.id_internacao
    {$internJoins}
    WHERE {$internWhere}
");
$permStmt->execute($internParams);
$permRow = $permStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$permanenciaMedia = isset($permRow['mp']) ? (float)$permRow['mp'] : 0.0;

$eventoStmt = $conn->prepare("
    SELECT COUNT(DISTINCT g.fk_internacao_ges) AS total_eventos
    FROM tb_gestao g
    JOIN tb_internacao i ON i.id_internacao = g.fk_internacao_ges
    {$internJoins}
    WHERE {$internWhere}
      AND LOWER(IFNULL(g.evento_adverso_ges,'')) = 's'
");
$eventoStmt->execute($internParams);
$eventoRow = $eventoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$eventosTotal = (int)($eventoRow['total_eventos'] ?? 0);
$eventosPct = $totalCasos > 0 ? ($eventosTotal / $totalCasos) * 100 : 0.0;

$readmFilters = biRedeBuildWhere($filterValues, 'al.data_alta_alt', 'i', true);
$readmWhere = $readmFilters['where'];
$readmParams = $readmFilters['params'];
$readmJoins = $readmFilters['joins'];
$readmStmt = $conn->prepare("
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
        ) AS readm30
    FROM tb_alta al
    JOIN tb_internacao i ON i.id_internacao = al.fk_id_int_alt
    {$readmJoins}
    WHERE {$readmWhere}
");
$readmStmt->execute($readmParams);
$readmRow = $readmStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$totalAltas = (int)($readmRow['total_altas'] ?? 0);
$readm30 = (int)($readmRow['readm30'] ?? 0);
$readmPct = $totalAltas > 0 ? ($readm30 / $totalAltas) * 100 : 0.0;

$qualityIndex = 0.0;
if ($totalCasos > 0 || $totalAltas > 0) {
    $qualityIndex = max(0, 100 - (($glosaPct + $eventosPct + $readmPct) / 3));
}

$rankingStmt = $conn->prepare("
    SELECT
        h.id_hospital,
        h.nome_hosp AS hospital,
        COUNT(DISTINCT ca.fk_int_capeante) AS casos_capeante,
        SUM(COALESCE(ca.valor_final_capeante,0)) AS valor_final,
        SUM(COALESCE(ca.valor_apresentado_capeante,0)) AS valor_apresentado,
        SUM(COALESCE(ca.valor_glosa_total,0)) AS valor_glosa,
        AVG(GREATEST(1, DATEDIFF(COALESCE(al.data_alta_alt, CURDATE()), i.data_intern_int) + 1)) AS mp
    FROM tb_internacao i
    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int
    LEFT JOIN tb_capeante ca ON ca.fk_int_capeante = i.id_internacao
    LEFT JOIN (
        SELECT fk_id_int_alt, MAX(data_alta_alt) AS data_alta_alt
        FROM tb_alta
        GROUP BY fk_id_int_alt
    ) al ON al.fk_id_int_alt = i.id_internacao
    {$internJoins}
    WHERE {$internWhere}
    GROUP BY h.id_hospital
    HAVING h.id_hospital IS NOT NULL
    ORDER BY casos_capeante DESC
    LIMIT 8
");
$rankingStmt->execute($internParams);
$rankingRows = $rankingStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$rankingRows = array_map(function ($row) {
    $cases = (int)($row['casos_capeante'] ?? 0);
    $valorAp = (float)($row['valor_apresentado'] ?? 0);
    $valorFin = (float)($row['valor_final'] ?? 0);
    $valorGl = (float)($row['valor_glosa'] ?? 0);
    $row['custo_medio'] = $cases > 0 ? (($valorFin > 0 ? $valorFin : $valorAp) / $cases) : 0.0;
    $row['glosa_pct'] = $valorAp > 0 ? ($valorGl / $valorAp) * 100 : 0.0;
    return $row;
}, $rankingRows);

$costValues = array_filter(array_map(fn($r) => $r['custo_medio'], $rankingRows), fn($v) => $v > 0);
$minCost = $costValues ? min($costValues) : 0;
$maxCost = $costValues ? max($costValues) : 0;

$rankingRows = array_map(function ($row) use ($minCost, $maxCost) {
    $costScore = 50;
    if ($maxCost > $minCost) {
        $costScore = 100 - ((($row['custo_medio'] - $minCost) / ($maxCost - $minCost)) * 100);
    }
    $qualityScore = max(0, 100 - $row['glosa_pct']);
    $row['score'] = round(($costScore * 0.5) + ($qualityScore * 0.5), 1);
    return $row;
}, $rankingRows);
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260111">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260111"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>

<div class="bi-wrapper bi-theme">
    <div class="bi-header">
        <div>
            <h1 class="bi-title">Performance Comparativa da Rede Hospitalar</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Prioridade alta: comparar custo e qualidade por hospital.</div>
        </div>
        <div class="bi-header-actions">
            <a class="bi-nav-icon" href="<?= $BASE_URL ?>bi/navegacao" title="Navegacao BI">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        </div>
    </div>

    <?php include "templates/bi_rede_filters.php"; ?>

    <div class="bi-panel">
        <h3>Resumo rapido</h3>
        <div class="bi-kpis kpi-grid-4">
            <div class="bi-kpi kpi-compact">
                <small>Hospitais analisados</small>
                <strong><?= fmtInt($totalHospitais) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Casos no periodo</small>
                <strong><?= fmtInt($totalCasos) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Custo medio por caso</small>
                <strong><?= fmtMoney($custoMedio) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Indice de qualidade</small>
                <strong><?= fmtFloat($qualityIndex, 1) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Metricas prioritarias</h3>
        <div class="bi-link-grid">
            <?php foreach ($metricSlice as $metric): ?>
                <a class="bi-link-card" href="<?= $BASE_URL . e($metric['href']) ?>">
                    <strong><?= e($metric['label']) ?></strong>
                    <small><?= e($metric['desc']) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-top:12px;">
            <div style="color: var(--bi-muted); font-size: 0.9rem;">
                Pagina <?= fmtInt($metricPage) ?> de <?= fmtInt($metricPages) ?>
            </div>
            <div style="display:flex; gap:8px;">
                <?php if ($metricPage > 1): ?>
                    <a class="bi-nav-icon" href="<?= e(buildMetricPageUrl($metricPage - 1, $BASE_URL)) ?>" title="Anterior">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                <?php endif; ?>
                <?php if ($metricPage < $metricPages): ?>
                    <a class="bi-nav-icon" href="<?= e(buildMetricPageUrl($metricPage + 1, $BASE_URL)) ?>" title="Proxima">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Ranking geral (preview)</h3>
        <div class="bi-split">
            <div class="bi-placeholder">
                Qualidade considera glosa, eventos adversos e readmissao 30d. Permanencia media atual: <?= fmtFloat($permanenciaMedia, 1) ?> d.
            </div>
            <table class="bi-table">
                <thead>
                    <tr>
                        <th>Hospital</th>
                        <th>Score</th>
                        <th>Custo</th>
                        <th>Qualidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rankingRows): ?>
                        <tr>
                            <td colspan="4" class="bi-empty">Sem dados com os filtros atuais.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rankingRows as $row): ?>
                            <tr>
                                <td><?= e($row['hospital'] ?? 'Sem informacoes') ?></td>
                                <td><?= fmtFloat($row['score'], 1) ?></td>
                                <td><?= fmtMoney($row['custo_medio']) ?></td>
                                <td><?= fmtPct(100 - $row['glosa_pct'], 1) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
