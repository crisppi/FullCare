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

$hoje = date('Y-m-d');
$dataIni = filter_input(INPUT_GET, 'data_ini') ?: date('Y-m-d', strtotime('-90 days'));
$dataFim = filter_input(INPUT_GET, 'data_fim') ?: $hoje;
$hospitalId = filter_input(INPUT_GET, 'hospital_id', FILTER_VALIDATE_INT) ?: null;
$seguradoraId = filter_input(INPUT_GET, 'seguradora_id', FILTER_VALIDATE_INT) ?: null;

$hospitais = $conn->query("SELECT id_hospital, nome_hosp FROM tb_hospital ORDER BY nome_hosp")
    ->fetchAll(PDO::FETCH_ASSOC);
$seguradoras = $conn->query("SELECT id_seguradora, seguradora_seg FROM tb_seguradora ORDER BY seguradora_seg")
    ->fetchAll(PDO::FETCH_ASSOC);

$where = "i.data_intern_int BETWEEN :data_ini AND :data_fim";
$params = [
    ':data_ini' => $dataIni,
    ':data_fim' => $dataFim,
];
if ($hospitalId) {
    $where .= " AND i.fk_hospital_int = :hospital_id";
    $params[':hospital_id'] = $hospitalId;
}
if ($seguradoraId) {
    $where .= " AND pa.fk_seguradora_pac = :seguradora_id";
    $params[':seguradora_id'] = $seguradoraId;
}

$sqlSummary = "
    SELECT
        COUNT(DISTINCT i.id_internacao) AS total_internacoes,
        COUNT(DISTINCT CASE WHEN pr.id_prorrogacao IS NOT NULL OR ges.evento_prorrogar_ges = 's' THEN i.id_internacao END) AS total_indicacoes,
        COUNT(DISTINCT CASE WHEN alt.data_alta_alt IS NOT NULL THEN i.id_internacao END) AS total_altas,
        COUNT(DISTINCT CASE
            WHEN alt.data_alta_alt IS NOT NULL
             AND COALESCE(NULLIF(p.dias_pato, 0), NULLIF(s.longa_permanencia_seg, 0)) IS NOT NULL
             AND (DATEDIFF(alt.data_alta_alt, i.data_intern_int) + 1) <= COALESCE(NULLIF(p.dias_pato, 0), NULLIF(s.longa_permanencia_seg, 0))
            THEN i.id_internacao END
        ) AS altas_dentro_prazo,
        COUNT(DISTINCT CASE
            WHEN alt.data_alta_alt IS NOT NULL
             AND COALESCE(NULLIF(p.dias_pato, 0), NULLIF(s.longa_permanencia_seg, 0)) IS NOT NULL
             AND (DATEDIFF(alt.data_alta_alt, i.data_intern_int) + 1) > COALESCE(NULLIF(p.dias_pato, 0), NULLIF(s.longa_permanencia_seg, 0))
            THEN i.id_internacao END
        ) AS altas_fora_prazo
    FROM tb_internacao i
    LEFT JOIN tb_paciente pa ON pa.id_paciente = i.fk_paciente_int
    LEFT JOIN tb_seguradora s ON s.id_seguradora = pa.fk_seguradora_pac
    LEFT JOIN tb_patologia p ON p.id_patologia = i.fk_patologia_int
    LEFT JOIN tb_alta alt ON alt.fk_id_int_alt = i.id_internacao
    LEFT JOIN tb_prorrogacao pr ON pr.fk_internacao_pror = i.id_internacao
    LEFT JOIN tb_gestao ges ON ges.fk_internacao_ges = i.id_internacao
    WHERE {$where}
";
$stmt = $conn->prepare($sqlSummary);
$stmt->execute($params);
$summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$sqlByConvenio = "
    SELECT
        COALESCE(s.seguradora_seg, 'Sem operadora') AS convenio,
        COUNT(DISTINCT i.id_internacao) AS total_internacoes,
        COUNT(DISTINCT CASE WHEN pr.id_prorrogacao IS NOT NULL OR ges.evento_prorrogar_ges = 's' THEN i.id_internacao END) AS total_indicacoes,
        COUNT(DISTINCT CASE
            WHEN alt.data_alta_alt IS NOT NULL
             AND COALESCE(NULLIF(p.dias_pato, 0), NULLIF(s.longa_permanencia_seg, 0)) IS NOT NULL
             AND (DATEDIFF(alt.data_alta_alt, i.data_intern_int) + 1) <= COALESCE(NULLIF(p.dias_pato, 0), NULLIF(s.longa_permanencia_seg, 0))
            THEN i.id_internacao END
        ) AS altas_dentro_prazo,
        COUNT(DISTINCT CASE
            WHEN alt.data_alta_alt IS NOT NULL
             AND COALESCE(NULLIF(p.dias_pato, 0), NULLIF(s.longa_permanencia_seg, 0)) IS NOT NULL
             AND (DATEDIFF(alt.data_alta_alt, i.data_intern_int) + 1) > COALESCE(NULLIF(p.dias_pato, 0), NULLIF(s.longa_permanencia_seg, 0))
            THEN i.id_internacao END
        ) AS altas_fora_prazo
    FROM tb_internacao i
    LEFT JOIN tb_paciente pa ON pa.id_paciente = i.fk_paciente_int
    LEFT JOIN tb_seguradora s ON s.id_seguradora = pa.fk_seguradora_pac
    LEFT JOIN tb_patologia p ON p.id_patologia = i.fk_patologia_int
    LEFT JOIN tb_alta alt ON alt.fk_id_int_alt = i.id_internacao
    LEFT JOIN tb_prorrogacao pr ON pr.fk_internacao_pror = i.id_internacao
    LEFT JOIN tb_gestao ges ON ges.fk_internacao_ges = i.id_internacao
    WHERE {$where}
    GROUP BY convenio
    ORDER BY total_indicacoes DESC, total_internacoes DESC
";
$stmt = $conn->prepare($sqlByConvenio);
$stmt->execute($params);
$rowsConvenio = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalIntern = (int)($summary['total_internacoes'] ?? 0);
$totalIndicacoes = (int)($summary['total_indicacoes'] ?? 0);
$totalAltas = (int)($summary['total_altas'] ?? 0);
$altasDentro = (int)($summary['altas_dentro_prazo'] ?? 0);
$altasFora = (int)($summary['altas_fora_prazo'] ?? 0);
$altasComPrazo = $altasDentro + $altasFora;
?>

<link href="<?= $BASE_URL ?>css/listagem_padrao.css?v=<?= @filemtime(__DIR__ . '/css/listagem_padrao.css') ?>" rel="stylesheet">
<link href="<?= $BASE_URL ?>css/operational_reports.css?v=<?= @filemtime(__DIR__ . '/css/operational_reports.css') ?>" rel="stylesheet">

<div class="report-wrapper prorrog-report-wrapper">
    <div class="report-header">
        <h1>Indicação de permanência vs. alta no prazo</h1>
        <div class="text-muted">Indicação clara considera prorrogação registrada ou marcação de gestão, apoiando decisão de pagamento.</div>
    </div>

    <form class="report-card prorrog-filter-card listagem-panel" method="get">
        <div class="prorrog-filter-row filter-inline-row">
            <div class="prorrog-filter-field filter-inline-field filter-inline--date">
                <input type="date" class="form-control form-control-sm" name="data_ini" value="<?= e($dataIni) ?>" aria-label="Data inicial">
            </div>
            <div class="prorrog-filter-field filter-inline-field filter-inline--date">
                <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= e($dataFim) ?>" aria-label="Data final">
            </div>
            <div class="prorrog-filter-field filter-inline-field filter-inline--wide">
                <select class="form-select form-control-sm" name="hospital_id" aria-label="Hospital">
                    <option value="">Hospital: todos</option>
                    <?php foreach ($hospitais as $h): ?>
                        <option value="<?= (int)$h['id_hospital'] ?>" <?= $hospitalId == $h['id_hospital'] ? 'selected' : '' ?>>
                            <?= e($h['nome_hosp']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="prorrog-filter-field filter-inline-field filter-inline--wide">
                <select class="form-select form-control-sm" name="seguradora_id" aria-label="Operadora">
                    <option value="">Operadora: todas</option>
                    <?php foreach ($seguradoras as $s): ?>
                        <option value="<?= (int)$s['id_seguradora'] ?>" <?= $seguradoraId == $s['id_seguradora'] ? 'selected' : '' ?>>
                            <?= e($s['seguradora_seg']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="prorrog-filter-actions filter-inline-field filter-inline--icon">
                <button class="btn btn-primary btn-filtro-buscar btn-filtro-limpar-icon" type="submit" title="Pesquisar" aria-label="Pesquisar">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
                <a class="btn btn-light btn-sm btn-filtro-limpar btn-filtro-limpar-icon" href="<?= htmlspecialchars($BASE_URL . 'inteligencia/prorrogacao-vs-alta', ENT_QUOTES, 'UTF-8') ?>" title="Limpar filtros" aria-label="Limpar filtros">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="report-card">
        <h5 class="prorrog-section-title">Resumo</h5>
        <div class="table-responsive listagem-table-wrap prorrog-list-table-wrap">
            <table class="table table-sm table-striped table-hover table-condensed align-middle">
                <thead>
                    <tr>
                        <th>Internações</th>
                        <th>Indicações de permanência</th>
                        <th>Altas</th>
                        <th>Altas dentro do prazo</th>
                        <th>Altas fora do prazo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= $totalIntern ?></td>
                        <td><?= $totalIndicacoes ?></td>
                        <td><?= $totalAltas ?></td>
                        <td><?= $altasDentro ?><?= $altasComPrazo ? ' (' . round(($altasDentro / $altasComPrazo) * 100, 1) . '%)' : '' ?></td>
                        <td><?= $altasFora ?><?= $altasComPrazo ? ' (' . round(($altasFora / $altasComPrazo) * 100, 1) . '%)' : '' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-card">
        <h5 class="prorrog-section-title">Detalhe por Operadora</h5>
        <div class="table-responsive listagem-table-wrap prorrog-list-table-wrap">
            <table class="table table-sm table-striped table-hover table-condensed align-middle">
                <thead>
                    <tr>
                        <th>Operadora</th>
                        <th class="text-end">Internações</th>
                        <th class="text-end">Indicações</th>
                        <th class="text-end">Altas dentro</th>
                        <th class="text-end">Altas fora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rowsConvenio): ?>
                        <tr><td colspan="5" class="text-muted">Nenhum dado encontrado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rowsConvenio as $row): ?>
                        <tr>
                            <td><?= e($row['convenio']) ?></td>
                            <td class="text-end"><?= (int)$row['total_internacoes'] ?></td>
                            <td class="text-end"><?= (int)$row['total_indicacoes'] ?></td>
                            <td class="text-end"><?= (int)$row['altas_dentro_prazo'] ?></td>
                            <td class="text-end"><?= (int)$row['altas_fora_prazo'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="text-muted small">Prazo calculado por patologia (dias) ou operadora (longa permanência).</div>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
