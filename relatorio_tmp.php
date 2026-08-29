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

$sqlBase = "
    FROM tb_internacao i
    LEFT JOIN (
        SELECT fk_id_int_alt, MAX(data_alta_alt) AS data_alta_alt
        FROM tb_alta
        GROUP BY fk_id_int_alt
    ) alt ON alt.fk_id_int_alt = i.id_internacao
    LEFT JOIN tb_paciente pa ON pa.id_paciente = i.fk_paciente_int
    LEFT JOIN tb_seguradora s ON s.id_seguradora = pa.fk_seguradora_pac
    LEFT JOIN tb_cid cid ON cid.id_cid = i.fk_cid_int
    LEFT JOIN tb_patologia p ON p.id_patologia = i.fk_patologia_int
    WHERE {$where}
";

$sqlCid = "
    SELECT
        COALESCE(cid.cat, 'Sem CID') AS cid,
        COALESCE(cid.descricao, 'Sem descrição') AS descricao,
        COUNT(DISTINCT i.id_internacao) AS total,
        ROUND(AVG(GREATEST(1, DATEDIFF(COALESCE(alt.data_alta_alt, CURDATE()), i.data_intern_int) + 1)), 1) AS tmp
    {$sqlBase}
    GROUP BY cid, descricao
    ORDER BY tmp DESC, total DESC
    LIMIT 50
";
$stmt = $conn->prepare($sqlCid);
$stmt->execute($params);
$tmpCid = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlProc = "
    SELECT
        COALESCE(p.patologia_pat, 'Sem procedimento') AS procedimento,
        COUNT(DISTINCT i.id_internacao) AS total,
        ROUND(AVG(GREATEST(1, DATEDIFF(COALESCE(alt.data_alta_alt, CURDATE()), i.data_intern_int) + 1)), 1) AS tmp
    {$sqlBase}
    GROUP BY procedimento
    ORDER BY tmp DESC, total DESC
    LIMIT 50
";
$stmt = $conn->prepare($sqlProc);
$stmt->execute($params);
$tmpProc = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlConv = "
    SELECT
        COALESCE(s.seguradora_seg, 'Sem operadora') AS convenio,
        COUNT(DISTINCT i.id_internacao) AS total,
        ROUND(AVG(GREATEST(1, DATEDIFF(COALESCE(alt.data_alta_alt, CURDATE()), i.data_intern_int) + 1)), 1) AS tmp
    {$sqlBase}
    GROUP BY convenio
    ORDER BY tmp DESC, total DESC
";
$stmt = $conn->prepare($sqlConv);
$stmt->execute($params);
$tmpConv = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="<?= $BASE_URL ?>css/listagem_padrao.css?v=<?= @filemtime(__DIR__ . '/css/listagem_padrao.css') ?>" rel="stylesheet">
<style>
.tmp-list-page .tmp-list-panel {
    padding: 10px 10px 16px;
}

.tmp-list-page .tmp-filter-card {
    margin: 0 0 10px;
    padding: 0 0 9px;
    border-bottom: 1px solid rgba(47, 111, 159, .10);
}

.tmp-list-page .tmp-list-section + .tmp-list-section {
    margin-top: 12px;
}

.tmp-list-page .tmp-list-title {
    margin: 0 0 6px 2px;
    color: #24384f;
    font-size: .76rem;
    font-weight: 800;
}
</style>

<main class="container-fluid listagem-page tmp-list-page" id="main-container">
    <div class="listagem-hero listagem-hero--module listagem-hero--inteligencia">
        <div class="listagem-hero__copy">
            <div class="listagem-kicker">Inteligência Operacional</div>
            <h1 class="listagem-title">TMP por CID, procedimento e operadora</h1>
        </div>
    </div>

    <div class="complete-table listagem-panel tmp-list-panel">
    <form class="tmp-filter-card table-filters" method="get">
        <div class="tmp-filter-row filter-inline-row">
            <div class="tmp-filter-field filter-inline-field filter-inline--date">
                <input type="date" class="form-control form-control-sm" name="data_ini" value="<?= e($dataIni) ?>" aria-label="Data inicial">
            </div>
            <div class="tmp-filter-field filter-inline-field filter-inline--date">
                <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= e($dataFim) ?>" aria-label="Data final">
            </div>
            <div class="tmp-filter-field filter-inline-field filter-inline--wide">
                <select class="form-select form-control-sm" name="hospital_id" aria-label="Hospital">
                    <option value="">Hospital: todos</option>
                    <?php foreach ($hospitais as $h): ?>
                        <option value="<?= (int)$h['id_hospital'] ?>" <?= $hospitalId == $h['id_hospital'] ? 'selected' : '' ?>>
                            <?= e($h['nome_hosp']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tmp-filter-field filter-inline-field filter-inline--wide">
                <select class="form-select form-control-sm" name="seguradora_id" aria-label="Operadora">
                    <option value="">Operadora: todas</option>
                    <?php foreach ($seguradoras as $s): ?>
                        <option value="<?= (int)$s['id_seguradora'] ?>" <?= $seguradoraId == $s['id_seguradora'] ? 'selected' : '' ?>>
                            <?= e($s['seguradora_seg']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tmp-filter-actions filter-inline-field filter-inline--icon">
                <button class="btn btn-primary btn-filtro-buscar btn-filtro-limpar-icon" type="submit" title="Pesquisar" aria-label="Pesquisar">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
                <a class="btn btn-light btn-sm btn-filtro-limpar btn-filtro-limpar-icon" href="<?= htmlspecialchars($BASE_URL . 'inteligencia/tmp', ENT_QUOTES, 'UTF-8') ?>" title="Limpar filtros" aria-label="Limpar filtros">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </form>

    <section class="tmp-list-section">
        <h2 class="tmp-list-title">TMP por CID</h2>
        <div class="table-responsive listagem-table-wrap tmp-list-table-wrap">
            <table class="table table-sm table-striped table-hover table-condensed align-middle">
                <thead>
                    <tr>
                        <th>CID</th>
                        <th>Descrição</th>
                        <th class="text-end">Internações</th>
                        <th class="text-end">TMP (dias)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$tmpCid): ?>
                        <tr><td colspan="4" class="text-muted">Nenhum dado encontrado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tmpCid as $row): ?>
                        <tr>
                            <td><?= e($row['cid']) ?></td>
                            <td><?= e($row['descricao']) ?></td>
                            <td class="text-end"><?= (int)$row['total'] ?></td>
                            <td class="text-end"><?= e($row['tmp']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="tmp-list-section">
        <h2 class="tmp-list-title">TMP por Procedimento</h2>
        <div class="table-responsive listagem-table-wrap tmp-list-table-wrap">
            <table class="table table-sm table-striped table-hover table-condensed align-middle">
                <thead>
                    <tr>
                        <th>Procedimento</th>
                        <th class="text-end">Internações</th>
                        <th class="text-end">TMP (dias)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$tmpProc): ?>
                        <tr><td colspan="3" class="text-muted">Nenhum dado encontrado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tmpProc as $row): ?>
                        <tr>
                            <td><?= e($row['procedimento']) ?></td>
                            <td class="text-end"><?= (int)$row['total'] ?></td>
                            <td class="text-end"><?= e($row['tmp']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="tmp-list-section">
        <h2 class="tmp-list-title">TMP por Operadora</h2>
        <div class="table-responsive listagem-table-wrap tmp-list-table-wrap">
            <table class="table table-sm table-striped table-hover table-condensed align-middle">
                <thead>
                    <tr>
                        <th>Operadora</th>
                        <th class="text-end">Internações</th>
                        <th class="text-end">TMP (dias)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$tmpConv): ?>
                        <tr><td colspan="3" class="text-muted">Nenhum dado encontrado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tmpConv as $row): ?>
                        <tr>
                            <td><?= e($row['convenio']) ?></td>
                            <td class="text-end"><?= (int)$row['total'] ?></td>
                            <td class="text-end"><?= e($row['tmp']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    </div>
</main>

<?php require_once("templates/footer.php"); ?>
