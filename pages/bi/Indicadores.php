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

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$normCargoAccess = static function ($txt): string {
    $txt = mb_strtolower(trim((string)$txt), 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
    $txt = $ascii !== false ? $ascii : $txt;
    return preg_replace('/[^a-z]/', '', $txt);
};
$isSeguradoraRole = (strpos($normCargoAccess($_SESSION['cargo'] ?? ''), 'seguradora') !== false);
$seguradoraUserId = (int)($_SESSION['fk_seguradora_user'] ?? 0);
if ($isSeguradoraRole && $seguradoraUserId <= 0) {
    try {
        $uid = (int)($_SESSION['id_usuario'] ?? 0);
        if ($uid > 0) {
            $stmtSeg = $conn->prepare("SELECT fk_seguradora_user FROM tb_user WHERE id_usuario = :id LIMIT 1");
            $stmtSeg->bindValue(':id', $uid, PDO::PARAM_INT);
            $stmtSeg->execute();
            $seguradoraUserId = (int)($stmtSeg->fetchColumn() ?: 0);
            if ($seguradoraUserId > 0) {
                $_SESSION['fk_seguradora_user'] = $seguradoraUserId;
            }
        }
    } catch (Throwable $e) {
        error_log('[INDICADORES][SEGURADORA] ' . $e->getMessage());
    }
}

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
if ($isSeguradoraRole) {
    $where .= " AND pa.fk_seguradora_pac = :seguradora_id";
    $params[':seguradora_id'] = $seguradoraUserId > 0 ? $seguradoraUserId : -1;
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
    LEFT JOIN tb_paciente pa ON pa.id_paciente = i.fk_paciente_int
    {$utiJoin}
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
        SUM(CASE WHEN i.internado_int = 's' THEN 1 ELSE 0 END) AS internados
    {$sqlBase}
";
$stmt = $conn->prepare($sqlStats);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$totalInternacoes = (int)($stats['total_internacoes'] ?? 0);
$totalDiarias = (int)($stats['total_diarias'] ?? 0);
$maiorPermanencia = (int)($stats['maior_permanencia'] ?? 0);
$internados = (int)($stats['internados'] ?? 0);
$mp = $totalInternacoes > 0 ? round($totalDiarias / $totalInternacoes, 1) : 0.0;

$sqlFlags = "
    SELECT
        SUM(CASE WHEN g.evento_adverso_ges = 's' THEN 1 ELSE 0 END) AS evento_adverso,
        SUM(CASE WHEN g.home_care_ges = 's' THEN 1 ELSE 0 END) AS home_care,
        SUM(CASE WHEN g.opme_ges = 's' THEN 1 ELSE 0 END) AS opme,
        SUM(CASE WHEN g.alto_custo_ges = 's' THEN 1 ELSE 0 END) AS alto_custo
    FROM tb_gestao g
    JOIN tb_internacao i ON i.id_internacao = g.fk_internacao_ges
    LEFT JOIN tb_paciente pa ON pa.id_paciente = i.fk_paciente_int
    {$utiJoin}
    WHERE {$where}
";
$stmtFlags = $conn->prepare($sqlFlags);
$stmtFlags->execute($params);
$flags = $stmtFlags->fetch(PDO::FETCH_ASSOC) ?: [];

$eventoAdverso = (int)($flags['evento_adverso'] ?? 0);
$homeCare = (int)($flags['home_care'] ?? 0);
$opme = (int)($flags['opme'] ?? 0);
$altoCusto = (int)($flags['alto_custo'] ?? 0);
$obitos = 0;

function fmtPct(float $value): string
{
    return number_format($value, 1, ',', '.') . '%';
}

$idxEventoAdverso = $totalInternacoes > 0 ? ($eventoAdverso / $totalInternacoes) * 100 : 0.0;
$idxHomeCare = $totalInternacoes > 0 ? ($homeCare / $totalInternacoes) * 100 : 0.0;
$idxOpme = $totalInternacoes > 0 ? ($opme / $totalInternacoes) * 100 : 0.0;
$idxAltoCusto = $totalInternacoes > 0 ? ($altoCusto / $totalInternacoes) * 100 : 0.0;
$idxObitos = $totalInternacoes > 0 ? ($obitos / $totalInternacoes) * 100 : 0.0;
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260628-select-arrow">
<script src="diversos/chartjs/Chart.min.js"></script>
<script src="<?= $BASE_URL ?>js/bi.js?v=20260614-select-neutral"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>
<style>
    .bi-indicadores-page .bi-kpis.kpi-auditor-v2 {
        gap: 12px;
    }

    .bi-indicadores-page .bi-kpi.kpi-card-v2 {
        min-height: 126px;
        border-width: 1px;
        box-shadow: 0 8px 18px rgba(36, 53, 92, 0.14);
    }

    .bi-indicadores-page .bi-kpi.kpi-card-v2 .kpi-card-v2-head small,
    .bi-indicadores-page .bi-kpi.kpi-card-v2 strong {
        color: #ffffff;
    }

    .bi-indicadores-page .bi-kpi.kpi-card-v2 .kpi-card-v2-icon {
        background: rgba(255, 255, 255, 0.16);
        border-color: rgba(255, 255, 255, 0.3);
        color: #d7f2ff;
    }

    .bi-indicadores-page .bi-kpi.kpi-card-v2 .kpi-trend {
        color: rgba(240, 247, 255, 0.92);
    }

    .bi-indicadores-page .bi-kpi.kpi-card-v2.kpi-card-v2-1 {
        background: linear-gradient(135deg, #1f4f8f 0%, #2e77bd 100%);
        border-color: #5e9fdd;
    }

    .bi-indicadores-page .bi-kpi.kpi-card-v2.kpi-card-v2-2 {
        background: linear-gradient(135deg, #4a2f9b 0%, #6d4ec2 100%);
        border-color: #8c74d8;
    }

    .bi-indicadores-page .bi-kpi.kpi-card-v2.kpi-card-v2-3 {
        background: linear-gradient(135deg, #1d6f83 0%, #2a9dad 100%);
        border-color: #61becb;
    }

    .bi-indicadores-page .bi-kpi.kpi-card-v2.kpi-card-v2-4 {
        background: linear-gradient(135deg, #7a2f7e 0%, #a1489b 100%);
        border-color: #c270bc;
    }

    .bi-indicadores-page .performance-kpis {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 14px;
        align-items: stretch;
        margin-top: 14px;
    }

    .bi-indicadores-page .performance-card {
        position: relative;
        grid-column: span 3;
        min-height: 118px;
        min-width: 0;
        padding: 14px 14px 12px;
        overflow: hidden;
        border-radius: 8px;
        background: rgba(13, 48, 77, 0.54);
        border: 1px solid rgba(169, 222, 255, 0.22);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 10px 22px rgba(5, 24, 44, 0.18);
    }

    .bi-indicadores-page .performance-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 4px;
        background: var(--performance-accent, #64d8ff);
    }

    .bi-indicadores-page .performance-card-main {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr) auto;
        gap: 10px;
        align-items: start;
    }

    .bi-indicadores-page .performance-card-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: var(--performance-accent, #64d8ff);
        font-size: 0.92rem;
    }

    .bi-indicadores-page .performance-card small {
        display: block;
        margin-bottom: 8px;
        color: rgba(229, 244, 255, 0.82);
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .bi-indicadores-page .performance-card strong {
        display: block;
        color: #ffffff;
        font-size: clamp(1.12rem, 1.55vw, 1.55rem);
        line-height: 1;
    }

    .bi-indicadores-page .performance-chip {
        justify-self: end;
        min-width: 58px;
        padding: 5px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.16);
        color: rgba(244, 250, 255, 0.94);
        font-size: 0.64rem;
        font-weight: 800;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
    }

    .bi-indicadores-page .performance-chip-muted {
        color: rgba(215, 232, 247, 0.9);
    }

    .bi-indicadores-page .performance-bar {
        height: 5px;
        margin-top: 18px;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.13);
    }

    .bi-indicadores-page .performance-bar span {
        display: block;
        width: var(--metric-pct, 0%);
        max-width: 100%;
        height: 100%;
        border-radius: inherit;
        background: var(--performance-accent, #64d8ff);
    }

    .bi-indicadores-page .performance-card-base .performance-bar span {
        width: 100%;
        opacity: 0.55;
    }

    .bi-indicadores-page .performance-card-blue { --performance-accent: #63c6ff; }
    .bi-indicadores-page .performance-card-violet { --performance-accent: #a78bfa; }
    .bi-indicadores-page .performance-card-teal { --performance-accent: #5eead4; }
    .bi-indicadores-page .performance-card-rose { --performance-accent: #fb7bc7; }
    .bi-indicadores-page .performance-card-cyan { --performance-accent: #67e8f9; }
    .bi-indicadores-page .performance-card-amber { --performance-accent: #fbbf24; }

    .bi-indicadores-page .performance-kpis .performance-card:nth-child(-n + 4) {
        grid-column: span 3;
    }

    .bi-indicadores-page .performance-kpis .performance-card:nth-child(n + 5) {
        grid-column: span 4;
    }

    @media (max-width: 1100px) {
        .bi-indicadores-page .bi-kpis.kpi-auditor-v2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bi-indicadores-page .performance-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bi-indicadores-page .performance-kpis .performance-card {
            grid-column: auto;
        }

        .bi-indicadores-page .performance-kpis .performance-card:last-child {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 640px) {
        .bi-indicadores-page .bi-kpis.kpi-auditor-v2 {
            grid-template-columns: 1fr;
        }

        .bi-indicadores-page .performance-kpis {
            grid-template-columns: 1fr;
        }

        .bi-indicadores-page .performance-kpis .performance-card:last-child {
            grid-column: auto;
        }

        .bi-indicadores-page .performance-card-main {
            grid-template-columns: 34px minmax(0, 1fr);
        }

        .bi-indicadores-page .performance-chip {
            grid-column: 2;
            justify-self: start;
            margin-top: 4px;
        }
    }
</style>

<div class="bi-wrapper bi-theme bi-auditor-page bi-indicadores-page">
    <div class="bi-header">
        <h1 class="bi-title">Dashboard Indicadores</h1>
        <div class="bi-header-actions">
            <div class="text-end text-muted"></div>
            <a class="bi-nav-icon" href="<?= $BASE_URL ?>bi/navegacao" title="Navegação">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        </div>
    </div>

    <form class="bi-panel bi-filters bi-filters-wrap" method="get">
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
        </div>
    </form>

    <div class="bi-panel" style="margin-top:16px;">
        <div class="bi-kpis kpi-auditor-v2">
            <div class="bi-kpi kpi-card-v2 kpi-card-v2-1">
                <div class="kpi-card-v2-head">
                    <span class="kpi-card-v2-icon"><i class="bi bi-hospital"></i></span>
                    <small>Internações</small>
                </div>
                <strong><?= number_format($totalInternacoes, 0, ',', '.') ?></strong>
                <div class="kpi-trend kpi-trend-neutral">
                    <i class="bi bi-dash-circle"></i>
                    <span>No período</span>
                </div>
            </div>
            <div class="bi-kpi kpi-card-v2 kpi-card-v2-2">
                <div class="kpi-card-v2-head">
                    <span class="kpi-card-v2-icon"><i class="bi bi-moon-stars"></i></span>
                    <small>Diárias</small>
                </div>
                <strong><?= number_format($totalDiarias, 0, ',', '.') ?></strong>
                <div class="kpi-trend kpi-trend-neutral">
                    <i class="bi bi-dash-circle"></i>
                    <span>No período</span>
                </div>
            </div>
            <div class="bi-kpi kpi-card-v2 kpi-card-v2-3">
                <div class="kpi-card-v2-head">
                    <span class="kpi-card-v2-icon"><i class="bi bi-activity"></i></span>
                    <small>MP</small>
                </div>
                <strong><?= number_format($mp, 1, ',', '.') ?></strong>
                <div class="kpi-trend kpi-trend-neutral">
                    <i class="bi bi-dash-circle"></i>
                    <span>No período</span>
                </div>
            </div>
            <div class="bi-kpi kpi-card-v2 kpi-card-v2-4">
                <div class="kpi-card-v2-head">
                    <span class="kpi-card-v2-icon"><i class="bi bi-stopwatch"></i></span>
                    <small>Maior permanência</small>
                </div>
                <strong><?= number_format($maiorPermanencia, 0, ',', '.') ?></strong>
                <div class="kpi-trend kpi-trend-neutral">
                    <i class="bi bi-dash-circle"></i>
                    <span>No período</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bi-panel" style="margin-top:16px;">
        <h3>Indicadores de performance</h3>
        <div class="performance-kpis">
            <div class="performance-card performance-card-base performance-card-blue">
                <div class="performance-card-main">
                    <span class="performance-card-icon"><i class="bi bi-hospital"></i></span>
                    <div>
                        <small>Internações</small>
                        <strong><?= number_format($totalInternacoes, 0, ',', '.') ?></strong>
                    </div>
                    <span class="performance-chip performance-chip-muted">Base</span>
                </div>
                <div class="performance-bar"><span></span></div>
            </div>
            <div class="performance-card performance-card-base performance-card-violet">
                <div class="performance-card-main">
                    <span class="performance-card-icon"><i class="bi bi-person-check"></i></span>
                    <div>
                        <small>Internados</small>
                        <strong><?= number_format($internados, 0, ',', '.') ?></strong>
                    </div>
                    <span class="performance-chip performance-chip-muted">Base</span>
                </div>
                <div class="performance-bar"><span></span></div>
            </div>
            <div class="performance-card performance-card-teal" style="--metric-pct: <?= min(100, max(0, round($idxEventoAdverso, 1))) ?>%;">
                <div class="performance-card-main">
                    <span class="performance-card-icon"><i class="bi bi-exclamation-triangle"></i></span>
                    <div>
                        <small>Evento adverso</small>
                        <strong><?= number_format($eventoAdverso, 0, ',', '.') ?></strong>
                    </div>
                    <span class="performance-chip"><?= fmtPct($idxEventoAdverso) ?></span>
                </div>
                <div class="performance-bar"><span></span></div>
            </div>
            <div class="performance-card performance-card-rose" style="--metric-pct: <?= min(100, max(0, round($idxHomeCare, 1))) ?>%;">
                <div class="performance-card-main">
                    <span class="performance-card-icon"><i class="bi bi-house-heart"></i></span>
                    <div>
                        <small>Home care</small>
                        <strong><?= number_format($homeCare, 0, ',', '.') ?></strong>
                    </div>
                    <span class="performance-chip"><?= fmtPct($idxHomeCare) ?></span>
                </div>
                <div class="performance-bar"><span></span></div>
            </div>
            <div class="performance-card performance-card-cyan" style="--metric-pct: <?= min(100, max(0, round($idxOpme, 1))) ?>%;">
                <div class="performance-card-main">
                    <span class="performance-card-icon"><i class="bi bi-gear"></i></span>
                    <div>
                        <small>OPME</small>
                        <strong><?= number_format($opme, 0, ',', '.') ?></strong>
                    </div>
                    <span class="performance-chip"><?= fmtPct($idxOpme) ?></span>
                </div>
                <div class="performance-bar"><span></span></div>
            </div>
            <div class="performance-card performance-card-amber" style="--metric-pct: <?= min(100, max(0, round($idxAltoCusto, 1))) ?>%;">
                <div class="performance-card-main">
                    <span class="performance-card-icon"><i class="bi bi-cash-stack"></i></span>
                    <div>
                        <small>Alto custo</small>
                        <strong><?= number_format($altoCusto, 0, ',', '.') ?></strong>
                    </div>
                    <span class="performance-chip"><?= fmtPct($idxAltoCusto) ?></span>
                </div>
                <div class="performance-bar"><span></span></div>
            </div>
            <div class="performance-card performance-card-rose" style="--metric-pct: <?= min(100, max(0, round($idxObitos, 1))) ?>%;">
                <div class="performance-card-main">
                    <span class="performance-card-icon"><i class="bi bi-heartbreak"></i></span>
                    <div>
                        <small>Óbitos</small>
                        <strong><?= number_format($obitos, 0, ',', '.') ?></strong>
                    </div>
                    <span class="performance-chip"><?= fmtPct($idxObitos) ?></span>
                </div>
                <div class="performance-bar"><span></span></div>
            </div>
        </div>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
