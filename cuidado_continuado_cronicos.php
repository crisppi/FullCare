<?php
include_once("check_logado.php");
require_once("templates/header.php");
require_once(__DIR__ . "/app/cuidadoContinuado.php");

ensure_cuidado_continuado_schema($conn);

$search = trim((string)filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW));
$risk = trim((string)filter_input(INPUT_GET, 'risco', FILTER_UNSAFE_RAW));
$summary = cc_fetch_cronicos_summary($conn);
$rows = cc_fetch_cronicos_list($conn, $search, $risk);

function cc_fmt_date(?string $date): string
{
    if (!$date || $date === '0000-00-00') {
        return '-';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt ? $dt->format('d/m/Y') : (string)$date;
}

function cc_badge_class(string $risk): string
{
    if ($risk === 'alto') {
        return 'danger';
    }
    if ($risk === 'moderado') {
        return 'warning';
    }
    return 'secondary';
}
?>
<script src="js/timeout.js"></script>
<style>
    .cc-module-shell {
        padding: 36px 20px 32px;
        background: #f6f8fc;
        min-height: 100vh;
    }
    .cc-module-hero {
        background: linear-gradient(135deg, #6b2230, #b6475f 60%, #f2b8c6);
        color: #fff;
        border-radius: 22px;
        padding: 20px 24px;
    }
    .cc-module-hero h1,
    .cc-module-hero h2,
    .cc-module-hero p,
    .cc-module-hero div {
        color: #fff !important;
    }
</style>

<div class="cc-module-shell">
    <div class="container-fluid">
        <div class="cc-module-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="text-uppercase small fw-semibold" style="letter-spacing:.08em;opacity:.85;">Cuidado Continuado</div>
                    <h1 class="h3 mt-2 mb-2">Gestão de Crônicos</h1>
                    <p class="mb-0" style="max-width:780px;opacity:.92;">
                        Acompanhamento longitudinal de pacientes com doenças crônicas, priorização por risco e monitoramento de pendências assistenciais.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <a class="btn btn-light" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado') ?>">Dashboard</a>
                    <a class="btn btn-outline-light" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/medicina-preventiva') ?>">Medicina Preventiva</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total</div><div class="fs-3 fw-bold"><?= (int)$summary['total'] ?></div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Ativos</div><div class="fs-3 fw-bold"><?= (int)$summary['ativos'] ?></div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Alto risco</div><div class="fs-3 fw-bold text-danger"><?= (int)$summary['alto_risco'] ?></div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Pendentes</div><div class="fs-3 fw-bold text-warning"><?= (int)$summary['pendentes'] ?></div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form class="row g-3 align-items-end" method="get">
                    <div class="col-12 col-lg-7">
                        <label class="form-label">Pesquisar paciente, matrícula ou condição</label>
                        <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Ex.: diabetes, João, matrícula">
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label">Risco</label>
                        <select name="risco" class="form-select">
                            <option value="">Todos</option>
                            <option value="baixo"<?= $risk === 'baixo' ? ' selected' : '' ?>>Baixo</option>
                            <option value="moderado"<?= $risk === 'moderado' ? ' selected' : '' ?>>Moderado</option>
                            <option value="alto"<?= $risk === 'alto' ? ' selected' : '' ?>>Alto</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div>
                        <h2 class="h5 mb-1">Fila de acompanhamento</h2>
                        <div class="text-muted small">Base inicial do módulo. A próxima etapa pode incluir cadastro, edição e protocolos por condição.</div>
                    </div>
                </div>

                <?php if (!$rows): ?>
                    <div class="alert alert-light border mb-0">
                        Nenhum paciente crônico encontrado. A estrutura do módulo já está pronta, mas ainda não há registros carregados nessa base.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Condição</th>
                                    <th>Risco</th>
                                    <th>Status</th>
                                    <th>Última consulta</th>
                                    <th>Próximo contato</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars((string)$row['nome_pac']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars((string)($row['matricula_pac'] ?: 'Sem matrícula')) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars((string)$row['condicao']) ?></td>
                                        <td><span class="badge bg-<?= cc_badge_class((string)$row['nivel_risco']) ?>"><?= htmlspecialchars((string)$row['nivel_risco']) ?></span></td>
                                        <td><?= htmlspecialchars((string)$row['status_acompanhamento']) ?></td>
                                        <td><?= htmlspecialchars(cc_fmt_date($row['ultima_consulta'] ?? null)) ?></td>
                                        <td><?= htmlspecialchars(cc_fmt_date($row['proximo_contato'] ?? null)) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($BASE_URL . 'hub_paciente/paciente' . (int)$row['fk_paciente']) ?>">Paciente</a>
                                        </td>
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
