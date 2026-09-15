<?php
require_once __DIR__.'/check_logado.php';
require_once __DIR__.'/globals.php';
require_once __DIR__.'/ajax/_auth_scope.php';
require_once __DIR__.'/app/services/HomeCareExtensionService.php';
FullCareAccess::enforce($conn, $BASE_URL, 'cuidado_continuado', 'view');
$service = new HomeCareExtensionService($conn, ajax_user_context($conn));
$base = $BASE_URL.'cuidado-continuado/home-care/pacientes';
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$search = is_string($_GET['q'] ?? '') ? mb_substr(trim($_GET['q'] ?? ''), 0, 100) : '';
$page = max(1, (int)(filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1));
$listing = ['rows'=>[], 'count'=>0, 'page'=>1];
$error = '';
try {
    $listing = $service->cases($search, $page, true);
} catch (Throwable $e) {
    error_log('[HC PACIENTES] '.$e->getMessage());
    $error = 'Não foi possível carregar os pacientes em home care. Tente novamente.';
}
require_once __DIR__.'/templates/header.php';
?>
<link rel="stylesheet" href="<?= $h($BASE_URL) ?>css/home_care_prorrogacao.css?v=<?= filemtime(__DIR__.'/css/home_care_prorrogacao.css') ?>">
<main class="hcx-page">
    <header class="fc-module-header fc-module-header--cuidado">
        <div class="fc-module-header__copy">
            <p class="fc-module-header__kicker">Cuidado continuado · Home care</p>
            <h1 class="fc-module-header__title">Pacientes em home care</h1>
            <p class="fc-module-header__subtitle">Pacientes com home care implantado, incluindo os que já tiveram alta hospitalar.</p>
        </div>
        <div class="fc-module-header__actions">
            <a class="btn btn-light" href="<?= $h($BASE_URL) ?>cuidado-continuado/home-care">Gestão de home care</a>
            <a class="btn btn-light" href="<?= $h($BASE_URL) ?>cuidado-continuado/home-care/prorrogacoes">Prorrogações</a>
        </div>
    </header>
    <?php if ($error): ?>
        <p class="hcx-alert hcx-alert--error" role="alert"><?= $h($error) ?></p>
    <?php else: ?>
    <section class="hcx-card">
        <form method="get" action="<?= $h($base) ?>" class="hcx-actions">
            <label class="hcx-search">Paciente ou hospital de origem<input type="search" name="q" maxlength="100" value="<?= $h($search) ?>" placeholder="Pesquisar paciente ou hospital"></label>
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a href="<?= $h($base) ?>">Limpar</a>
        </form>
        <div class="hcx-table-wrap">
            <table>
                <thead><tr><th>Paciente / caso</th><th>Hospital de origem / seguradora</th><th>Prestador</th><th>Modalidade</th><th>Último período autorizado até</th><th>Prorrogações pendentes</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($listing['rows'] as $row): ?>
                    <tr>
                        <td><?= $h($row['nome_pac']) ?><small>Caso #<?= (int)$row['id_internacao'] ?></small></td>
                        <td><?= $h($row['nome_hosp'] ?? '—') ?><small><?= $h($row['seguradora_seg'] ?? '—') ?></small></td>
                        <td><?= $h($row['fornecedor_hc'] ?: 'Não informado') ?></td>
                        <td><?php $mode = $row['modalidade_aprovada_hc'] ?: $row['modalidade_sugerida_hc']; ?><?= $h(HomeCareExtensionService::MODES[$mode] ?? 'Não informada') ?></td>
                        <td><?= $row['autorizado_ate'] ? $h(date('d/m/Y', strtotime($row['autorizado_ate']))) : 'Sem período registrado' ?><?php if ($row['autorizado_ate'] && $row['autorizado_ate'] < date('Y-m-d')): ?><small class="hcx-expired">Período vencido</small><?php endif; ?></td>
                        <td><?= (int)$row['pendentes'] ?></td>
                        <td><div class="hcx-actions"><a href="<?= $h($BASE_URL) ?>cuidado-continuado/home-care/avaliar/<?= (int)$row['id_internacao'] ?>">Avaliação</a><a href="<?= $h($BASE_URL) ?>cuidado-continuado/home-care/prorrogacoes?caso=<?= (int)$row['id_internacao'] ?>">Prorrogações</a></div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$listing['rows']): ?><tr><td colspan="7" class="hcx-empty"><?= $search !== '' ? 'Nenhum paciente encontrado para esta busca.' : 'Nenhum paciente com home care implantado. Os casos aparecem aqui quando a avaliação registra o status Implantado.' ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <nav class="hcx-actions" aria-label="Paginação">
            <span><?= (int)$listing['count'] ?> caso(s) em home care · Página <?= (int)$listing['page'] ?></span>
            <?php if ($listing['page'] > 1): ?><a href="<?= $h($base.'?'.http_build_query(['q'=>$search, 'pagina'=>$listing['page']-1])) ?>">Anterior</a><?php endif; ?>
            <?php if ($listing['page']*25 < $listing['count']): ?><a href="<?= $h($base.'?'.http_build_query(['q'=>$search, 'pagina'=>$listing['page']+1])) ?>">Próxima</a><?php endif; ?>
        </nav>
    </section>
    <?php endif; ?>
</main>
<?php require_once __DIR__.'/templates/footer.php'; ?>
