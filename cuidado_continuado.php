<?php
include_once("check_logado.php");
require_once("templates/header.php");
require_once(__DIR__ . "/app/cuidadoContinuado.php");

ensure_cuidado_continuado_schema($conn);

$cronicos = cc_fetch_cronicos_summary($conn);
$preventiva = cc_fetch_preventiva_summary($conn);
$campaigns = cc_fetch_active_campaigns($conn);
$elegiveis = cc_fetch_preventiva_elegiveis($conn);

function cc_card(string $title, string $value, string $subtitle, string $accent): void
{
    ?>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <span class="badge rounded-pill" style="background: <?= htmlspecialchars($accent) ?>1a; color: <?= htmlspecialchars($accent) ?>;">
                    <?= htmlspecialchars($title) ?>
                </span>
                <div class="mt-3" style="font-size:2rem;font-weight:700;color:#24324a;line-height:1;">
                    <?= htmlspecialchars($value) ?>
                </div>
                <div class="mt-2 text-muted small"><?= htmlspecialchars($subtitle) ?></div>
            </div>
        </div>
    </div>
    <?php
}
?>
<script src="js/timeout.js"></script>
<style>
    .cc-shell {
        padding: 36px 20px 32px;
        background: linear-gradient(180deg, #f5f8ff 0%, #ffffff 180px);
        min-height: 100vh;
    }
    .cc-hero {
        background: linear-gradient(135deg, #0f3d63, #1d6a96 58%, #71c2cb);
        color: #fff;
        border-radius: 22px;
        padding: 22px 24px;
        box-shadow: 0 18px 45px rgba(15, 61, 99, 0.18);
    }
    .cc-hero h1,
    .cc-hero h2,
    .cc-hero p,
    .cc-hero div {
        color: #fff !important;
    }
    .cc-link-card {
        display: block;
        text-decoration: none;
        color: inherit;
        background: #fff;
        border-radius: 18px;
        padding: 22px;
        height: 100%;
        box-shadow: 0 14px 34px rgba(36, 50, 74, 0.08);
        border: 1px solid rgba(15, 61, 99, 0.08);
    }
    .cc-link-card:hover {
        color: inherit;
        transform: translateY(-1px);
    }
</style>

<div class="cc-shell">
    <div class="container-fluid">
        <div class="cc-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-end">
                <div>
                    <div class="text-uppercase small fw-semibold" style="letter-spacing:.08em;opacity:.8;">Cuidado Continuado</div>
                    <h1 class="h3 mt-2 mb-2">Módulos iniciais de gestão de crônicos e medicina preventiva</h1>
                    <p class="mb-0" style="max-width:760px;opacity:.92;">
                        Estrutura base pronta para acompanhar pacientes crônicos, organizar elegibilidade preventiva e evoluir para campanhas, jornadas e protocolos.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-light" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/cronicos') ?>">Gestão de Crônicos</a>
                    <a class="btn btn-outline-light" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/medicina-preventiva') ?>">Medicina Preventiva</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <?php cc_card('Crônicos', (string)$cronicos['total'], 'Pacientes acompanhados na base do módulo.', '#1d6a96'); ?>
            <?php cc_card('Alto risco', (string)$cronicos['alto_risco'], 'Pacientes que exigem monitoramento mais próximo.', '#c43d4b'); ?>
            <?php cc_card('Campanhas ativas', (string)$preventiva['campanhas_ativas'], 'Ações preventivas já abertas na operação.', '#198754'); ?>
            <?php cc_card('Elegíveis', (string)count($elegiveis), 'Pacientes aptos para nova ação preventiva.', '#b26a00'); ?>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <a class="cc-link-card" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/cronicos') ?>">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-uppercase small fw-semibold text-muted">Módulo 1</div>
                            <h2 class="h4 mt-2">Gestão de Crônicos</h2>
                            <p class="text-muted mb-3">Painel assistencial com risco, pendências de retorno, condições crônicas e fila prioritária.</p>
                        </div>
                        <i class="bi bi-heart-pulse-fill" style="font-size:2rem;color:#1d6a96;"></i>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <div class="text-muted small">Ativos</div>
                            <div class="fw-bold fs-5"><?= (int)$cronicos['ativos'] ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Pendentes</div>
                            <div class="fw-bold fs-5"><?= (int)$cronicos['pendentes'] ?></div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-xl-6">
                <a class="cc-link-card" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/medicina-preventiva') ?>">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="text-uppercase small fw-semibold text-muted">Módulo 2</div>
                            <h2 class="h4 mt-2">Medicina Preventiva</h2>
                            <p class="text-muted mb-3">Base para campanhas, convocação de elegíveis, adesão e cobertura por condição e risco.</p>
                        </div>
                        <i class="bi bi-shield-check" style="font-size:2rem;color:#198754;"></i>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <div class="text-muted small">Campanhas</div>
                            <div class="fw-bold fs-5"><?= (int)$preventiva['campanhas_total'] ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Conclusões</div>
                            <div class="fw-bold fs-5"><?= (int)$preventiva['concluidos'] ?></div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h5 mb-0">Campanhas Preventivas</h3>
                            <a href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/medicina-preventiva') ?>" class="btn btn-sm btn-outline-secondary">Abrir módulo</a>
                        </div>
                        <?php if (!$campaigns): ?>
                            <div class="alert alert-light border mb-0">Nenhuma campanha criada ainda. A estrutura já está pronta para iniciar a primeira campanha preventiva.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Campanha</th>
                                            <th>Status</th>
                                            <th>Público</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($campaigns, 0, 5) as $campaign): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars((string)$campaign['nome_campanha']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars((string)($campaign['publico_condicao'] ?: 'Condição não definida')) ?></div>
                                                </td>
                                                <td><?= htmlspecialchars((string)$campaign['status_campanha']) ?></td>
                                                <td><?= (int)$campaign['total_publico'] ?> paciente(s)</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h5 mb-0">Elegíveis Prioritários</h3>
                            <a href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/cronicos') ?>" class="btn btn-sm btn-outline-secondary">Ver crônicos</a>
                        </div>
                        <?php if (!$elegiveis): ?>
                            <div class="alert alert-light border mb-0">Ainda não há pacientes crônicos cadastrados ou todos já receberam contato preventivo recente.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Paciente</th>
                                            <th>Condição</th>
                                            <th>Risco</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($elegiveis, 0, 5) as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars((string)$item['nome_pac']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars((string)($item['matricula_pac'] ?: 'Sem matrícula')) ?></div>
                                                </td>
                                                <td><?= htmlspecialchars((string)$item['condicao']) ?></td>
                                                <td><?= htmlspecialchars((string)$item['nivel_risco']) ?></td>
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
    </div>
</div>

<?php include_once("templates/footer.php"); ?>
