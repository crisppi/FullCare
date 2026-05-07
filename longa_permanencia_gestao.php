<?php
include_once("check_logado.php");
require_once("globals.php");
require_once("db.php");
require_once("templates/header.php");
require_once("dao/longaPermanenciaDao.php");

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$dao = new LongaPermanenciaDAO($conn, $BASE_URL);
$hospitalId = filter_input(INPUT_GET, 'hospital_id', FILTER_VALIDATE_INT) ?: null;
$seguradoraId = filter_input(INPUT_GET, 'seguradora_id', FILTER_VALIDATE_INT) ?: null;
$status = trim((string)(filter_input(INPUT_GET, 'status') ?? ''));
$escalonamento = trim((string)(filter_input(INPUT_GET, 'escalonamento') ?? ''));
$semAtualizacao = filter_input(INPUT_GET, 'sem_atualizacao', FILTER_VALIDATE_INT) ?: null;
$queue = [];
$statusOptions = $dao->getStatusOptions();
$hospitais = [];
$seguradoras = [];
$pageError = '';

try {
    $queue = $dao->fetchQueue([
        'hospital_id' => $hospitalId,
        'seguradora_id' => $seguradoraId,
        'status' => $status,
        'escalonamento' => $escalonamento,
        'sem_atualizacao' => $semAtualizacao,
    ]);

    $hospitais = $conn->query("SELECT id_hospital, nome_hosp FROM tb_hospital ORDER BY nome_hosp")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $seguradoras = $conn->query("SELECT id_seguradora, seguradora_seg FROM tb_seguradora ORDER BY seguradora_seg")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $pageError = 'Nao foi possivel carregar a fila de longa permanencia agora.';
    error_log('[LONGA_PERMANENCIA][GESTAO][ERROR] ' . $e->getMessage());
}

$totais = [
    'casos' => count($queue),
    'sem_status' => 0,
    'escalonados' => 0,
    'sem_revisao' => 0,
];
foreach ($queue as $row) {
    if (empty($row['status_lp'])) {
        $totais['sem_status']++;
    }
    if (($row['necessita_escalonamento_lp'] ?? 'n') === 's') {
        $totais['escalonados']++;
    }
    $ultimaAtualizacao = !empty($row['data_atualizacao_lp']) ? strtotime((string)$row['data_atualizacao_lp']) : false;
    if (!$ultimaAtualizacao || $ultimaAtualizacao < strtotime('-7 days')) {
        $totais['sem_revisao']++;
    }
}
?>

<style>
.lp-shell { padding: 20px 18px 34px; background: linear-gradient(180deg, #f7f5fb 0%, #eef2f7 100%); min-height: calc(100vh - 100px); }
.lp-hero { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; margin-bottom:18px; }
.lp-hero h1 { margin:0; font-size:1.6rem; color:#2f2240; }
.lp-hero p { margin:6px 0 0; color:#6b6580; }
.lp-top-actions { display:flex; gap:10px; flex-wrap:wrap; }
.lp-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:38px; padding:0 14px; border-radius:10px; border:1px solid rgba(94,35,99,.14); background:#fff; color:#4f2b63; font-weight:600; text-decoration:none; }
.lp-btn--primary { background:linear-gradient(135deg, #5e2363, #7d52a1); color:#fff; border:none; }
.lp-kpis { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:16px; }
.lp-kpi { padding:14px 16px; border-radius:16px; background:#fff; border:1px solid rgba(94,35,99,.08); box-shadow:0 12px 28px rgba(40,26,64,.08); }
.lp-kpi small { display:block; color:#7a728f; text-transform:uppercase; letter-spacing:.08em; font-size:.66rem; margin-bottom:6px; }
.lp-kpi strong { font-size:1.5rem; color:#2f2240; }
.lp-grid { display:grid; grid-template-columns:300px minmax(0,1fr); gap:16px; }
.lp-card { background:#fff; border:1px solid rgba(94,35,99,.08); border-radius:18px; box-shadow:0 16px 34px rgba(40,26,64,.08); overflow:hidden; }
.lp-card__head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:14px 16px; border-bottom:1px solid rgba(94,35,99,.08); }
.lp-card__head h2 { margin:0; font-size:1rem; color:#2f2240; }
.lp-card__body { padding:16px; }
.lp-filter { margin-bottom:12px; }
.lp-filter label { display:block; margin-bottom:5px; font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6b6580; }
.lp-filter select, .lp-filter input { width:100%; min-height:40px; border-radius:10px; border:1px solid #d8d2e4; padding:8px 12px; font-size:.82rem; color:#342944; background:#fff; }
.lp-filter-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
.lp-table { width:100%; border-collapse:separate; border-spacing:0; }
.lp-table th, .lp-table td { padding:12px 10px; border-bottom:1px solid #ece7f4; vertical-align:top; font-size:.8rem; color:#3d334d; }
.lp-table th { font-size:.68rem; text-transform:uppercase; letter-spacing:.08em; color:#766d89; background:#faf8fd; }
.lp-table tr:hover td { background:#faf7ff; }
.lp-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 9px; border-radius:999px; font-size:.7rem; font-weight:700; }
.lp-chip--warn { background:#fff3d8; color:#946200; }
.lp-chip--critical { background:#ffe1e1; color:#a82b2b; }
.lp-chip--ok { background:#e8f7ec; color:#2b7a46; }
.lp-chip--neutral { background:#f1eef7; color:#5d5573; }
.lp-sub { color:#7e7692; font-size:.72rem; margin-top:3px; }
.lp-empty { padding:28px 16px; text-align:center; color:#7c7590; }
.lp-alert { margin-bottom:16px; padding:14px 16px; border-radius:14px; background:#fff0f0; border:1px solid #f2c7c7; color:#8a2f2f; box-shadow:0 10px 24px rgba(138,47,47,.08); }
@media (max-width: 1100px) { .lp-kpis { grid-template-columns:repeat(2, minmax(0,1fr)); } .lp-grid { grid-template-columns:1fr; } }
@media (max-width: 680px) { .lp-kpis { grid-template-columns:1fr; } .lp-hero { flex-direction:column; align-items:flex-start; } }
</style>

<div class="lp-shell">
    <div class="lp-hero">
        <div>
            <h1>Gestão de Longa Permanência</h1>
            <p>Fila operacional para revisão clínica, barreiras e plano de ação dos casos com maior impacto potencial na sinistralidade.</p>
        </div>
        <div class="lp-top-actions">
            <a class="lp-btn" href="<?= $BASE_URL ?>bi/longa-permanencia">Voltar ao BI</a>
        </div>
    </div>

    <?php if ($pageError !== ''): ?>
        <div class="lp-alert"><?= e($pageError) ?></div>
    <?php endif; ?>

    <div class="lp-kpis">
        <div class="lp-kpi"><small>Casos na fila</small><strong><?= number_format($totais['casos'], 0, ',', '.') ?></strong></div>
        <div class="lp-kpi"><small>Sem status</small><strong><?= number_format($totais['sem_status'], 0, ',', '.') ?></strong></div>
        <div class="lp-kpi"><small>Escalonados</small><strong><?= number_format($totais['escalonados'], 0, ',', '.') ?></strong></div>
        <div class="lp-kpi"><small>Sem revisão > 7d</small><strong><?= number_format($totais['sem_revisao'], 0, ',', '.') ?></strong></div>
    </div>

    <div class="lp-grid">
        <aside class="lp-card">
            <div class="lp-card__head">
                <h2>Filtros</h2>
            </div>
            <div class="lp-card__body">
                <form method="get">
                    <div class="lp-filter">
                        <label>Hospital</label>
                        <select name="hospital_id">
                            <option value="">Todos</option>
                            <?php foreach ($hospitais as $h): ?>
                                <option value="<?= (int)$h['id_hospital'] ?>" <?= $hospitalId == $h['id_hospital'] ? 'selected' : '' ?>><?= e($h['nome_hosp']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lp-filter">
                        <label>Seguradora</label>
                        <select name="seguradora_id">
                            <option value="">Todas</option>
                            <?php foreach ($seguradoras as $s): ?>
                                <option value="<?= (int)$s['id_seguradora'] ?>" <?= $seguradoraId == $s['id_seguradora'] ? 'selected' : '' ?>><?= e($s['seguradora_seg']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lp-filter">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Todos</option>
                            <option value="__sem_status__" <?= $status === '__sem_status__' ? 'selected' : '' ?>>Sem status</option>
                            <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lp-filter">
                        <label>Escalonamento</label>
                        <select name="escalonamento">
                            <option value="">Todos</option>
                            <option value="s" <?= $escalonamento === 's' ? 'selected' : '' ?>>Necessita escalonamento</option>
                            <option value="n" <?= $escalonamento === 'n' ? 'selected' : '' ?>>Sem escalonamento</option>
                        </select>
                    </div>
                    <div class="lp-filter">
                        <label>Sem revisão há</label>
                        <select name="sem_atualizacao">
                            <option value="">Todos</option>
                            <option value="7" <?= (int)$semAtualizacao === 7 ? 'selected' : '' ?>>7 dias</option>
                            <option value="15" <?= (int)$semAtualizacao === 15 ? 'selected' : '' ?>>15 dias</option>
                            <option value="30" <?= (int)$semAtualizacao === 30 ? 'selected' : '' ?>>30 dias</option>
                        </select>
                    </div>
                    <div class="lp-filter-actions">
                        <button class="lp-btn lp-btn--primary" type="submit">Aplicar</button>
                        <a class="lp-btn" href="<?= $BASE_URL ?>longa_permanencia_gestao.php">Limpar</a>
                    </div>
                </form>
            </div>
        </aside>

        <section class="lp-card">
            <div class="lp-card__head">
                <h2>Fila de casos</h2>
                <div class="lp-sub"><?= number_format(count($queue), 0, ',', '.') ?> caso(s)</div>
            </div>
            <div class="lp-card__body" style="padding:0;">
                <?php if (!$queue): ?>
                    <div class="lp-empty">Nenhum caso de longa permanência encontrado para os filtros atuais.</div>
                <?php else: ?>
                    <div style="overflow:auto;">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Hospital</th>
                                    <th>Dias</th>
                                    <th>Status atual</th>
                                    <th>Próxima revisão</th>
                                    <th>Responsável</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($queue as $row): ?>
                                    <?php
                                    $excesso = max(0, (int)$row['diarias'] - (int)$row['limiar']);
                                    $ultimaAtualizacaoTs = !empty($row['data_atualizacao_lp']) ? strtotime((string)$row['data_atualizacao_lp']) : false;
                                    $reviewChip = !$ultimaAtualizacaoTs || $ultimaAtualizacaoTs < strtotime('-7 days')
                                        ? 'lp-chip lp-chip--critical'
                                        : 'lp-chip lp-chip--ok';
                                    $reviewLabel = !$ultimaAtualizacaoTs || $ultimaAtualizacaoTs < strtotime('-7 days')
                                        ? 'Revisão atrasada'
                                        : 'Atualizado';
                                    ?>
                                    <tr>
                                        <td>
                                            <div><strong><?= e($row['nome_pac'] ?? 'Sem nome') ?></strong></div>
                                            <div class="lp-sub">Seguradora: <?= e($row['seguradora_seg'] ?? 'Sem seguradora') ?></div>
                                        </td>
                                        <td>
                                            <div><?= e($row['nome_hosp'] ?? 'Sem hospital') ?></div>
                                            <div class="lp-sub">Internação: <?= !empty($row['data_intern_int']) ? e(date('d/m/Y', strtotime((string)$row['data_intern_int']))) : '-' ?></div>
                                        </td>
                                        <td>
                                            <div><strong><?= number_format((int)$row['diarias'], 0, ',', '.') ?>d</strong></div>
                                            <div class="lp-sub">Limiar <?= number_format((int)$row['limiar'], 0, ',', '.') ?>d · excesso <?= number_format($excesso, 0, ',', '.') ?>d</div>
                                        </td>
                                        <td>
                                            <div class="lp-chip <?= !empty($row['status_lp']) ? 'lp-chip--neutral' : 'lp-chip--warn' ?>">
                                                <?= e($statusOptions[$row['status_lp']] ?? 'Sem status') ?>
                                            </div>
                                            <?php if (!empty($row['motivo_principal_lp'])): ?>
                                                <div class="lp-sub"><?= e($row['motivo_principal_lp']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="<?= $reviewChip ?>"><?= e($reviewLabel) ?></div>
                                            <div class="lp-sub">
                                                <?= !empty($row['proxima_revisao_lp']) ? e(date('d/m/Y', strtotime((string)$row['proxima_revisao_lp']))) : 'Sem data definida' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= e($row['responsavel_lp'] ?? '-') ?></div>
                                            <?php if (($row['necessita_escalonamento_lp'] ?? 'n') === 's'): ?>
                                                <div class="lp-sub" style="color:#a33d3d;font-weight:700;">Escalonar</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <a class="lp-btn lp-btn--primary" href="<?= $BASE_URL ?>longa_permanencia_editar.php?id_internacao=<?= (int)$row['id_internacao'] ?>">Gerir caso</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
