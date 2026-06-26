<?php
include_once("check_logado.php");
require_once("templates/header.php");
require_once(__DIR__ . "/app/cuidadoContinuado.php");

ensure_cuidado_continuado_schema($conn);

$feedback = null;
$feedbackType = 'success';
$userId = (int)($_SESSION['id_usuario'] ?? 0);
$responsavelNome = trim((string)($_SESSION['usuario_nome'] ?? ($_SESSION['nome_usuario'] ?? ($_SESSION['email_user'] ?? ('Usuário #' . $userId)))));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim((string)($_POST['cc_action'] ?? ''));
    $notes = trim((string)($_POST['observacoes'] ?? ''));
    if ($action === 'admitir_preventiva') {
        $ok = cc_admit_preventiva_from_cronico($conn, (int)($_POST['cronico_id'] ?? 0), $userId, $notes);
        $feedback = $ok ? 'Paciente admitido em Medicina Preventiva.' : 'Não foi possível admitir o paciente em Medicina Preventiva.';
        $feedbackType = $ok ? 'success' : 'danger';
    } elseif ($action === 'registrar_monitoramento') {
        $ok = cc_register_preventiva_followup(
            $conn,
            (int)($_POST['preventivo_id'] ?? 0),
            trim((string)($_POST['proximo_contato'] ?? '')) ?: null,
            $notes,
            $responsavelNome,
            trim((string)($_POST['tipo_acao'] ?? 'monitoramento_telefonico'))
        );
        $feedback = $ok ? 'Monitoramento telefônico registrado.' : 'Não foi possível registrar o monitoramento.';
        $feedbackType = $ok ? 'success' : 'danger';
    }
}

$search = trim((string)filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW));
$summary = cc_fetch_preventiva_summary($conn);
$elegiveis = cc_fetch_preventiva_elegiveis($conn);
$monitorados = cc_fetch_preventiva_active($conn, $search);
$actions = cc_fetch_program_actions($conn, 'preventiva', 12);

function mp_fmt_date(?string $date): string
{
    if (!$date || $date === '0000-00-00') {
        return '-';
    }
    $dt = DateTime::createFromFormat('Y-m-d', substr((string)$date, 0, 10));
    return $dt ? $dt->format('d/m/Y') : (string)$date;
}

function mp_fmt_datetime(?string $date): string
{
    if (!$date) {
        return '-';
    }
    try {
        return (new DateTime($date))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return (string)$date;
    }
}

function mp_badge_class(string $risk): string
{
    if ($risk === 'alto') {
        return 'danger';
    }
    if ($risk === 'moderado') {
        return 'warning';
    }
    return 'secondary';
}

function mp_action_label(string $action): string
{
    $map = [
        'admissao' => 'Admissão',
        'monitoramento_telefonico' => 'Monitoramento telefônico',
        'orientacao' => 'Orientação',
        'encerramento' => 'Encerramento',
    ];
    return $map[$action] ?? ucfirst(str_replace('_', ' ', $action));
}
?>
<script src="js/timeout.js"></script>
<style>
    .mp-shell {
        padding: 12px 14px 18px;
        background: #f6f7fb;
        min-height: 100vh;
    }
    .mp-shell .fc-module-header {
        margin-bottom: 8px !important;
        padding: 10px 12px !important;
        border-radius: 8px !important;
        border: 1px solid rgba(47, 111, 159, .16) !important;
        background: linear-gradient(135deg, #2f6f9f 0%, #4daed2 100%) !important;
        box-shadow: 0 10px 24px -20px rgba(47, 111, 159, .55) !important;
    }
    .mp-shell .mb-4 {
        margin-bottom: 8px !important;
    }
    .mp-shell .mb-3 {
        margin-bottom: 6px !important;
    }
    .mp-shell .row.g-3 {
        --bs-gutter-x: 10px;
        --bs-gutter-y: 4px;
        margin-top: 0 !important;
        margin-bottom: 4px !important;
    }
    .mp-shell .mp-summary-row {
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    .mp-shell .mp-summary-row + .card {
        margin-top: 0 !important;
    }
    .mp-shell .mp-filter-card {
        margin-top: 0 !important;
        margin-bottom: 8px !important;
        transform: none;
    }
    .mp-top-stack {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 8px;
    }
    .mp-summary-row {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px !important;
        width: 100%;
    }
    .mp-summary-row > .mp-summary-col {
        width: auto !important;
        max-width: none !important;
        min-width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .mp-shell .row.g-2 {
        --bs-gutter-x: 6px;
        --bs-gutter-y: 6px;
    }
    .mp-hero {
        background: linear-gradient(135deg, #2f6f9f 0%, #4daed2 100%);
        color: #fff;
        border-radius: 8px;
        padding: 10px 12px;
    }
    .mp-hero h1,
    .mp-hero h2,
    .mp-hero p,
    .mp-hero div {
        color: #fff !important;
    }
    .mp-hero .small {
        font-size: .62rem !important;
    }
    .mp-hero h1 {
        font-size: 1rem !important;
        margin-top: .35rem !important;
        margin-bottom: .35rem !important;
    }
    .mp-hero p {
        font-size: .74rem;
        line-height: 1.4;
    }
    .mp-hero .btn {
        min-height: 32px;
        padding: 6px 12px;
        font-size: .72rem;
    }
    .mp-mini-note {
        font-size: .68rem;
        color: #6b7280;
    }
    .mp-shell .card {
        border: 1px solid #e1e7ef !important;
        border-radius: 8px;
        box-shadow: 0 8px 20px -18px rgba(15, 23, 42, .35) !important;
    }
    .mp-shell .card-body {
        padding: 8px 10px;
    }
    .mp-shell > .container-fluid > .card {
        margin-top: 0 !important;
    }
    .mp-shell .text-muted.small,
    .mp-shell .small {
        font-size: .68rem !important;
    }
    .mp-shell .fs-3 {
        font-size: 1.18rem !important;
        line-height: 1.1;
    }
    .mp-shell .row.g-3 > [class*="col-"] .card-body {
        min-height: 34px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .mp-shell .mp-summary-row .card-body {
        min-height: 32px !important;
        padding: 4px 8px !important;
    }
    .mp-shell .mp-summary-row .fs-3 {
        font-size: .98rem !important;
        line-height: 1 !important;
    }
    .mp-shell .fc-module-header__title {
        font-size: .98rem !important;
        line-height: 1.12;
        margin-bottom: 2px;
    }
    .mp-shell .fc-module-header__kicker {
        font-size: .58rem !important;
        letter-spacing: .12em !important;
        margin-bottom: 2px !important;
    }
    .mp-shell .fc-module-header__subtitle {
        font-size: .68rem !important;
        line-height: 1.25;
        margin-bottom: 0;
    }
    .mp-shell .fc-module-header__actions {
        display: flex;
        gap: 6px;
        align-items: center;
        justify-content: flex-end;
    }
    .mp-shell .form-label {
        font-size: .68rem;
        margin-bottom: 4px;
    }
    .mp-shell .form-control,
    .mp-shell .form-select,
    .mp-shell .btn {
        min-height: 32px;
        height: 32px;
        font-size: .72rem;
        line-height: 1.2;
    }
    .mp-shell .form-control,
    .mp-shell .form-select {
        border: 1px solid #cbd5e1 !important;
        border-radius: 7px !important;
        background-color: #fff !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .08) !important;
        color: #1f2937 !important;
        font-weight: 400 !important;
    }
    .mp-shell .form-control:focus,
    .mp-shell .form-select:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 .14rem rgba(59, 130, 246, .16), 0 1px 2px rgba(15, 23, 42, .08) !important;
    }
    .mp-shell .form-control::placeholder {
        font-size: .72rem;
        color: #c4c4c4;
    }
    .mp-shell .btn.btn-sm {
        min-height: 30px;
        font-size: .68rem;
        padding: 5px 10px;
    }
    .mp-shell .btn-success {
        border-color: #2f6f9f !important;
        background: #2f6f9f !important;
        color: #fff !important;
    }
    .mp-shell .btn-success:hover {
        border-color: #255b85 !important;
        background: #255b85 !important;
    }
    .mp-shell .table thead th {
        font-size: .56rem;
        letter-spacing: .08em;
        padding: 7px 8px;
        text-transform: uppercase;
    }
    .mp-shell .table tbody td {
        font-size: .72rem;
        padding: 6px 8px;
        vertical-align: middle;
    }
    .mp-shell h2.h5 {
        color: #241437;
        font-size: .9rem;
        font-weight: 800;
        line-height: 1.1;
    }
    .mp-shell .alert {
        padding: .55rem .7rem;
        border-radius: 8px;
        font-size: .72rem;
        line-height: 1.25;
    }
</style>

<div class="mp-shell">
    <div class="container-fluid">
        <div class="fc-module-header fc-module-header--cuidado mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div class="fc-module-header__copy">
                    <p class="fc-module-header__kicker">Cuidado Continuado</p>
                    <h1 class="fc-module-header__title">Medicina Preventiva</h1>
                    <p class="fc-module-header__subtitle">
                        A Medicina Preventiva funciona como um monitoramento telefônico estruturado. Os pacientes elegíveis são admitidos no programa e passam a ter rotina de contato, orientação e acompanhamento.
                    </p>
                </div>
                <div class="fc-module-header__actions">
                    <a class="btn btn-light" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado') ?>">Dashboard</a>
                    <a class="btn btn-outline-light" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/cronicos') ?>">Gestão de Crônicos</a>
                </div>
            </div>
        </div>

        <?php if ($feedback): ?>
            <div class="alert alert-<?= htmlspecialchars($feedbackType) ?>"><?= htmlspecialchars($feedback) ?></div>
        <?php endif; ?>

        <div class="mp-top-stack">
            <div class="mp-summary-row">
                <div class="mp-summary-col"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Em monitoramento</div><div class="fs-3 fw-bold"><?= (int)$summary['ativos'] ?></div></div></div></div>
                <div class="mp-summary-col"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Elegíveis para admissão</div><div class="fs-3 fw-bold text-primary"><?= (int)$summary['elegiveis'] ?></div></div></div></div>
                <div class="mp-summary-col"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Pendentes de contato</div><div class="fs-3 fw-bold text-warning"><?= (int)$summary['pendentes'] ?></div></div></div></div>
                <div class="mp-summary-col"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Alto risco</div><div class="fs-3 fw-bold text-danger"><?= (int)$summary['alto_risco'] ?></div></div></div></div>
            </div>

            <div class="card border-0 shadow-sm mp-filter-card">
                <div class="card-body">
                    <form class="row g-3 align-items-end" method="get">
                        <div class="col-12 col-lg-10">
                            <label class="form-label">Pesquisar paciente, matrícula ou foco do monitoramento</label>
                            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Ex.: João, diabetes, matrícula">
                        </div>
                        <div class="col-12 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-success">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div>
                        <h2 class="h5 mb-1">Elegíveis para entrar no monitoramento</h2>
                        <div class="text-muted small">Pacientes já admitidos em crônicos e ainda não acompanhados pela preventiva.</div>
                    </div>
                </div>
                <?php if (!$elegiveis): ?>
                    <div class="alert alert-light border mb-0">
                        Não há elegíveis pendentes para Medicina Preventiva.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Condição de origem</th>
                                    <th>Risco</th>
                                    <th>Próximo contato do crônico</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($elegiveis as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars((string)$row['nome_pac']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars((string)($row['matricula_pac'] ?: 'Sem matrícula')) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars((string)$row['condicao']) ?></td>
                                        <td><span class="badge bg-<?= mp_badge_class((string)$row['nivel_risco']) ?>"><?= htmlspecialchars((string)$row['nivel_risco']) ?></span></td>
                                        <td><?= htmlspecialchars(mp_fmt_date($row['proximo_contato_cronico'] ?? null)) ?></td>
                                        <td class="text-end" style="min-width:280px;">
                                            <form method="post">
                                                <input type="hidden" name="cc_action" value="admitir_preventiva">
                                                <input type="hidden" name="cronico_id" value="<?= (int)$row['id_cronico'] ?>">
                                                <input type="text" name="observacoes" class="form-control form-control-sm mb-2" placeholder="Observação da admissão">
                                                <button type="submit" class="btn btn-sm btn-success w-100">Admitir em Medicina Preventiva</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div>
                        <h2 class="h5 mb-1">Pacientes em monitoramento telefônico</h2>
                        <div class="text-muted small">Ações da preventiva ficam registradas com data, próximo contato e observação.</div>
                    </div>
                </div>
                <?php if (!$monitorados): ?>
                    <div class="alert alert-light border mb-0">
                        Nenhum paciente ativo em Medicina Preventiva.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Foco</th>
                                    <th>Status</th>
                                    <th>Última ação</th>
                                    <th>Próximo contato</th>
                                    <th class="text-end">Registrar monitoramento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monitorados as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars((string)$row['nome_pac']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars((string)($row['matricula_pac'] ?: 'Sem matrícula')) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars((string)$row['foco_monitoramento']) ?></div>
                                            <span class="badge bg-<?= mp_badge_class((string)$row['nivel_risco']) ?>"><?= htmlspecialchars((string)$row['nivel_risco']) ?></span>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars((string)$row['status_monitoramento']) ?></div>
                                            <div class="small text-muted">Último contato: <?= htmlspecialchars(mp_fmt_date($row['ultima_interacao'] ?? null)) ?></div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars(mp_action_label((string)($row['ultima_acao'] ?? ''))) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars(mp_fmt_datetime($row['ultima_acao_em'] ?? null)) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars(mp_fmt_date($row['proximo_contato'] ?? null)) ?></td>
                                        <td class="text-end" style="min-width:330px;">
                                            <form method="post">
                                                <input type="hidden" name="cc_action" value="registrar_monitoramento">
                                                <input type="hidden" name="preventivo_id" value="<?= (int)$row['id_preventivo'] ?>">
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <select name="tipo_acao" class="form-select form-select-sm" required>
                                                            <option value="monitoramento_telefonico">Monitoramento telefônico</option>
                                                            <option value="orientacao">Orientação</option>
                                                            <option value="encerramento">Encerrar no programa</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <input type="date" name="proximo_contato" class="form-control form-control-sm" value="<?= htmlspecialchars(date('Y-m-d', strtotime('+15 days'))) ?>">
                                                    </div>
                                                    <div class="col-12">
                                                        <input type="text" name="observacoes" class="form-control form-control-sm" placeholder="Resumo da ligação">
                                                    </div>
                                                    <div class="col-12 d-grid">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">Salvar monitoramento</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div>
                        <h2 class="h5 mb-1">Últimos movimentos da preventiva</h2>
                        <div class="text-muted small">Histórico recente de admissões e monitoramentos telefônicos.</div>
                    </div>
                </div>
                <?php if (!$actions): ?>
                    <div class="alert alert-light border mb-0">Sem movimentações recentes em Medicina Preventiva.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Quando</th>
                                    <th>Paciente</th>
                                    <th>Ação</th>
                                    <th>Foco</th>
                                    <th>Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actions as $action): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(mp_fmt_datetime($action['realizado_em'] ?? null)) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars((string)$action['nome_pac']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars((string)($action['matricula_pac'] ?: 'Sem matrícula')) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars(mp_action_label((string)$action['tipo_acao'])) ?></td>
                                        <td><?= htmlspecialchars((string)($action['foco'] ?: '-')) ?></td>
                                        <td class="mp-mini-note"><?= htmlspecialchars((string)($action['observacoes'] ?: '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once("templates/footer.php"); ?>
