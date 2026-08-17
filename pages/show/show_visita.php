<?php
include_once("check_logado.php");
include_once("globals.php"); // $conn, $BASE_URL
Gate::enforceAction($conn, $BASE_URL, 'view', 'Você não tem permissão para visualizar este registro.');
include_once("templates/header.php");

include_once("models/message.php");
include_once("models/visita.php");
include_once("dao/visitaDao.php");
include_once("models/internacao.php");
include_once("dao/internacaoDao.php");
include_once("models/hospital.php");
include_once("dao/hospitalDao.php");

/* ============== Helpers ============== */
function safe($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalize_date_ymd_from_string($raw)
{
    $raw = trim((string)$raw);
    if ($raw === '') return '';
    $d10 = substr($raw, 0, 10);
    $dt  = DateTime::createFromFormat('Y-m-d', $d10);
    if ($dt) return $dt->format('Y-m-d');
    $dt2 = DateTime::createFromFormat('d/m/Y', $d10);
    if ($dt2) return $dt2->format('Y-m-d');
    return '';
}
function pick_visit_date_raw(array $r)
{
    foreach (['data_visita_vis', 'data_visita', 'data', 'created_at', 'data_visita_int'] as $k) {
        if (!empty($r[$k])) return $r[$k];
    }
    return '';
}
function pick_visit_user(array $r)
{
    foreach (['usuario_vis', 'usuario_create', 'usuario', 'responsavel', 'profissional', 'medico', 'nome_usuario'] as $k) {
        if (!empty($r[$k])) return $r[$k];
    }
    return '';
}
function pick_visit_text(array $r)
{
    foreach (['rel_visita', 'rel_visita_vis', 'rel_vis', 'relatorio', 'observacao', 'obs', 'descricao'] as $k) {
        if (empty($r[$k])) {
            continue;
        }
        $text = trim((string)$r[$k]);
        if (preg_match('/^(Importado do OCR do PDF|Complementado via OCR)/i', $text)) {
            continue;
        }
        return $text;
    }
    return '';
}

function formatDateBr($dateYmd)
{
    if (!$dateYmd || $dateYmd === '0000-00-00') return '';
    $dt = DateTime::createFromFormat('Y-m-d', substr($dateYmd, 0, 10));
    return $dt ? $dt->format('d/m/Y') : $dateYmd;
}

function initials_from_name($name)
{
    $name = trim((string)$name);
    if ($name === '') return 'PA';
    $parts = preg_split('/\s+/', $name);
    $first = substr($parts[0] ?? '', 0, 1);
    $second = substr($parts[1] ?? '', 0, 1);
    return strtoupper($first . $second);
}

function firstFilled(array $row, array $keys): string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $row)) {
            continue;
        }
        $value = trim((string)$row[$key]);
        if ($value !== '') {
            return $value;
        }
    }
    return '';
}

function yesNoLabel($value): string
{
    $value = strtolower(trim((string)$value));
    if ($value === '') return '';
    if (in_array($value, ['s', 'sim', '1', 'yes', 'true'], true)) return 'Sim';
    if (in_array($value, ['n', 'nao', 'não', '0', 'no', 'false'], true)) return 'Não';
    return ucfirst($value);
}

function compactRowsBySql(PDO $conn, string $sql, array $params): array
{
    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function nonEmptyText($value): string
{
    $text = trim((string)$value);
    return $text === '' ? '' : $text;
}

/* ============== GET / Base ============== */
$id_visita = filter_input(INPUT_GET, 'id_visita', FILTER_SANITIZE_NUMBER_INT);
$vpage     = max(1, (int)($_GET['vpage'] ?? 1));
$pageSize  = 7;

if (!$id_visita) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Visita não informada.</div></div>";
    include_once("templates/footer.php");
    exit;
}

$visitaDao = new visitaDAO($conn, $BASE_URL);
$rows = $visitaDao->joinVisitaShow($id_visita);

if (!$rows || !isset($rows[0])) {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Registro da visita não encontrado.</div></div>";
    include_once("templates/footer.php");
    exit;
}
$v = $rows[0];

/* ============== Cabeçalho ============== */
$nome_pac       = $v['nome_pac']        ?? '';
$ini            = initials_from_name($nome_pac);
$id_internacao  = $v['id_internacao']   ?? ($v['fk_internacao_vis'] ?? '');
$id_visita_row  = $v['id_visita']       ?? $id_visita;
$hospital_nome  = $v['nome_hosp']       ?? '';

$dv_raw         = $v['data_visita_int'] ?? ($v['data_visita_vis'] ?? ($v['data_visita'] ?? ''));
$dv_norm        = normalize_date_ymd_from_string($dv_raw);
$data_visita    = formatDateBr($dv_norm);

$data_intern    = formatDateBr($v['data_intern_int'] ?? '');
$acomodacao     = $v['acomodacao_int']  ?? '';

$relatorioVisita = nonEmptyText($v['rel_visita_vis'] ?? '');
$acoesAuditoria  = nonEmptyText($v['acoes_int_vis'] ?? '');
$programacao     = nonEmptyText($v['programacao_enf'] ?? '');
$auditorVisita   = firstFilled($v, ['usuario_vis', 'usuario_create', 'visita_auditor_prof_med', 'visita_auditor_prof_enf', 'nome_usuario']);

$detalhesRows = ($conn instanceof PDO)
    ? compactRowsBySql(
        $conn,
        "SELECT * FROM tb_detalhes WHERE fk_vis_det = :visita ORDER BY fk_vis_det DESC LIMIT 1",
        [':visita' => (int)$id_visita_row]
    )
    : [];
$detalhes = $detalhesRows[0] ?? [];

$utiRows = ($conn instanceof PDO)
    ? compactRowsBySql(
        $conn,
        "SELECT * FROM tb_uti WHERE fk_visita_uti = :visita ORDER BY id_uti DESC",
        [':visita' => (int)$id_visita_row]
    )
    : [];

$prorrogacaoRows = ($conn instanceof PDO)
    ? compactRowsBySql(
        $conn,
        "SELECT * FROM tb_prorrogacao WHERE fk_visita_pror = :visita ORDER BY id_prorrogacao DESC",
        [':visita' => (int)$id_visita_row]
    )
    : [];

$tussRows = ($conn instanceof PDO)
    ? compactRowsBySql(
        $conn,
        "SELECT * FROM tb_tuss WHERE fk_vis_tuss = :visita ORDER BY id_tuss DESC",
        [':visita' => (int)$id_visita_row]
    )
    : [];

$negociacaoRows = ($conn instanceof PDO)
    ? compactRowsBySql(
        $conn,
        "SELECT * FROM tb_negociacao WHERE fk_visita_neg = :visita ORDER BY id_negociacao DESC",
        [':visita' => (int)$id_visita_row]
    )
    : [];

$detalhesClinicos = [
    'Nível de consciência' => firstFilled($detalhes, ['nivel_consc_det']),
    'Dieta' => firstFilled($detalhes, ['dieta_det']),
    'Oxigênio' => trim(firstFilled($detalhes, ['oxig_det']) . ' ' . firstFilled($detalhes, ['oxig_uso_det'])),
    'Antibiótico' => trim(firstFilled($detalhes, ['atb_det']) . ' ' . firstFilled($detalhes, ['atb_uso_det'])),
    'Curativo' => firstFilled($detalhes, ['curativo_det']),
    'Dispositivo' => firstFilled($detalhes, ['dispositivo_det']),
    'Diálise' => firstFilled($detalhes, ['dialise_det']),
    'Hemoderivados' => firstFilled($detalhes, ['hemoderivados_det']),
    'Acamado' => firstFilled($detalhes, ['acamado_det']),
    'TQT' => firstFilled($detalhes, ['tqt_det']),
    'SVD' => firstFilled($detalhes, ['svd_det']),
    'GTT' => firstFilled($detalhes, ['gtt_det']),
    'Dreno' => firstFilled($detalhes, ['dreno_det']),
    'Lesões de pele' => firstFilled($detalhes, ['lesoes_pele_det']),
    'Medicamento alto custo' => trim(firstFilled($detalhes, ['medic_alto_custo_det']) . ' ' . firstFilled($detalhes, ['qual_medicamento_det'])),
    'Paliativos' => firstFilled($detalhes, ['paliativos_det']),
    'Braden' => firstFilled($detalhes, ['braden_det']),
    'Liminar' => firstFilled($detalhes, ['liminar_det']),
    'Parto' => firstFilled($detalhes, ['parto_det']),
];
$detalhesClinicos = array_filter($detalhesClinicos, fn($item) => trim((string)$item) !== '');

/* ============== Descobrir id_paciente (se disponível) ============== */
$id_paciente = $v['id_paciente'] ?? ($v['fk_paciente_int'] ?? ($v['id_pac'] ?? null));

/* ============== Carregar TODAS as visitas da internação ============== */
$all = [];
try {
    if (!empty($id_internacao) && method_exists($visitaDao, 'findGeralByIntern')) {
        $all = $visitaDao->findGeralByIntern((int)$id_internacao) ?: [];
        foreach ($all as &$r) {
            if (!isset($r['id_internacao']) && isset($r['fk_internacao_vis'])) {
                $r['id_internacao'] = $r['fk_internacao_vis'];
            }
            $r['data_visita_int'] = normalize_date_ymd_from_string(pick_visit_date_raw($r));
            if (!isset($r['usuario_vis'])) {
                $r['usuario_vis'] = !empty($r['usuario_create'])
                    ? $r['usuario_create']
                    : (isset($r['fk_usuario_vis']) ? ('ID ' . $r['fk_usuario_vis']) : '');
            }
            if (!isset($r['nome_hosp']) || $r['nome_hosp'] === '') {
                $r['nome_hosp'] = $hospital_nome;
            }
        }
        unset($r);
    }
} catch (Throwable $e) {
    $all = [];
}

/* ======= Ordenação: mais antiga -> mais recente (ASC) ======= */
if ($all) {
    usort($all, function ($a, $b) {
        return strcmp($a['data_visita_int'] ?? '', $b['data_visita_int'] ?? '');
    });
}

$total_intern = count($all);

/* ====== Extremos globais para o range do rodapé ====== */
$allTs = array_values(array_filter(array_map(function ($r) {
    $d = $r['data_visita_int'] ?? '';
    $ts = $d ? @strtotime($d) : null;
    return $ts ?: null;
}, $all)));
$minAllTs = $allTs ? min($allTs) : null;
$maxAllTs = $allTs ? max($allTs) : null;

/* Opcional: total por PACIENTE (todas as internações) */
$total_paciente = null;
if ($id_paciente && $conn instanceof PDO) {
    try {
        $sqlCnt = "SELECT COUNT(*) AS c
                 FROM tb_visita v
                 JOIN tb_internacao i ON i.id_internacao = v.fk_internacao_vis
                WHERE v.retificado IS NULL
                  AND i.fk_paciente_int = :pid";
        $stc = $conn->prepare($sqlCnt);
        $stc->bindValue(':pid', (int)$id_paciente, PDO::PARAM_INT);
        $stc->execute();
        $rowC = $stc->fetch(PDO::FETCH_ASSOC);
        if ($rowC && isset($rowC['c'])) $total_paciente = (int)$rowC['c'];
    } catch (Throwable $e) { /* ignore */
    }
}
$TOTAL_VISITAS_EXIBIR = $total_paciente ?? $total_intern;

/* Paginação: 7 por página (página 1 = mais antigas) */
$pages  = max(1, (int)ceil($total_intern / $pageSize));
if ($vpage > $pages) $vpage = $pages;
$offset = ($vpage - 1) * $pageSize;
$timeline = array_slice($all, $offset, $pageSize);

/* Posição proporcional baseada no subset da página (crescente) */
$datesTs = [];
foreach ($timeline as $tt) {
    $raw = $tt['data_visita_int'] ?? '';
    $ts  = $raw ? @strtotime($raw) : null;
    if ($ts) $datesTs[] = $ts;
}
$minTs = $datesTs ? min($datesTs) : null;
$maxTs = $datesTs ? max($datesTs) : null;
$count = count($timeline);

/* Trilho: largura base; contido via max-width:100% no CSS */
$trackWidthPx = max(800, $count * 160);

$baseUrlSelf = strtok($_SERVER["REQUEST_URI"], '?');
$queryBase   = "id_visita={$id_visita_row}&tab=timeline";
$prevUrl     = "{$baseUrlSelf}?{$queryBase}&vpage=" . max(1, $vpage - 1);      // mais antigas
$nextUrl     = "{$baseUrlSelf}?{$queryBase}&vpage=" . min($pages, $vpage + 1); // mais recentes
?>
<div id="main-container" class="container-fluid py-2 visita-show-page">
    <!-- Cabeçalho -->
    <div class="card shadow-sm mb-2 header-card patient-identity-card">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex gap-3 align-items-center">
                <div class="v2-avatar visit-avatar">
                    <?= safe($ini) ?>
                </div>
                <div class="patient-identity-main">
                    <h4 class="mb-1"><?= safe($nome_pac ?: 'Paciente') ?></h4>
                    <div class="d-flex flex-wrap gap-2 text-secondary small patient-identity-meta">
                        <span><i
                                class="bi bi-hospital me-1"></i><?= safe($hospital_nome ?: 'Hospital não informado') ?></span>
                        <span><i class="bi bi-heart-pulse me-1"></i>Internação
                            #<?= safe($id_internacao ?: '—') ?></span>
                        <span><i class="bi bi-person-badge me-1"></i>Visita
                            #<?= safe($id_visita_row ?: '—') ?></span>
                        <span><i class="bi bi-calendar-event me-1"></i>Data da visita:
                            <?= safe($data_visita ?: '—') ?></span>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <div class="small text-secondary">Data da internação</div>
                <div class="fw-semibold"><?= safe($data_intern ?: '—') ?></div>
                <div class="small text-secondary mt-2">Acomodação</div>
                <div class="fw-semibold"><?= safe($acomodacao ?: '—') ?></div>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <div class="card shadow-sm visita-main-card">
        <div class="card-body">
            <ul class="nav nav-pills mb-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-resumo" type="button"
                        role="tab">
                        <i class="bi bi-list-ul me-2"></i>Resumo
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-timeline" type="button"
                        role="tab">
                        <i class="bi bi-clock-history me-2"></i>Linha do tempo
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Resumo -->
                <div class="tab-pane fade show active" id="tab-resumo" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="card ov-card ov-int">
                                <div class="card-body">
                                    <div class="ov-head">
                                        <div class="ov-icon"><i class="bi bi-hospital"></i></div>
                                        <h6 class="ov-title mb-0">Internação</h6>
                                    </div>
                                    <dl class="details-dl">
                                        <dt>Código</dt>
                                        <dd>#<?= safe($id_internacao ?: '—') ?></dd>
                                        <dt>Data internação</dt>
                                        <dd><?= safe($data_intern ?: '—') ?></dd>
                                        <dt>Acomodação</dt>
                                        <dd><?= safe($acomodacao ?: '—') ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="card ov-card ov-vis">
                                <div class="card-body">
                                    <div class="ov-head">
                                        <div class="ov-icon"><i class="bi bi-person-badge"></i></div>
                                        <h6 class="ov-title mb-0">Visita</h6>
                                    </div>
                                    <dl class="details-dl">
                                        <dt>Código</dt>
                                        <dd>#<?= safe($id_visita_row ?: '—') ?></dd>
                                        <dt>Data</dt>
                                        <dd><?= safe($data_visita ?: '—') ?></dd>
                                        <dt>Hospital</dt>
                                        <dd><?= safe($hospital_nome ?: '—') ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="visit-summary-grid">
                        <section class="visit-panel visit-panel-wide visit-panel-report">
                            <div class="visit-panel-head">
                                <span class="visit-panel-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                                <div>
                                    <h6>Relatório da visita</h6>
                                    <span>Auditoria clínica</span>
                                </div>
                            </div>
                            <div class="visit-text-box">
                                <?= $relatorioVisita !== '' ? nl2br(safe($relatorioVisita)) : '<span class="visit-empty">Sem relatório registrado.</span>' ?>
                            </div>
                        </section>

                        <section class="visit-panel visit-panel-professional">
                            <div class="visit-panel-head">
                                <span class="visit-panel-icon"><i class="bi bi-person-badge"></i></span>
                                <div>
                                    <h6>Profissional</h6>
                                    <span>Dados da visita</span>
                                </div>
                            </div>
                            <dl class="visit-info-list">
                                <div><dt>Auditor</dt><dd><?= safe($auditorVisita ?: '—') ?></dd></div>
                                <div><dt>Data</dt><dd><?= safe($data_visita ?: '—') ?></dd></div>
                                <div><dt>Hospital</dt><dd><?= safe($hospital_nome ?: '—') ?></dd></div>
                            </dl>
                        </section>

                        <section class="visit-panel visit-panel-actions">
                            <div class="visit-panel-head">
                                <span class="visit-panel-icon"><i class="bi bi-list-check"></i></span>
                                <div>
                                    <h6>Ações da auditoria</h6>
                                    <span>Condutas registradas</span>
                                </div>
                            </div>
                            <div class="visit-text-box visit-text-box-compact">
                                <?= $acoesAuditoria !== '' ? nl2br(safe($acoesAuditoria)) : '<span class="visit-empty">Sem ações registradas.</span>' ?>
                            </div>
                        </section>

                        <section class="visit-panel visit-panel-wide visit-panel-program">
                            <div class="visit-panel-head">
                                <span class="visit-panel-icon"><i class="bi bi-calendar2-heart"></i></span>
                                <div>
                                    <h6>Programação terapêutica</h6>
                                    <span>Plano assistencial</span>
                                </div>
                            </div>
                            <div class="visit-text-box visit-text-box-compact">
                                <?= $programacao !== '' ? nl2br(safe($programacao)) : '<span class="visit-empty">Sem programação registrada.</span>' ?>
                            </div>
                        </section>

                        <section class="visit-panel visit-panel-wide visit-panel-clinical">
                            <div class="visit-panel-head">
                                <span class="visit-panel-icon"><i class="bi bi-activity"></i></span>
                                <div>
                                    <h6>Detalhes clínicos</h6>
                                    <span>Itens assistenciais</span>
                                </div>
                            </div>
                            <?php if ($detalhesClinicos): ?>
                                <div class="visit-chip-grid">
                                    <?php foreach ($detalhesClinicos as $label => $value): ?>
                                        <div class="visit-chip">
                                            <span><?= safe($label) ?></span>
                                            <strong><?= safe(yesNoLabel($value) ?: $value) ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="visit-empty-box">Sem detalhes clínicos registrados.</div>
                            <?php endif; ?>
                        </section>

                        <section class="visit-panel visit-panel-wide visit-uti-panel">
                            <div class="visit-panel-head">
                                <span class="visit-panel-icon"><i class="bi bi-hospital"></i></span>
                                <div>
                                    <h6>UTI</h6>
                                    <span><?= count($utiRows) ?> registro(s)</span>
                                </div>
                            </div>
                            <?php if ($utiRows): ?>
                                <?php foreach ($utiRows as $uti): ?>
                                    <div class="visit-mini-card visit-uti-card">
                                        <div><span>Internado</span><strong><?= safe(yesNoLabel($uti['internado_uti'] ?? '') ?: '—') ?></strong></div>
                                        <div><span>Entrada</span><strong><?= safe(formatDateBr(normalize_date_ymd_from_string($uti['data_internacao_uti'] ?? '')) ?: '—') ?></strong></div>
                                        <div><span>VM</span><strong><?= safe(yesNoLabel($uti['vm_uti'] ?? '') ?: '—') ?></strong></div>
                                        <div><span>Score</span><strong><?= safe($uti['score_uti'] ?? '—') ?></strong></div>
                                        <?php $utiRel = nonEmptyText(($uti['rel_uti'] ?? '') ?: ($uti['justifique_uti'] ?? '') ?: ($uti['motivo_uti'] ?? '')); ?>
                                        <?php if ($utiRel !== ''): ?>
                                            <p><?= nl2br(safe($utiRel)) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="visit-empty-box">Sem registro de UTI nesta visita.</div>
                            <?php endif; ?>
                        </section>

                        <section class="visit-panel visit-panel-wide visit-panel-tables">
                            <div class="visit-panel-head">
                                <span class="visit-panel-icon"><i class="bi bi-table"></i></span>
                                <div>
                                    <h6>Tabelas adicionais</h6>
                                    <span>Prorrogações, TUSS e negociações</span>
                                </div>
                            </div>

                            <div class="visit-table-group">
                                <div class="visit-table-block">
                                    <div class="visit-subtitle"><i class="bi bi-arrow-repeat"></i> Prorrogações <span><?= count($prorrogacaoRows) ?></span></div>
                                    <?php if ($prorrogacaoRows): ?>
                                        <div class="table-responsive">
                                            <table class="table visit-mini-table">
                                                <thead><tr><th>Acomodação</th><th>Início</th><th>Fim</th><th>Diárias</th><th>Isolamento</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($prorrogacaoRows as $pror): ?>
                                                        <tr>
                                                            <td><?= safe($pror['acomod1_pror'] ?? '—') ?></td>
                                                            <td><?= safe(formatDateBr(normalize_date_ymd_from_string($pror['prorrog1_ini_pror'] ?? '')) ?: '—') ?></td>
                                                            <td><?= safe(formatDateBr(normalize_date_ymd_from_string($pror['prorrog1_fim_pror'] ?? '')) ?: '—') ?></td>
                                                            <td><?= safe($pror['diarias_1'] ?? '—') ?></td>
                                                            <td><?= safe(yesNoLabel($pror['isol_1_pror'] ?? '') ?: '—') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="visit-empty-line">Sem prorrogação vinculada.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="visit-table-block">
                                    <div class="visit-subtitle"><i class="bi bi-receipt"></i> TUSS <span><?= count($tussRows) ?></span></div>
                                    <?php if ($tussRows): ?>
                                        <div class="table-responsive">
                                            <table class="table visit-mini-table">
                                                <thead><tr><th>Solicitado</th><th>Qtd. solic.</th><th>Liberado</th><th>Qtd. lib.</th><th>Data</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($tussRows as $tuss): ?>
                                                        <tr>
                                                            <td><?= safe($tuss['tuss_solicitado'] ?? '—') ?></td>
                                                            <td><?= safe($tuss['qtd_tuss_solicitado'] ?? '—') ?></td>
                                                            <td><?= safe(yesNoLabel($tuss['tuss_liberado_sn'] ?? '') ?: '—') ?></td>
                                                            <td><?= safe($tuss['qtd_tuss_liberado'] ?? '—') ?></td>
                                                            <td><?= safe(formatDateBr(normalize_date_ymd_from_string($tuss['data_realizacao_tuss'] ?? '')) ?: '—') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="visit-empty-line">Sem TUSS vinculada.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="visit-table-block">
                                    <div class="visit-subtitle"><i class="bi bi-diagram-3"></i> Negociações <span><?= count($negociacaoRows) ?></span></div>
                                    <?php if ($negociacaoRows): ?>
                                        <div class="table-responsive">
                                            <table class="table visit-mini-table">
                                                <thead><tr><th>Tipo</th><th>De</th><th>Para</th><th>Período</th><th>Qtd.</th><th>Saving</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($negociacaoRows as $neg): ?>
                                                        <tr>
                                                            <td><?= safe($neg['tipo_negociacao'] ?? '—') ?></td>
                                                            <td><?= safe($neg['troca_de'] ?? '—') ?></td>
                                                            <td><?= safe($neg['troca_para'] ?? '—') ?></td>
                                                            <td>
                                                                <?= safe(formatDateBr(normalize_date_ymd_from_string($neg['data_inicio_neg'] ?? '')) ?: '—') ?>
                                                                —
                                                                <?= safe(formatDateBr(normalize_date_ymd_from_string($neg['data_fim_neg'] ?? '')) ?: '—') ?>
                                                            </td>
                                                            <td><?= safe($neg['qtd'] ?? '—') ?></td>
                                                            <td><?= safe($neg['saving'] ?? '—') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="visit-empty-line">Sem negociação vinculada.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="tab-pane fade" id="tab-timeline" role="tabpanel">
                    <?php if ($timeline && count($timeline) > 0): ?>
                        <div class="timeline-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="small text-secondary">
                                Mostrando
                                <strong><?= $offset + 1 ?></strong>–<strong><?= min($offset + $pageSize, $total_intern) ?></strong>
                                de <strong><?= $total_intern ?></strong> visitas desta internação
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <?php if (empty($v['retificado']) && $total_intern > 1): ?>
                                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#modalDeleteVisita" data-delete-visita="<?= (int) $id_visita_row ?>">
                                        <i class="bi bi-trash3 me-1"></i>Remover visita
                                    </button>
                                <?php endif; ?>
                                <?php if ($pages > 1): ?>
                                <div class="btn-group btn-group-sm timeline-pager" role="group" aria-label="pager">
                                    <a class="btn btn-outline-secondary <?= $vpage <= 1 ? 'disabled' : '' ?>"
                                        href="<?= safe($prevUrl) ?>">‹ Mais antigas</a>
                                    <span class="btn btn-outline-secondary disabled">Página
                                        <?= (int)$vpage ?>/<?= (int)$pages ?></span>
                                    <a class="btn btn-outline-secondary <?= $vpage >= $pages ? 'disabled' : '' ?>"
                                        href="<?= safe($nextUrl) ?>">Mais recentes ›</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="ht-container">
                            <div class="ht-track" style="width: <?= (int)$trackWidthPx ?>px">
                                <div class="ht-bar"></div>
                                <?php foreach ($timeline as $idx => $t):
                                    $idv   = (int)($t['id_visita'] ?? 0);
                                    $raw   = $t['data_visita_int'] ?? '';
                                    $dtBr  = formatDateBr($raw);
                                    $orig  = trim((string)($t['data_visita_vis'] ?? ''));
                                    $label = $dtBr !== '' ? $dtBr : ($orig !== '' ? $orig : '—');

                                    $ts = $raw ? @strtotime($raw) : null;
                                    if ($minTs && $maxTs && $minTs !== $maxTs && $ts) {
                                        // crescente: antiga (0%) -> nova (100%)
                                        $pos = round((($ts - $minTs) / ($maxTs - $minTs)) * 100, 2);
                                    } else {
                                        $pos = $count > 1 ? round(($idx / ($count - 1)) * 100, 2) : 50;
                                    }

                                    // clamp e classes por posição (evita corte)
                                    $pos = max(2, min(98, $pos));
                                    $edgeLeft  = ($pos <= 3.5);
                                    $edgeRight = ($pos >= 96.5);
                                    $edgeCls   = ($edgeLeft ? ' edge-left' : '') . ($edgeRight ? ' edge-right' : '');

                                    $responsavel = trim((string)($t['usuario_vis'] ?? ($t['usuario_create'] ?? '')));
                                    $hosp        = $t['nome_hosp'] ?? '';
                                    $isCurrent   = ((int)$id_visita_row === $idv);
                                    $rel_html    = safe($t['rel_visita_vis'] ?? '');
                                    $retificadoFlag = !empty($t['retificado']);
                                ?>
                                    <a class="ht-marker <?= $isCurrent ? 'active' : '' ?><?= $edgeCls ?>" href="#"
                                        style="left: <?= $pos ?>%;" data-id="<?= $idv ?>" data-date="<?= safe($label) ?>"
                                        data-user="<?= safe($responsavel) ?>" data-hosp="<?= safe($hosp) ?>"
                                        data-retificado="<?= $retificadoFlag ? '1' : '0' ?>"
                                        data-rel="<?= $rel_html ?>">
                                        <span class="ht-label"><?= safe($label) ?></span>
                                        <span class="ht-dot"></span>
                                        <div class="ht-pop">
                                            <?php if ($responsavel): ?>
                                                <div class="small text-secondary mb-1"><i
                                                        class="bi bi-person-badge me-1"></i><?= safe($responsavel) ?></div>
                                            <?php endif; ?>
                                            <div class="small"><i class="bi bi-hospital me-1"></i><?= safe($hosp) ?></div>
                                            <div class="small mt-1">Visita #<?= $idv ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Detalhes -->
                        <div id="tl-details" class="card timeline-details" style="display:none;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                    <h6 class="mb-0">
                                        <i class="bi bi-clipboard2-pulse me-2"></i>
                                        Relatório da visita <span id="tl-id" class="badge bg-secondary"></span>
                                    </h6>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <div class="small text-secondary">
                                            <i class="bi bi-calendar-event me-1"></i><span id="tl-date"></span>
                                            <span id="tl-sep1" style="display:none;"> • </span>
                                            <i class="bi bi-person-badge ms-2 me-1"></i><span id="tl-user"></span>
                                            <span id="tl-sep2" style="display:none;"> • </span>
                                            <i class="bi bi-hospital ms-2 me-1"></i><span id="tl-hosp"></span>
                                            <span id="tl-sep3" style="display:none;"> • </span>
                                            <span id="tl-total-wrap" style="display:none;">Total visitas: <span
                                                    id="tl-total"></span></span>
                                        </div>
                                        <button type="button" id="tl-delete-btn" class="btn btn-outline-danger btn-sm d-none"
                                            data-bs-toggle="modal" data-bs-target="#modalDeleteVisita"
                                            data-delete-visita="<?= (int) $id_visita_row ?>">
                                            <i class="bi bi-trash3 me-1"></i>Remover
                                        </button>
                                    </div>
                                </div>
                                <div id="tl-rel" class="text-body" style="white-space: pre-line;"></div>
                            </div>
                        </div>

                        <div class="timeline-caption d-flex justify-content-between align-items-center">
                            <div class="small text-secondary">
                                <?php if ($minAllTs && $maxAllTs): ?>
                                    <?= safe(date('d/m/Y', $minAllTs)) ?> — <?= safe(date('d/m/Y', $maxAllTs)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="small"><span class="legend-dot"></span> Clique nas datas para ver o relatório abaixo
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border text-secondary">Não há visitas nesta página.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 visit-page-footer">
                <div class="small text-secondary">Atualizado: <?= safe(date('d/m/Y H:i')) ?></div>
                <div class="d-flex gap-2 visit-footer-actions">
                    <?php
                    $backHref = !empty($_SERVER['HTTP_REFERER']) ? 'javascript:history.back()' : $BASE_URL . 'internacoes.php';
                    ?>
                    <a href="<?= safe($BASE_URL . 'visitas/nova/internacao/' . (int)$id_internacao . '?edit_visita=' . (int)$id_visita_row) ?>"
                        class="btn btn-sm visit-action-btn visit-action-edit">
                        <i class="bi bi-pencil-square"></i><span>Editar visita</span>
                    </a>
                    <a href="<?= safe($backHref) ?>" class="btn btn-sm visit-action-btn visit-action-back">
                        <i class="bi bi-arrow-left"></i><span>Voltar</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteVisita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Remover visita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja remover esta visita? Essa ação não exclui os dados do banco,
                    mas a visita deixará de aparecer nas listas e relatórios.</p>
                <div class="alert alert-danger d-none js-delete-feedback" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm-delete">Remover visita</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Estilos ===== -->
<style>
    :root {
        --brand: #5e2363;
        --brand-700: #4b1c50;
        --brand-800: #431945;
        --brand-100: #f2e8f7;
        --brand-050: #f9f3fc;
        --teal: #0f766e;
        --teal-100: #d1fae5;
        --padX: 56px;
        /* respiro lateral maior para não cortar etiquetas */
    }

    .visita-show-page .btn-primary {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
    }

    .visita-show-page .btn-primary:hover {
        background-color: var(--brand-700) !important;
        border-color: var(--brand-700) !important;
    }

    .visita-show-page .btn-primary:focus,
    .visita-show-page .btn-primary:active {
        background-color: var(--brand-800) !important;
        border-color: var(--brand-800) !important;
        box-shadow: 0 0 0 .2rem rgba(94, 35, 99, .25) !important;
    }

    .visita-show-page .btn-outline-secondary {
        border-color: #e0d4ea !important;
    }

    .visita-show-page .btn-outline-secondary:hover {
        background: var(--brand-050) !important;
    }

    .visita-show-page .nav-pills .nav-link {
        color: var(--brand);
    }

    .visita-show-page .nav-pills .nav-link:hover {
        background: var(--brand-050);
    }

    .visita-show-page .nav-pills .nav-link.active {
        background-color: var(--brand) !important;
    }

    .visita-show-page .card {
        border-radius: 14px;
    }

    .visita-show-page .card.shadow-sm {
        box-shadow: 0 8px 24px rgba(0, 0, 0, .06) !important;
    }

    .visita-show-page .ov-card {
        position: relative;
        border: 0 !important;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .06) !important;
        background: #fff;
    }

    .visita-show-page .ov-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 6px;
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
        background: var(--ov-accent, var(--brand));
        opacity: .9;
    }

    .visita-show-page .ov-head {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .5rem;
    }

    .visita-show-page .ov-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--ov-accent-100, var(--brand-100));
        color: var(--ov-accent, var(--brand));
        flex: 0 0 36px;
        font-size: 16px;
    }

    .visita-show-page .ov-title {
        margin: 0;
        font-weight: 600;
        color: var(--ov-accent, var(--brand));
    }

    .visita-show-page .ov-int {
        --ov-accent: var(--brand);
        --ov-accent-100: var(--brand-100);
    }

    .visita-show-page .ov-vis {
        --ov-accent: var(--teal);
        --ov-accent-100: var(--teal-100);
    }

    .visita-show-page {
        font-size: .78rem;
    }

    .visita-show-page .patient-identity-card {
        border: 1px solid #c4dceb !important;
        border-left: 5px solid #2f78a8 !important;
        background: linear-gradient(90deg, #f2f8fc 0%, #ffffff 44%, #fbfdff 100%) !important;
        box-shadow: 0 8px 18px rgba(47, 120, 168, .08) !important;
        border-radius: 12px !important;
    }

    .visita-show-page .header-card .card-body {
        padding: .72rem 1rem !important;
    }

    .visita-show-page .visit-avatar,
    .visita-show-page .v2-avatar {
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        border-radius: 50% !important;
        background: #dff0fb !important;
        color: #1d4f72 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: .8rem !important;
        font-weight: 800 !important;
    }

    .visita-show-page .header-card h4 {
        font-size: 1rem !important;
        line-height: 1.15 !important;
        margin-bottom: .22rem !important;
        color: #0f2538 !important;
        font-weight: 800 !important;
    }

    .visita-show-page .header-card .small,
    .visita-show-page .header-card .text-secondary {
        font-size: .72rem !important;
        line-height: 1.2 !important;
    }

    .visita-show-page .patient-identity-meta span {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #d6e6f1;
        color: #5d6b7a;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .035);
    }

    .visita-show-page .text-end .small {
        font-size: .68rem !important;
    }

    .visita-show-page .text-end .fw-semibold {
        font-size: .78rem !important;
        color: #4b5563;
    }

    .visita-show-page .visita-main-card {
        border-radius: 12px !important;
        border: 1px solid #d7e5ef !important;
    }

    .visita-show-page .visita-main-card > .card-body {
        padding: .8rem .9rem .65rem !important;
    }

    .visita-show-page .nav-pills {
        margin-bottom: .65rem !important;
        gap: .25rem;
    }

    .visita-show-page .nav-pills .nav-link {
        padding: .42rem .68rem !important;
        font-size: .72rem !important;
        font-weight: 700 !important;
        border-radius: 10px !important;
        line-height: 1.15 !important;
        color: #2f78a8 !important;
    }

    .visita-show-page .nav-pills .nav-link i {
        font-size: .72rem !important;
        margin-right: .32rem !important;
    }

    .visita-show-page .nav-pills .nav-link:hover {
        background: #edf7fc !important;
    }

    .visita-show-page .nav-pills .nav-link.active {
        background: #2f78a8 !important;
        color: #fff !important;
    }

    .visita-show-page .row.g-3 {
        --bs-gutter-x: .7rem;
        --bs-gutter-y: .7rem;
    }

    .visita-show-page .ov-card {
        border: 1px solid #d7e5ef !important;
        border-radius: 10px !important;
        box-shadow: 0 8px 18px rgba(47, 120, 168, .06) !important;
    }

    .visita-show-page .ov-card::before {
        width: 5px;
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }

    .visita-show-page .ov-card .card-body {
        padding: .76rem .9rem !important;
    }

    .visita-show-page .ov-head {
        gap: .36rem !important;
        margin-bottom: .38rem !important;
    }

    .visita-show-page .ov-icon {
        width: 28px !important;
        height: 28px !important;
        flex-basis: 28px !important;
        border-radius: 8px !important;
        font-size: .78rem !important;
    }

    .visita-show-page .ov-title,
    .visita-show-page .tab-pane h6 {
        font-size: .82rem !important;
        line-height: 1.2 !important;
        font-weight: 700 !important;
    }

    .visita-show-page .details-dl {
        display: grid;
        grid-template-columns: 96px minmax(0, 1fr);
        row-gap: 5px;
        column-gap: 10px;
        margin: 0;
    }

    .visita-show-page .details-dl dt,
    .visita-show-page .details-dl dd {
        font-size: .7rem !important;
        line-height: 1.2 !important;
        font-weight: 600 !important;
        margin: 0;
    }

    .visita-show-page .details-dl dt {
        color: #6f7785;
    }

    .visita-show-page .details-dl dd {
        color: #303947;
        overflow-wrap: anywhere;
    }

    .visit-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .45rem;
    }

    #main-container.visita-show-page #tab-resumo > .row.g-3 + .visit-summary-grid {
        margin-top: .12rem !important;
    }

    .visit-panel {
        border: 1px solid #cfe0ec;
        border-left: 5px solid #2f78a8;
        border-radius: 10px;
        background: #f8fbfd;
        padding: .76rem .9rem;
        box-shadow: 0 8px 20px rgba(43, 72, 96, .09);
        min-width: 0;
    }

    .visit-panel-report,
    .visit-panel-actions {
        border-left-color: #2f78a8;
    }

    .visit-panel-program {
        border-left-color: #7c4d9b;
        background: #fcf9ff;
    }

    .visit-panel-clinical {
        border-left-color: #16806c;
        background: #f7fcfa;
    }

    .visit-uti-panel {
        border-left-color: #b45309;
        background: #fffbf5;
    }

    .visit-panel-tables {
        border-left-color: #5368a6;
        background: #f8f9ff;
    }

    .visit-panel-wide {
        grid-column: 1 / -1;
    }

    .visit-panel-head {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .45rem;
    }

    .visit-panel-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e8f4fb;
        color: #2f78a8;
        flex: 0 0 28px;
        font-size: .78rem;
    }

    .visit-panel-program .visit-panel-icon {
        background: #f0e7f7;
        color: #713b8a;
    }

    .visit-panel-clinical .visit-panel-icon {
        background: #dcf3ec;
        color: #14715f;
    }

    .visit-uti-panel .visit-panel-icon {
        background: #fff0d9;
        color: #a34d08;
    }

    .visit-panel-tables .visit-panel-icon {
        background: #e8ebfa;
        color: #4e609b;
    }

    .visit-panel h6 {
        margin: 0;
        color: #1f2937;
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .visit-panel-head span:not(.visit-panel-icon) {
        color: #7b8794;
        font-size: .68rem;
        font-weight: 600;
    }

    .visit-text-box {
        border: 1px solid #d2dee8;
        background: #fff;
        color: #465465;
        border-radius: 8px;
        padding: .65rem .75rem;
        font-size: .7rem;
        line-height: 1.32;
        white-space: normal;
        max-height: 220px;
        overflow: auto;
    }

    .visit-text-box-compact {
        min-height: 58px;
        max-height: 150px;
    }

    .visit-empty,
    .visit-empty-line,
    .visit-empty-box {
        color: #8a94a3;
        font-size: .7rem;
        font-weight: 500;
    }

    .visit-empty-box,
    .visit-empty-line {
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 8px;
        padding: .6rem .7rem;
    }

    .visit-info-list {
        display: grid;
        gap: 5px;
        margin: 0;
    }

    .visit-info-list div {
        display: grid;
        grid-template-columns: 82px minmax(0, 1fr);
        gap: 8px;
        align-items: baseline;
    }

    .visit-info-list dt {
        color: #28678f;
        font-size: .7rem;
        font-weight: 700;
        background: #e7f2f9;
        border-radius: 5px;
        padding: .18rem .38rem;
    }

    .visit-info-list dd {
        margin: 0;
        color: #1f2937;
        font-size: .72rem;
        font-weight: 600;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .visit-chip-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: .45rem;
    }

    .visit-chip {
        border: 1px solid #cfe3dc;
        background: #fff;
        border-radius: 8px;
        padding: .52rem .62rem;
        display: grid;
        gap: 3px;
    }

    .visit-chip span,
    .visit-mini-card span {
        color: #28678f;
        font-size: .66rem;
        font-weight: 700;
    }

    .visit-chip span,
    .visit-uti-card > div span {
        justify-self: start;
        background: #e7f2f9;
        border-radius: 5px;
        padding: .18rem .4rem;
    }

    .visit-chip span {
        background: #e1f2ec;
        color: #126b59;
    }

    .visit-uti-card > div span {
        background: #fff0d9;
        color: #914208;
    }

    .visit-chip strong,
    .visit-mini-card strong {
        color: #273444;
        font-size: .72rem;
        font-weight: 700;
    }

    .visit-mini-card {
        border: 1px solid #ead7bc;
        background: #fff;
        border-radius: 8px;
        padding: .62rem;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .45rem;
    }

    .visit-uti-panel {
        width: 100%;
    }

    .visit-uti-card {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        column-gap: 1rem;
        row-gap: .65rem;
    }

    .visit-uti-card > div {
        display: grid;
        align-content: start;
        gap: .22rem;
        min-width: 0;
    }

    .visit-uti-card > div span,
    .visit-uti-card > div strong {
        display: block;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .visit-mini-card p {
        grid-column: 1 / -1;
        margin: 2px 0 0;
        color: #4b5563;
        font-size: .7rem;
        line-height: 1.32;
    }

    .visit-table-group {
        display: grid;
        gap: .7rem;
    }

    .visit-table-block {
        border: 1px solid #d6daed;
        border-radius: 8px;
        padding: .62rem;
        background: #fff;
    }

    .visit-footer-actions {
        align-items: center;
    }

    .visit-action-btn {
        min-height: 34px;
        padding: .42rem .78rem !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: .42rem;
        font-size: .72rem !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        box-shadow: 0 4px 10px rgba(30, 55, 75, .1);
    }

    .visit-action-edit {
        color: #fff !important;
        background: #2f78a8 !important;
        border: 1px solid #2f78a8 !important;
    }

    .visit-action-edit:hover {
        background: #25658f !important;
        border-color: #25658f !important;
    }

    .visit-action-back {
        color: #5e2363 !important;
        background: #fff !important;
        border: 1px solid #cdbbd0 !important;
    }

    .visit-action-back:hover {
        background: #f7eff8 !important;
        border-color: #9e6fa3 !important;
    }

    .visit-subtitle {
        display: flex;
        align-items: center;
        gap: .35rem;
        color: #2f78a8;
        font-size: .74rem;
        font-weight: 800;
        margin-bottom: .42rem;
    }

    .visit-subtitle span {
        background: #e8f4fb;
        color: #2f78a8;
        border-radius: 999px;
        min-width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .62rem;
    }

    .visit-mini-table {
        margin: 0;
        font-size: .7rem;
        color: #374151;
    }

    .visit-mini-table thead th {
        background: #edf7fc;
        color: #2f6f98;
        border-bottom: 1px solid #c8ddeb;
        font-family: inherit;
        font-size: .66rem !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        text-transform: uppercase !important;
        letter-spacing: .025em !important;
        height: 18px !important;
        min-height: 18px !important;
        padding: 1px 6px !important;
        white-space: nowrap;
    }

    .visit-mini-table thead,
    .visit-mini-table thead tr {
        height: 18px !important;
    }

    .visit-mini-table tbody td {
        padding: 6px 8px;
        border-color: #e5edf5;
        vertical-align: middle;
    }

    @media (max-width: 991.98px) {
        .visit-summary-grid {
            grid-template-columns: 1fr;
        }

        .visit-uti-card {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .visit-uti-card {
            grid-template-columns: 1fr;
        }
    }

    /* Timeline centralizada e SEM corte nas bordas */
    #main-container.visita-show-page #tab-timeline {
        background: #edf2f6;
        border: 1px solid #d9e3eb;
        border-radius: 12px;
        padding: .65rem;
    }

    .timeline-toolbar {
        min-height: 32px;
        padding: .38rem .55rem;
        margin-bottom: .45rem;
        border: 1px solid #d3e0e9;
        border-radius: 8px;
        background: #fff;
    }

    .timeline-toolbar .small,
    .timeline-caption .small {
        font-size: .68rem !important;
    }

    .timeline-pager .btn {
        min-height: 28px !important;
        padding: .28rem .52rem !important;
        font-size: .66rem !important;
        line-height: 1 !important;
    }

    .timeline-toolbar .btn-outline-danger,
    .timeline-details #tl-delete-btn {
        min-height: 24px !important;
        height: 24px !important;
        padding: .2rem .42rem !important;
        border-radius: 6px !important;
        font-size: .62rem !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: .22rem !important;
        box-shadow: none !important;
    }

    .timeline-toolbar .btn-outline-danger i,
    .timeline-details #tl-delete-btn i {
        margin-right: 0 !important;
        font-size: .66rem !important;
    }

    .ht-container {
        position: relative;
        overflow-x: auto;
        padding: 10px var(--padX) 2px;
        display: flex;
        justify-content: center;
        scroll-snap-type: x mandatory;
        border: 1px solid #d3e0e9;
        border-radius: 8px;
        background: #fff;
    }

    .ht-track {
        position: relative;
        height: 64px;
        margin: 0 auto;
        max-width: 100%;
    }

    .ht-bar {
        position: absolute;
        left: var(--padX);
        right: var(--padX);
        top: 39px;
        height: 4px;
        background: #d9c4e5;
        border-radius: 999px;
        box-shadow: inset 0 0 0 1px #e5d8ef
    }

    .ht-marker {
        position: absolute;
        top: 0;
        transform: translateX(-50%);
        text-align: center;
        cursor: pointer;
        color: inherit;
        text-decoration: none;
        scroll-snap-align: center;
        max-width: 45%;
    }

    .ht-marker.edge-left {
        transform: none;
    }

    .ht-marker.edge-right {
        transform: translateX(-100%);
    }

    /* Label da data */
    .ht-label {
        display: inline-block;
        font-size: .68rem;
        color: var(--brand);
        margin-bottom: 3px;
        white-space: nowrap;
        transition: all .2s ease;
        padding: 3px 7px;
        border-radius: 6px;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Hover e ativo */
    .ht-marker:hover .ht-label {
        background: var(--brand-100);
        color: var(--brand-800)
    }

    .ht-marker.active .ht-label {
        background: var(--brand);
        color: #fff;
        font-weight: 700;
        transform: scale(1.02)
    }

    .ht-dot {
        display: block;
        position: absolute;
        top: 34px;
        left: 50%;
        transform: translateX(-50%);
        width: 11px;
        height: 11px;
        border-radius: 50%;
        background: var(--brand);
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px var(--brand-100), 0 3px 7px rgba(0, 0, 0, .08);
        transition: all .2s ease
    }

    .ht-marker:hover .ht-dot {
        transform: translateX(-50%) scale(1.1)
    }

    .ht-marker.active .ht-dot {
        background: var(--brand-800);
        box-shadow: 0 0 0 4px var(--brand-100), 0 6px 14px rgba(0, 0, 0, .12)
    }

    .ht-pop {
        display: none;
        position: absolute;
        bottom: 54px;
        left: 50%;
        transform: translateX(-50%);
        background: #fff;
        border: 1px solid #eadcf3;
        border-radius: 8px;
        padding: 6px 8px;
        min-width: 180px;
        box-shadow: 0 12px 24px rgba(0, 0, 0, .08);
        z-index: 5
    }

    .ht-marker:hover .ht-pop {
        display: block
    }

    .legend-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--brand);
        margin-right: 6px
    }

    .timeline-details {
        margin-top: .45rem;
        margin-bottom: 0 !important;
        border: 1px solid #cbd9e3 !important;
        border-left: 5px solid #6b2a72 !important;
        border-radius: 9px !important;
        background: #fff !important;
        box-shadow: 0 6px 15px rgba(35, 57, 76, .1) !important;
    }

    .timeline-details .card-body {
        padding: .65rem .8rem !important;
    }

    .timeline-details h6 {
        font-size: .76rem !important;
    }

    .timeline-details #tl-rel {
        border: 1px solid #d8e2ea;
        border-radius: 7px;
        background: #f9fbfc;
        padding: .52rem .65rem;
        font-size: .7rem;
        line-height: 1.3;
    }

    .timeline-caption {
        padding: .38rem .2rem 0;
        gap: .75rem;
    }

    /* Botão "ghost" brand */
    .btn-ghost-brand {
        color: var(--brand);
        background: var(--brand-050);
        border: 1px solid #eadcf3;
    }

    .btn-ghost-brand:hover {
        background: var(--brand-100);
        color: var(--brand-800);
    }

    /* Contraste final da área de resumo e ações. */
    #main-container.visita-show-page #tab-resumo {
        background: #edf2f6;
        border: 1px solid #d9e3eb;
        border-radius: 12px;
        padding: .55rem;
    }

    #main-container.visita-show-page #tab-resumo > .row.g-3 {
        --bs-gutter-y: .45rem;
        margin-bottom: .12rem !important;
    }

    #main-container.visita-show-page #tab-resumo > .row.g-3 .ov-card {
        margin-bottom: 0 !important;
    }

    #main-container.visita-show-page #tab-resumo > .row.g-3 > [class*="col-"] {
        height: auto !important;
    }

    #main-container.visita-show-page .visit-panel {
        background: #fff !important;
        border-top-color: #cbd9e3 !important;
        border-right-color: #cbd9e3 !important;
        border-bottom-color: #cbd9e3 !important;
        box-shadow: 0 7px 18px rgba(35, 57, 76, .13) !important;
    }

    #main-container.visita-show-page .visit-text-box,
    #main-container.visita-show-page .visit-chip,
    #main-container.visita-show-page .visit-mini-card,
    #main-container.visita-show-page .visit-table-block {
        background: #f9fbfc !important;
    }

    #main-container.visita-show-page .visit-page-footer {
        background: #f5f7fa;
        border: 1px solid #d5e0e8;
        border-radius: 10px;
        padding: .55rem .65rem;
        margin-bottom: 4.25rem;
    }

    #main-container.visita-show-page a.visit-action-btn {
        min-height: 36px !important;
        padding: .5rem .9rem !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: .42rem !important;
        text-decoration: none !important;
        opacity: 1 !important;
        box-shadow: 0 4px 10px rgba(31, 52, 70, .16) !important;
    }

    #main-container.visita-show-page a.visit-action-edit {
        background-color: #2f78a8 !important;
        background-image: none !important;
        border: 1px solid #24658f !important;
        color: #fff !important;
    }

    #main-container.visita-show-page a.visit-action-back {
        background-color: #fff !important;
        background-image: none !important;
        border: 1px solid #8e5a94 !important;
        color: #5e2363 !important;
    }
</style>

<script>
    (function ensureBootstrap() {
        if (typeof window.bootstrap === 'undefined') {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
            s.defer = true;
            document.body.appendChild(s);
        }
    })();

    // total para detalhes
    window.TOTAL_VISITAS = <?= (int)$TOTAL_VISITAS_EXIBIR ?>;
    window.TOTAL_VISITAS_INTERNACAO = <?= (int)$total_intern ?>;

    function updateTimelineDetailsFromMarker(marker) {
        if (!marker) return;
        var box = document.getElementById('tl-details');
        var idEl = document.getElementById('tl-id');
        var date = document.getElementById('tl-date');
        var user = document.getElementById('tl-user');
        var hosp = document.getElementById('tl-hosp');
        var rel = document.getElementById('tl-rel');
        var sep1 = document.getElementById('tl-sep1');
        var sep2 = document.getElementById('tl-sep2');
        var sep3 = document.getElementById('tl-sep3');
        var totW = document.getElementById('tl-total-wrap');
        var tot = document.getElementById('tl-total');

        if (idEl) idEl.textContent = '#' + (marker.dataset.id || '');
        if (date) date.textContent = marker.dataset.date || '—';
        if (user) user.textContent = marker.dataset.user || '';
        if (hosp) hosp.textContent = marker.dataset.hosp || '';
        if (rel) rel.innerHTML = marker.dataset.rel || '';
        var tlDeleteBtn = document.getElementById('tl-delete-btn');
        if (tlDeleteBtn) {
            if (marker.dataset.id) {
                tlDeleteBtn.setAttribute('data-delete-visita', marker.dataset.id);
            }
            var isRetificado = marker.dataset.retificado === '1';
            var totalIntern = Number(window.TOTAL_VISITAS_INTERNACAO || 0);
            var preventDelete = isRetificado || totalIntern <= 1;
            tlDeleteBtn.classList.toggle('d-none', preventDelete);
            tlDeleteBtn.disabled = preventDelete;
        }

        if (sep1) sep1.style.display = (user && user.textContent) ? '' : 'none';
        if (sep2) sep2.style.display = (hosp && hosp.textContent) ? '' : 'none';

        if (tot) tot.textContent = String(window.TOTAL_VISITAS || '');
        if (totW) totW.style.display = (window.TOTAL_VISITAS && Number(window.TOTAL_VISITAS) > 0) ? '' : 'none';
        if (sep3) sep3.style.display = (totW && totW.style.display !== 'none') ? '' : 'none';

        if (box) box.style.display = 'block';

        document.querySelectorAll('#tab-timeline .ht-marker').forEach(function(a) {
            a.classList.remove('active');
        });
        marker.classList.add('active');

        var container = document.querySelector('#tab-timeline .ht-container');
        if (container) {
            container.scrollLeft = Math.max(0, marker.offsetLeft - (container.clientWidth / 2));
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var timeline = document.querySelector('#tab-timeline');
        if (!timeline) return;

        timeline.addEventListener('click', function(ev) {
            var a = ev.target.closest('.ht-marker');
            if (!a) return;
            ev.preventDefault();
            updateTimelineDetailsFromMarker(a);
        });

        // Seleção inicial: ativo ou ÚLTIMO (mais recente)
        var active = timeline.querySelector('.ht-marker.active');
        var markers = timeline.querySelectorAll('.ht-marker');
        var last = markers.length ? markers[markers.length - 1] : null;

        updateTimelineDetailsFromMarker(active || last);

        // Rola tudo para a direita para garantir a última etiqueta visível
        var container = document.querySelector('#tab-timeline .ht-container');
        if (container) {
            container.scrollLeft = container.scrollWidth;
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        var modalDelete = document.getElementById('modalDeleteVisita');
        if (!modalDelete) return;
        var confirmBtn = modalDelete.querySelector('[data-action="confirm-delete"]');
        var feedback = modalDelete.querySelector('.js-delete-feedback');
        var redirectUrl = <?= json_encode(rtrim($BASE_URL, '/') . '/internacoes/visualizar/' . (int)$id_internacao . '#tab-visitas') ?>;
        var defaultVisitaId = <?= (int)$id_visita_row ?>;
        var selectedVisitaId = defaultVisitaId;

        if (!confirmBtn) return;

        modalDelete.addEventListener('show.bs.modal', function(event) {
            var trigger = event.relatedTarget;
            var btnId = trigger ? parseInt(trigger.getAttribute('data-delete-visita'), 10) : NaN;
            selectedVisitaId = Number.isFinite(btnId) && btnId > 0 ? btnId : defaultVisitaId;
        });

        confirmBtn.addEventListener('click', function() {
            confirmBtn.disabled = true;
            if (feedback) {
                feedback.classList.add('d-none');
                feedback.textContent = '';
            }

            var formData = new FormData();
            formData.append('type', 'delete');
            formData.append('id_visita', selectedVisitaId);
            formData.append('redirect', redirectUrl);
            formData.append('ajax', '1');
            formData.append('csrf', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>');

            fetch('process_visita.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function(resp) { return resp.json(); })
                .then(function(res) {
                    if (res && res.success) {
                        window.location.href = res.redirect || redirectUrl;
                        return;
                    }
                    var msg = (res && res.message) ? res.message : 'Não foi possível remover a visita.';
                    if (feedback) {
                        feedback.textContent = msg;
                        feedback.classList.remove('d-none');
                    } else {
                        alert(msg);
                    }
                })
                .catch(function() {
                    if (feedback) {
                        feedback.textContent = 'Falha inesperada ao remover a visita.';
                        feedback.classList.remove('d-none');
                    } else {
                        alert('Falha inesperada ao remover a visita.');
                    }
                })
                .finally(function() {
                    confirmBtn.disabled = false;
                });
        });
    });
</script>

<?php include_once("templates/footer.php"); ?>
