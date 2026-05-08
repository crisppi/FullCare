<?php
include_once("check_logado.php");
require_once("templates/header.php");

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Conexao invalida.");
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$hoje = date('Y-m-d');
$dataIni = filter_input(INPUT_GET, 'data_ini') ?: date('Y-m-d', strtotime('-120 days'));
$dataFim = filter_input(INPUT_GET, 'data_fim') ?: $hoje;
$internado = trim((string)(filter_input(INPUT_GET, 'internado') ?? ''));
$hospitalId = filter_input(INPUT_GET, 'hospital_id', FILTER_VALIDATE_INT) ?: null;
$tipoInternação = trim((string)(filter_input(INPUT_GET, 'tipo_internacao') ?? ''));
$modoAdmissão = trim((string)(filter_input(INPUT_GET, 'modo_admissao') ?? ''));
$uti = trim((string)(filter_input(INPUT_GET, 'uti') ?? ''));

$hospitais = $conn->query("SELECT id_hospital, nome_hosp FROM tb_hospital ORDER BY nome_hosp")
    ->fetchAll(PDO::FETCH_ASSOC);
$tiposInt = $conn->query("SELECT DISTINCT tipo_admissao_int FROM tb_internacao WHERE tipo_admissao_int IS NOT NULL AND tipo_admissao_int <> '' ORDER BY tipo_admissao_int")
    ->fetchAll(PDO::FETCH_COLUMN);
$modosAdm = $conn->query("SELECT DISTINCT modo_internacao_int FROM tb_internacao WHERE modo_internacao_int IS NOT NULL AND modo_internacao_int <> '' ORDER BY modo_internacao_int")
    ->fetchAll(PDO::FETCH_COLUMN);

$where = "i.data_intern_int BETWEEN :data_ini AND :data_fim";
$params = [
    ':data_ini' => $dataIni,
    ':data_fim' => $dataFim,
];
if ($internado !== '') {
    $where .= " AND i.internado_int = :internado";
    $params[':internado'] = $internado;
}
if ($hospitalId) {
    $where .= " AND i.fk_hospital_int = :hospital_id";
    $params[':hospital_id'] = $hospitalId;
}
if ($tipoInternação !== '') {
    $where .= " AND i.tipo_admissao_int = :tipo";
    $params[':tipo'] = $tipoInternação;
}
if ($modoAdmissão !== '') {
    $where .= " AND i.modo_internacao_int = :modo";
    $params[':modo'] = $modoAdmissão;
}

$utiJoin = "LEFT JOIN (SELECT DISTINCT fk_internacao_uti FROM tb_uti) ut ON ut.fk_internacao_uti = i.id_internacao";
if ($uti === 's') {
    $where .= " AND ut.fk_internacao_uti IS NOT NULL";
}
if ($uti === 'n') {
    $where .= " AND ut.fk_internacao_uti IS NULL";
}

$sqlBase = "
    FROM tb_internacao i
    {$utiJoin}
    JOIN tb_gestao g ON g.fk_internacao_ges = i.id_internacao AND g.home_care_ges = 's'
    LEFT JOIN tb_paciente pa ON pa.id_paciente = i.fk_paciente_int
    LEFT JOIN tb_hospital h ON h.id_hospital = i.fk_hospital_int
    LEFT JOIN (
        SELECT fk_id_int_alt, MAX(data_alta_alt) AS data_alta_alt
        FROM tb_alta
        GROUP BY fk_id_int_alt
    ) al ON al.fk_id_int_alt = i.id_internacao
    WHERE {$where}
";

$sqlStats = "
    SELECT
        COUNT(DISTINCT i.id_internacao) AS total_internacoes,
        SUM(GREATEST(1, DATEDIFF(COALESCE(al.data_alta_alt, CURDATE()), i.data_intern_int) + 1)) AS total_diarias,
        MAX(GREATEST(1, DATEDIFF(COALESCE(al.data_alta_alt, CURDATE()), i.data_intern_int) + 1)) AS maior_permanencia,
        ROUND(AVG(GREATEST(1, DATEDIFF(COALESCE(al.data_alta_alt, CURDATE()), i.data_intern_int) + 1)), 1) AS mp,
        COUNT(DISTINCT g.id_gestao) AS total_eventos
    {$sqlBase}
";
$stmt = $conn->prepare($sqlStats);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$totalInternações = (int)($stats['total_internacoes'] ?? 0);
$totalDiárias = (int)($stats['total_diarias'] ?? 0);
$maiorPermanencia = (int)($stats['maior_permanencia'] ?? 0);
$mp = (float)($stats['mp'] ?? 0);
$totalEventos = (int)($stats['total_eventos'] ?? 0);

$sqlHosp = "
    SELECT h.nome_hosp AS label, COUNT(*) AS total
    {$sqlBase}
    GROUP BY h.id_hospital
    ORDER BY total DESC
    LIMIT 12
";
$stmtHosp = $conn->prepare($sqlHosp);
$stmtHosp->execute($params);
$hospRows = $stmtHosp->fetchAll(PDO::FETCH_ASSOC) ?: [];

$tipoRows = [
    ['label' => 'Home Care', 'total' => $totalEventos],
];

$sqlTable = "
    SELECT
        COALESCE(NULLIF(pa.nome_pac,''), 'Sem informacoes') AS paciente,
        COALESCE(NULLIF(h.nome_hosp,''), 'Sem informacoes') AS hospital,
        COALESCE(NULLIF(g.rel_home_care_ges,''), '-') AS relatorio
    {$sqlBase}
    ORDER BY i.data_intern_int DESC
    LIMIT 60
";
$stmtTable = $conn->prepare($sqlTable);
$stmtTable->execute($params);
$rowsTable = $stmtTable->fetchAll(PDO::FETCH_ASSOC) ?: [];

$selectedHospitalLabel = 'Todos os hospitais';
foreach ($hospitais as $hospital) {
    if ($hospitalId == ($hospital['id_hospital'] ?? null)) {
        $selectedHospitalLabel = (string)($hospital['nome_hosp'] ?? $selectedHospitalLabel);
        break;
    }
}

$activeFilters = [];
if ($internado !== '') {
    $activeFilters[] = 'Internado: ' . ($internado === 's' ? 'Sim' : 'Não');
}
if ($hospitalId) {
    $activeFilters[] = 'Hospital: ' . $selectedHospitalLabel;
}
if ($tipoInternação !== '') {
    $activeFilters[] = 'Tipo: ' . $tipoInternação;
}
if ($modoAdmissão !== '') {
    $activeFilters[] = 'Modo: ' . $modoAdmissão;
}
if ($uti !== '') {
    $activeFilters[] = 'UTI: ' . ($uti === 's' ? 'Sim' : 'Não');
}
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260501">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260501"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>
<style>
.hc-bi-shell {
    --hcbi-border: rgba(255,255,255,.2);
    --hcbi-soft: rgba(255,255,255,.08);
    --hcbi-text: rgba(255,255,255,.92);
    --hcbi-text-soft: rgba(255,255,255,.76);
    --hcbi-ink: #f4f8ff;
    --hcbi-cyan: #7fe4ff;
    --hcbi-mint: #8df0c7;
    --hcbi-amber: #ffd48a;
    --hcbi-rose: #ff9fc0;
}

.hc-bi-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(280px, .95fr);
    gap: 14px;
    margin-bottom: 14px;
}

.hc-bi-hero-main {
    padding: 18px 20px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(22,78,132,.45), rgba(74,133,197,.28));
    border: 1px solid var(--hcbi-border);
}

.hc-bi-eyebrow {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.12);
    color: rgba(255,255,255,.82);
    font-size: .66rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.hc-bi-hero-main .bi-title {
    margin: 10px 0 6px;
    color: var(--hcbi-ink);
}

.hc-bi-copy {
    margin: 0;
    max-width: 760px;
    color: rgba(255,255,255,.82);
    font-size: .92rem;
}

.hc-bi-mini-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-top: 12px;
}

.hc-bi-mini-kpi {
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.1);
}

.hc-bi-mini-kpi:nth-child(1) {
    background: linear-gradient(135deg, rgba(119, 232, 255, .18), rgba(255,255,255,.08));
}

.hc-bi-mini-kpi:nth-child(2) {
    background: linear-gradient(135deg, rgba(255, 212, 138, .18), rgba(255,255,255,.08));
}

.hc-bi-mini-kpi:nth-child(3) {
    background: linear-gradient(135deg, rgba(255, 159, 192, .18), rgba(255,255,255,.08));
}

.hc-bi-mini-kpi strong {
    display: block;
    font-size: 1.05rem;
    line-height: 1;
    color: #fff;
}

.hc-bi-mini-kpi span {
    display: block;
    margin-top: 4px;
    color: rgba(255,255,255,.74);
    font-size: .72rem;
}

.hc-bi-hero-side {
    padding: 16px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(255,255,255,.12), rgba(255,255,255,.06));
    border: 1px solid var(--hcbi-border);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 10px;
}

.hc-bi-hero-side h2 {
    margin: 0;
    font-size: .95rem;
    color: #fff;
}

.hc-bi-hero-side p {
    margin: 0;
    color: rgba(255,255,255,.74);
    font-size: .78rem;
}

.hc-bi-filters {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 12px;
    align-items: end;
}

.hc-bi-filters .bi-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.hc-bi-btn-ghost {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.16);
}

.hc-bi-topline {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.hc-bi-topline h3 {
    margin: 0;
    color: var(--hcbi-ink);
}

.hc-bi-active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.hc-bi-filter-pill {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.1);
    color: rgba(255,255,255,.84);
    font-size: .72rem;
    font-weight: 700;
}

.hc-bi-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-top: 14px;
}

.hc-bi-kpi {
    min-height: 0;
    padding: 14px 16px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(255,255,255,.14), rgba(255,255,255,.07));
    border: 1px solid var(--hcbi-border);
    box-shadow: none;
}

.hc-bi-kpi small {
    display: block;
    margin-bottom: 6px;
    font-size: .66rem;
    letter-spacing: .09em;
    text-transform: uppercase;
    color: rgba(255,255,255,.68);
}

.hc-bi-kpi strong {
    display: block;
    font-size: 1.55rem;
    line-height: 1;
    color: #fff;
}

.hc-bi-kpi span {
    display: block;
    margin-top: 5px;
    color: rgba(255,255,255,.72);
    font-size: .72rem;
}

.hc-bi-kpi:nth-child(1) {
    background: linear-gradient(135deg, rgba(111, 204, 255, .2), rgba(255,255,255,.06));
}

.hc-bi-kpi:nth-child(2) {
    background: linear-gradient(135deg, rgba(141, 240, 199, .18), rgba(255,255,255,.06));
}

.hc-bi-kpi:nth-child(3) {
    background: linear-gradient(135deg, rgba(255, 212, 138, .18), rgba(255,255,255,.06));
}

.hc-bi-kpi:nth-child(4) {
    background: linear-gradient(135deg, rgba(255, 159, 192, .18), rgba(255,255,255,.06));
}

.hc-bi-grid {
    display: grid;
    grid-template-columns: 1.05fr 1.05fr .95fr;
    gap: 12px;
    margin-top: 14px;
}

.hc-bi-card {
    padding: 14px 16px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(255,255,255,.11), rgba(255,255,255,.06));
    border: 1px solid var(--hcbi-border);
}

.hc-bi-card h3 {
    margin: 0 0 10px;
    color: var(--hcbi-ink);
    font-size: 1.05rem;
}

.hc-bi-card--hospital {
    background: linear-gradient(135deg, rgba(127, 228, 255, .14), rgba(255,255,255,.06));
}

.hc-bi-card--event {
    background: linear-gradient(135deg, rgba(141, 240, 199, .14), rgba(255,255,255,.06));
}

.hc-bi-list {
    display: grid;
    gap: 8px;
}

.hc-bi-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(255,255,255,.08);
    color: rgba(255,255,255,.88);
}

.hc-bi-card--hospital .hc-bi-list-item {
    background: linear-gradient(135deg, rgba(127, 228, 255, .14), rgba(255,255,255,.05));
}

.hc-bi-card--event .hc-bi-list-item {
    background: linear-gradient(135deg, rgba(141, 240, 199, .14), rgba(255,255,255,.05));
}

.hc-bi-list-item span:last-child {
    min-width: 28px;
    text-align: right;
    font-weight: 700;
    color: #fff;
}

.hc-bi-focus {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 100%;
    padding: 18px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(255, 212, 138, .16), rgba(255, 159, 192, .12), rgba(255,255,255,.08));
    border: 1px solid var(--hcbi-border);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.hc-bi-focus::after {
    content: "";
    position: absolute;
    inset: auto -40px -50px auto;
    width: 150px;
    height: 150px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(255,255,255,.22), rgba(255,255,255,0) 70%);
}

.hc-bi-focus small {
    display: block;
    margin-bottom: 8px;
    color: rgba(255,255,255,.7);
    text-transform: uppercase;
    letter-spacing: .09em;
    font-size: .68rem;
}

.hc-bi-focus strong {
    display: block;
    font-size: 2rem;
    line-height: 1;
    color: #fff;
    position: relative;
    z-index: 1;
}

.hc-bi-focus span {
    margin-top: 8px;
    color: rgba(255,255,255,.74);
    font-size: .76rem;
    position: relative;
    z-index: 1;
}

.hc-bi-table-wrap {
    margin-top: 14px;
}

.hc-bi-table-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.hc-bi-table-head h3,
.hc-bi-table-head p {
    margin: 0;
}

.hc-bi-table-head h3 {
    color: var(--hcbi-ink);
}

.hc-bi-table-head p {
    color: rgba(255,255,255,.68) !important;
}

@media (max-width: 1300px) {
    .hc-bi-filters {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .hc-bi-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 980px) {
    .hc-bi-hero,
    .hc-bi-kpis {
        grid-template-columns: 1fr;
    }
    .hc-bi-mini-kpis {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .hc-bi-filters {
        grid-template-columns: 1fr;
    }
    .hc-bi-topline,
    .hc-bi-table-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="bi-wrapper bi-theme hc-bi-shell">
    <div class="hc-bi-hero">
        <section class="hc-bi-hero-main">
            <div class="hc-bi-eyebrow">Cuidado domiciliar</div>
            <h1 class="bi-title">Dashboard Home Care</h1>
            <p class="hc-bi-copy">Acompanhe volume, permanência, elegibilidade e concentração da fila com acesso direto para a tela operacional de gestão.</p>
            <div class="hc-bi-mini-kpis">
                <div class="hc-bi-mini-kpi">
                    <strong><?= $totalEventos ?></strong>
                    <span>eventos de Home Care</span>
                </div>
                <div class="hc-bi-mini-kpi">
                    <strong><?= number_format($mp, 1, ',', '.') ?></strong>
                    <span>média de permanência</span>
                </div>
                <div class="hc-bi-mini-kpi">
                    <strong><?= $maiorPermanencia ?></strong>
                    <span>maior permanência</span>
                </div>
            </div>
        </section>
        <aside class="hc-bi-hero-side">
            <div>
                <h2>Fluxo operacional</h2>
                <p>Use o dashboard para leitura executiva e avance para a tela operacional quando precisar tratar elegibilidade, barreiras e implantação por caso.</p>
            </div>
            <div class="bi-header-actions">
                <a class="bi-btn bi-btn-secondary" href="<?= $BASE_URL ?>home_care_gestao.php">Gestão Home Care</a>
                <a class="bi-nav-icon" href="<?= $BASE_URL ?>bi/navegacao" title="Navegacao">
                    <i class="bi bi-grid-3x3-gap"></i>
                </a>
            </div>
        </aside>
    </div>

    <form class="bi-panel bi-filters hc-bi-filters" method="get">
        <div class="bi-filter">
            <label>Internado</label>
            <select name="internado">
                <option value="">Todos</option>
                <option value="s" <?= $internado === 's' ? 'selected' : '' ?>>Sim</option>
                <option value="n" <?= $internado === 'n' ? 'selected' : '' ?>>Não</option>
            </select>
        </div>
        <div class="bi-filter">
            <label>Hospitais</label>
            <select name="hospital_id">
                <option value="">Todos</option>
                <?php foreach ($hospitais as $h): ?>
                    <option value="<?= (int)$h['id_hospital'] ?>" <?= $hospitalId == $h['id_hospital'] ? 'selected' : '' ?>>
                        <?= e($h['nome_hosp']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label>Tipo Internação</label>
            <select name="tipo_internacao">
                <option value="">Todos</option>
                <?php foreach ($tiposInt as $tipo): ?>
                    <option value="<?= e($tipo) ?>" <?= $tipoInternação === $tipo ? 'selected' : '' ?>>
                        <?= e($tipo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label>Modo Admissão</label>
            <select name="modo_admissao">
                <option value="">Todos</option>
                <?php foreach ($modosAdm as $modo): ?>
                    <option value="<?= e($modo) ?>" <?= $modoAdmissão === $modo ? 'selected' : '' ?>>
                        <?= e($modo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label>UTI</label>
            <select name="uti">
                <option value="">Todos</option>
                <option value="s" <?= $uti === 's' ? 'selected' : '' ?>>Sim</option>
                <option value="n" <?= $uti === 'n' ? 'selected' : '' ?>>Não</option>
            </select>
        </div>
        <div class="bi-filter">
            <label>Data Internação</label>
            <input type="date" name="data_ini" value="<?= e($dataIni) ?>">
        </div>
        <div class="bi-filter">
            <label>Data Final</label>
            <input type="date" name="data_fim" value="<?= e($dataFim) ?>">
        </div>
        <div class="bi-actions">
            <button class="bi-btn" type="submit">Aplicar</button>
            <a class="bi-btn hc-bi-btn-ghost" href="<?= $BASE_URL ?>bi/home-care">Limpar</a>
        </div>
    </form>

    <div class="bi-panel" style="margin-top:14px;">
        <div class="hc-bi-topline">
            <h3>Visão executiva</h3>
            <?php if ($activeFilters): ?>
                <div class="hc-bi-active-filters">
                    <?php foreach ($activeFilters as $filterLabel): ?>
                        <span class="hc-bi-filter-pill"><?= e($filterLabel) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="hc-bi-kpis">
            <div class="hc-bi-kpi"><small>Internações</small><strong><?= $totalInternações ?></strong><span>Total de casos no recorte.</span></div>
            <div class="hc-bi-kpi"><small>Diárias</small><strong><?= $totalDiárias ?></strong><span>Soma de permanência acumulada.</span></div>
            <div class="hc-bi-kpi"><small>MP</small><strong><?= number_format($mp, 1, ',', '.') ?></strong><span>Média de permanência do período.</span></div>
            <div class="hc-bi-kpi"><small>Maior permanência</small><strong><?= $maiorPermanencia ?></strong><span>Caso mais extenso da base atual.</span></div>
        </div>
    </div>

    <div class="hc-bi-grid">
        <div class="hc-bi-card hc-bi-card--hospital">
            <h3>Hospitais</h3>
            <div class="hc-bi-list">
                <?php if (!$hospRows): ?>
                    <div class="hc-bi-list-item"><span>Sem informacoes</span><span>0</span></div>
                <?php endif; ?>
                <?php foreach ($hospRows as $row): ?>
                    <div class="hc-bi-list-item">
                        <span><?= e($row['label'] ?? 'Sem informacoes') ?></span>
                        <span><?= (int)($row['total'] ?? 0) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="hc-bi-card hc-bi-card--event">
            <h3>Tipo do evento</h3>
            <div class="hc-bi-list">
                <?php foreach ($tipoRows as $row): ?>
                    <div class="hc-bi-list-item">
                        <span><?= e($row['label'] ?? 'Home Care') ?></span>
                        <span><?= (int)($row['total'] ?? 0) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="hc-bi-focus">
            <small>No. de Home Care</small>
            <strong><?= $totalEventos ?></strong>
            <span>Quantidade de eventos identificados no período filtrado.</span>
        </div>
    </div>

    <div class="bi-panel hc-bi-table-wrap">
        <div class="hc-bi-table-head">
            <div>
                <h3>Relatorios de Home Care</h3>
                <p><?= count($rowsTable) ?> registro(s) exibidos.</p>
            </div>
            <a class="bi-btn bi-btn-secondary" href="<?= $BASE_URL ?>home_care_gestao.php">Abrir tela operacional</a>
        </div>
        <table class="bi-table">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Hospital</th>
                    <th>Tipo do evento</th>
                    <th>Relatorio</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rowsTable): ?>
                    <tr>
                        <td colspan="4">Sem informacoes para o filtro selecionado.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rowsTable as $row): ?>
                    <tr>
                        <td><?= e($row['paciente'] ?? '-') ?></td>
                        <td><?= e($row['hospital'] ?? '-') ?></td>
                        <td>Home Care</td>
                        <td><?= e($row['relatorio'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
