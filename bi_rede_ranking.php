<?php
$pageTitle = 'Ranking Custo x Qualidade - Rede Hospitalar';
$pageSlug = 'bi/rede-ranking';
require_once("templates/bi_rede_bootstrap.php");

$internFilters = biRedeBuildWhere($filterValues, 'i.data_intern_int', 'i', true);
$internWhere = $internFilters['where'];
$internParams = $internFilters['params'];
$internJoins = $internFilters['joins'];

$baseStmt = $conn->prepare("
    SELECT
        h.id_hospital,
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
");
$baseStmt->execute($internParams);
$hospRows = $baseStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$overallMp = 0.0;
if ($hospRows) {
    $overallMp = array_sum(array_map(fn($r) => (float)($r['mp'] ?? 0), $hospRows)) / max(1, count($hospRows));
}

$capeanteDateExpr = "COALESCE(NULLIF(ca.data_inicial_capeante,'0000-00-00'), NULLIF(ca.data_digit_capeante,'0000-00-00'), NULLIF(ca.data_fech_capeante,'0000-00-00'))";
$capFilters = biRedeBuildWhere($filterValues, $capeanteDateExpr, 'i', true);
$capWhere = $capFilters['where'];
$capParams = $capFilters['params'];
$capJoins = $capFilters['joins'];

$capStmt = $conn->prepare("
    SELECT
        i.fk_hospital_int AS hospital_id,
        COUNT(DISTINCT ca.fk_int_capeante) AS casos,
        SUM(COALESCE(ca.valor_apresentado_capeante,0)) AS valor_apresentado,
        SUM(COALESCE(ca.valor_final_capeante,0)) AS valor_final,
        SUM(COALESCE(ca.valor_glosa_total,0)) AS valor_glosa
    FROM tb_capeante ca
    JOIN tb_internacao i ON i.id_internacao = ca.fk_int_capeante
    {$capJoins}
    WHERE {$capWhere}
    GROUP BY i.fk_hospital_int
");
$capStmt->execute($capParams);
$capRows = $capStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
$capMap = [];
foreach ($capRows as $row) {
    $capMap[(int)$row['hospital_id']] = $row;
}

$eventStmt = $conn->prepare("
    SELECT
        i.fk_hospital_int AS hospital_id,
        COUNT(DISTINCT i.id_internacao) AS casos,
        COUNT(DISTINCT CASE WHEN LOWER(IFNULL(g.evento_adverso_ges,'')) = 's' THEN g.fk_internacao_ges END) AS eventos
    FROM tb_internacao i
    LEFT JOIN tb_gestao g ON g.fk_internacao_ges = i.id_internacao
    {$internJoins}
    WHERE {$internWhere}
    GROUP BY i.fk_hospital_int
");
$eventStmt->execute($internParams);
$eventRows = $eventStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
$eventMap = [];
foreach ($eventRows as $row) {
    $eventMap[(int)$row['hospital_id']] = $row;
}

$readmFilters = biRedeBuildWhere($filterValues, 'al.data_alta_alt', 'i', true);
$readmWhere = $readmFilters['where'];
$readmParams = $readmFilters['params'];
$readmJoins = $readmFilters['joins'];
$readmStmt = $conn->prepare("
    SELECT
        i.fk_hospital_int AS hospital_id,
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
    GROUP BY i.fk_hospital_int
");
$readmStmt->execute($readmParams);
$readmRows = $readmStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
$readmMap = [];
foreach ($readmRows as $row) {
    $readmMap[(int)$row['hospital_id']] = $row;
}

$ranking = [];
foreach ($hospRows as $row) {
    $hid = (int)$row['id_hospital'];
    $cap = $capMap[$hid] ?? ['casos' => 0, 'valor_apresentado' => 0, 'valor_final' => 0, 'valor_glosa' => 0];
    $capCasos = (int)($cap['casos'] ?? 0);
    $valorAp = (float)($cap['valor_apresentado'] ?? 0);
    $valorFin = (float)($cap['valor_final'] ?? 0);
    $valorGl = (float)($cap['valor_glosa'] ?? 0);
    $custoMedio = $capCasos > 0 ? (($valorFin > 0 ? $valorFin : $valorAp) / $capCasos) : 0.0;
    $glosaPct = $valorAp > 0 ? ($valorGl / $valorAp) * 100 : 0.0;

    $event = $eventMap[$hid] ?? ['casos' => 0, 'eventos' => 0];
    $eventCasos = (int)($event['casos'] ?? 0);
    $eventos = (int)($event['eventos'] ?? 0);
    $eventPct = $eventCasos > 0 ? ($eventos / $eventCasos) * 100 : 0.0;

    $readm = $readmMap[$hid] ?? ['total_altas' => 0, 'readm30' => 0];
    $altas = (int)($readm['total_altas'] ?? 0);
    $readm30 = (int)($readm['readm30'] ?? 0);
    $readmPct = $altas > 0 ? ($readm30 / $altas) * 100 : 0.0;

    $mp = (float)($row['mp'] ?? 0);
    $permPenalty = ($overallMp > 0 && $mp > $overallMp) ? min(10, (($mp - $overallMp) / $overallMp) * 10) : 0;

    $qualityScore = max(0, 100 - (($glosaPct + $eventPct + $readmPct) / 3) - $permPenalty);

    $ranking[] = [
        'hospital' => $row['hospital'] ?? 'Sem informacoes',
        'custo' => $custoMedio,
        'qualidade' => $qualityScore,
        'score' => 0,
    ];
}

$costValues = array_filter(array_map(fn($r) => $r['custo'], $ranking), fn($v) => $v > 0);
$minCost = $costValues ? min($costValues) : 0;
$maxCost = $costValues ? max($costValues) : 0;

foreach ($ranking as &$r) {
    $costScore = 50;
    if ($maxCost > $minCost) {
        $costScore = 100 - ((($r['custo'] - $minCost) / ($maxCost - $minCost)) * 100);
    }
    $r['score'] = round(($costScore * 0.5) + ($r['qualidade'] * 0.5), 1);
}
unset($r);

usort($ranking, function ($a, $b) {
    return $b['score'] <=> $a['score'];
});

$ranking = array_slice($ranking, 0, 12);

$hospitaisAvaliados = count($ranking);
$scoreMedio = $hospitaisAvaliados > 0 ? array_sum(array_column($ranking, 'score')) / $hospitaisAvaliados : 0.0;
$melhorScore = $ranking ? ($ranking[0]['score'] ?? 0) : 0.0;
$riscoElevado = count(array_filter($ranking, fn($r) => $r['score'] < 50));
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260111">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260111"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>

<div class="bi-wrapper bi-theme">
    <div class="bi-header">
        <div>
            <h1 class="bi-title">Ranking Custo x Qualidade</h1>
            <div style="color: var(--bi-muted); font-size: 0.95rem;">Indice combinado para comparar hospitais.</div>
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
                <small>Hospitais avaliados</small>
                <strong><?= fmtInt($hospitaisAvaliados) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Score medio</small>
                <strong><?= fmtFloat($scoreMedio, 1) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Melhor score</small>
                <strong><?= fmtFloat($melhorScore, 1) ?></strong>
            </div>
            <div class="bi-kpi kpi-compact">
                <small>Risco elevado</small>
                <strong><?= fmtInt($riscoElevado) ?></strong>
            </div>
        </div>
    </div>

    <div class="bi-panel">
        <h3>Ranking consolidado</h3>
        <div class="bi-split">
            <div class="bi-placeholder">Grafico de dispersao custo x qualidade.</div>
            <div class="bi-list">
                <div class="bi-list-item">
                    <span>Hospital destaque</span>
                    <strong><?= $ranking ? e($ranking[0]['hospital']) : '-' ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Maior risco</span>
                    <strong><?= $ranking ? e(end($ranking)['hospital']) : '-' ?></strong>
                </div>
                <div class="bi-list-item">
                    <span>Alertas ativos</span>
                    <strong><?= fmtInt($riscoElevado) ?></strong>
                </div>
            </div>
        </div>
        <table class="bi-table" style="margin-top: 16px;">
            <thead>
                <tr>
                    <th>Hospital</th>
                    <th>Score</th>
                    <th>Custo</th>
                    <th>Qualidade</th>
                    <th>Posicao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$ranking): ?>
                    <tr>
                        <td colspan="5" class="bi-empty">Sem dados com os filtros atuais.</td>
                    </tr>
                <?php else: ?>
                    <?php $pos = 1; ?>
                    <?php foreach ($ranking as $row): ?>
                        <tr>
                            <td><?= e($row['hospital']) ?></td>
                            <td><?= fmtFloat($row['score'], 1) ?></td>
                            <td><?= fmtMoney($row['custo']) ?></td>
                            <td><?= fmtPct($row['qualidade'], 1) ?></td>
                            <td><?= fmtInt($pos++) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
