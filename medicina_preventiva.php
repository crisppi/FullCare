<?php
include_once("check_logado.php");
require_once("templates/header.php");
require_once(__DIR__ . "/app/cuidadoContinuado.php");

ensure_cuidado_continuado_schema($conn);

$summary = cc_fetch_preventiva_summary($conn);
$campaigns = cc_fetch_active_campaigns($conn);
$elegiveis = cc_fetch_preventiva_elegiveis($conn);

function mp_fmt_date(?string $date): string
{
    if (!$date || $date === '0000-00-00') {
        return '-';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt ? $dt->format('d/m/Y') : (string)$date;
}
?>
<script src="js/timeout.js"></script>
<style>
    .mp-shell {
        padding: 36px 20px 32px;
        background: #f7faf5;
        min-height: 100vh;
    }
    .mp-hero {
        background: linear-gradient(135deg, #1b6a43, #3ba56b 58%, #c9e7b5);
        color: #fff;
        border-radius: 22px;
        padding: 20px 24px;
    }
    .mp-hero h1,
    .mp-hero h2,
    .mp-hero p,
    .mp-hero div {
        color: #fff !important;
    }
</style>

<div class="mp-shell">
    <div class="container-fluid">
        <div class="mp-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="text-uppercase small fw-semibold" style="letter-spacing:.08em;opacity:.85;">Cuidado Continuado</div>
                    <h1 class="h3 mt-2 mb-2">Medicina Preventiva</h1>
                    <p class="mb-0" style="max-width:780px;opacity:.92;">
                        Identificação de elegíveis, campanhas preventivas e acompanhamento de adesão a partir da carteira de pacientes crônicos.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <a class="btn btn-light" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado') ?>">Dashboard</a>
                    <a class="btn btn-outline-light" href="<?= htmlspecialchars($BASE_URL . 'cuidado-continuado/cronicos') ?>">Gestão de Crônicos</a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Campanhas</div><div class="fs-3 fw-bold"><?= (int)$summary['campanhas_total'] ?></div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Ativas</div><div class="fs-3 fw-bold text-success"><?= (int)$summary['campanhas_ativas'] ?></div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Elegíveis</div><div class="fs-3 fw-bold text-warning"><?= (int)$summary['elegiveis'] ?: count($elegiveis) ?></div></div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Concluídos</div><div class="fs-3 fw-bold"><?= (int)$summary['concluidos'] ?></div></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                            <div>
                                <h2 class="h5 mb-1">Campanhas preventivas</h2>
                                <div class="text-muted small">Estrutura pronta para campanhas por condição, risco e periodicidade.</div>
                            </div>
                        </div>
                        <?php if (!$campaigns): ?>
                            <div class="alert alert-light border mb-0">
                                Nenhuma campanha cadastrada ainda. A tabela base já foi criada para suportar planejamento, ativação e encerramento.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Campanha</th>
                                            <th>Status</th>
                                            <th>Início</th>
                                            <th>Fim</th>
                                            <th>Público</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($campaigns as $campaign): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars((string)$campaign['nome_campanha']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars((string)($campaign['publico_condicao'] ?: 'Carteira geral')) ?></div>
                                                </td>
                                                <td><?= htmlspecialchars((string)$campaign['status_campanha']) ?></td>
                                                <td><?= htmlspecialchars(mp_fmt_date($campaign['data_inicio'] ?? null)) ?></td>
                                                <td><?= htmlspecialchars(mp_fmt_date($campaign['data_fim'] ?? null)) ?></td>
                                                <td>
                                                    <?= (int)$campaign['total_publico'] ?> paciente(s)
                                                    <div class="small text-muted"><?= (int)$campaign['total_concluido'] ?> concluído(s)</div>
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
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                            <div>
                                <h2 class="h5 mb-1">Fila de elegibilidade</h2>
                                <div class="text-muted small">Pacientes crônicos sem contato preventivo recente.</div>
                            </div>
                        </div>
                        <?php if (!$elegiveis): ?>
                            <div class="alert alert-light border mb-0">
                                Ainda não existem elegíveis na base preventiva ou a carteira crônica ainda não foi iniciada.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Paciente</th>
                                            <th>Condição</th>
                                            <th>Último contato</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($elegiveis as $row): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars((string)$row['nome_pac']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars((string)$row['nivel_risco']) ?></div>
                                                </td>
                                                <td><?= htmlspecialchars((string)$row['condicao']) ?></td>
                                                <td><?= htmlspecialchars(mp_fmt_date($row['ultimo_contato_preventivo'] ?? null)) ?></td>
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
