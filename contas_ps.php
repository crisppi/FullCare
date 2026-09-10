<?php
require_once __DIR__ . '/check_logado.php';
require_once __DIR__ . '/globals.php';
require_once __DIR__ . '/ajax/_auth_scope.php';
require_once __DIR__ . '/app/services/ProntoSocorroAuditService.php';
FullCareAccess::enforce($conn, $BASE_URL, 'contas', 'view');
$canCreate = FullCareAccess::can($conn, 'contas', 'create');
$canEdit = FullCareAccess::can($conn, 'contas', 'edit');
$ps = new ProntoSocorroAuditService($conn, ajax_user_context($conn));
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'R$ ' . number_format((float)$value, 2, ',', '.');
$base = $BASE_URL . 'contas/pronto-socorro';
$_SESSION['ps_csrf'] = $_SESSION['ps_csrf'] ?? bin2hex(random_bytes(32));
$error = ''; $unavailable = false; $lot = null; $account = null; $accounts = []; $history = [];
$lotId = max(0, (int)($_GET['lote'] ?? 0)); $accountId = max(0, (int)($_GET['conta'] ?? 0));
$newAccount = isset($_GET['nova']); $newLot = isset($_GET['novo_lote']);
$search = is_string($_GET['q'] ?? '') ? mb_substr(trim($_GET['q'] ?? ''),0,100) : '';
try {
    $tables = (int)$conn->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('tb_ps_lote','tb_ps_conta','tb_ps_item','tb_ps_historico')")->fetchColumn();
    if ($tables !== 4) {
        $unavailable = true;
        throw new DomainException('A auditoria de PS ainda não está disponível neste ambiente.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['ps_csrf'], $_POST['csrf'])) throw new DomainException('Sessão do formulário expirada. Atualize a página e tente novamente.');
        $action = $_POST['action'] ?? '';
        if ($action === 'create_lot') {
            FullCareAccess::enforce($conn, $BASE_URL, 'contas', 'create');
            $lotId = $ps->createLot($_POST);
            $_SESSION['ps_notice'] = 'Lote criado. Cadastre a primeira conta de PS.';
            header('Location: ' . $base . '?lote=' . $lotId . '&nova=1', true, 303); exit;
        }
        if ($action !== 'save_account') throw new DomainException('Ação inválida.');
        FullCareAccess::enforce($conn, $BASE_URL, 'contas', $accountId > 0 ? 'edit' : 'create');
        if (!$lotId) throw new DomainException('Selecione um lote antes de cadastrar a conta.');
        $accountId = $ps->saveAccount($lotId, $accountId, $_POST);
        $_SESSION['ps_notice'] = 'Conta salva. Totais do lote atualizados.';
        header('Location: ' . $base . '?lote=' . $lotId . '&conta=' . $accountId, true, 303); exit;
    }
} catch (DomainException $e) { $error = $e->getMessage(); }
catch (PDOException $e) {
    error_log('[PS] ' . $e->getMessage());
    if ((int)($e->errorInfo[1] ?? 0) === 1062) $error = 'Já existe um registro com este número no lote ou neste hospital e seguradora.';
    else { $error = 'Não foi possível acessar ou salvar a auditoria de PS. Tente novamente.'; $unavailable = true; }
}
$options = ['hospitais'=>[], 'seguradoras'=>[]]; $lots = ['rows'=>[], 'count'=>0, 'page'=>1];
if (!$unavailable) {
    try {
        $options = $ps->options();
        if ($lotId) {
            $lot = $ps->lot($lotId); $accounts = $ps->accounts($lotId);
            if ($accountId) {
                $account = $ps->account($accountId);
                if ((int)$account['lote_id'] !== $lotId) throw new DomainException('A conta não pertence a este lote.');
                $history = $ps->history($accountId);
            }
        } else $lots = $ps->lots($search, max(1,(int)($_GET['pagina'] ?? 1)));
    } catch (DomainException $e) { $error = $e->getMessage(); $lot = null; $account = null; $accounts = []; $history = []; http_response_code(404); }
    catch (Throwable $e) { error_log('[PS] ' . $e->getMessage()); $error = 'Não foi possível carregar os dados de PS.'; $unavailable = true; }
}
$notice = $_SESSION['ps_notice'] ?? ''; unset($_SESSION['ps_notice']);
$editing = $lot && ($newAccount || $account);
$form = $account ?? ['numero'=>'','paciente'=>'','matricula'=>'','atendimento'=>'','entrada'=>'','saida'=>'','modalidade'=>'aberta','pacote'=>'','status'=>'em_auditoria','observacao'=>'','versao'=>0];
if ($account) foreach (['entrada','saida'] as $field) $form[$field] = $form[$field] ? substr(str_replace(' ','T',$form[$field]),0,16) : '';
if ($error && ($_POST['action'] ?? '') === 'save_account' && $lot) $form = array_merge($form, array_intersect_key($_POST,$form));
$blocks = ProntoSocorroAuditService::toBlocks($account['items'] ?? []);
if ($error && ($_POST['action'] ?? '') === 'save_account' && is_array($_POST['blocos'] ?? null)) {
    foreach ($blocks as $key=>$block) {
        if (is_array($_POST['blocos'][$key] ?? null)) $blocks[$key] = array_merge($block,array_intersect_key($_POST['blocos'][$key],$block));
    }
}
$writable = $account ? $canEdit : $canCreate;
require_once __DIR__ . '/templates/header.php';
?>
<link rel="stylesheet" href="<?= $h($BASE_URL) ?>css/contas_ps.css?v=<?= filemtime(__DIR__ . '/css/contas_ps.css') ?>">
<main class="ps-page">
    <header class="fc-module-header fc-module-header--contas">
        <div class="fc-module-header__copy">
            <p class="fc-module-header__kicker">Contas · Pronto-socorro</p>
            <h1 class="fc-module-header__title">Auditoria de contas de PS</h1>
            <p class="fc-module-header__subtitle">Auditoria por atendimento, com contas em pacote ou abertas vinculadas a um lote.</p>
        </div>
        <div class="fc-module-header__actions">
            <?php if ($lot): ?><a class="btn btn-light" href="<?= $h($base) ?>">Todos os lotes</a><?php endif; ?>
            <?php if ($canCreate): ?><a class="btn btn-light" href="<?= $h($base . ($lot ? '?lote='.$lotId.'&nova=1' : '?novo_lote=1')) ?>"><?= $lot ? 'Nova conta' : 'Novo lote' ?></a><?php endif; ?>
        </div>
    </header>
    <?php if ($error): ?><div class="ps-alert ps-alert--error" role="alert"><?= $h($error) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="ps-alert" role="status"><?= $h($notice) ?></div><?php endif; ?>
    <?php if (!$unavailable && !$lotId): ?>
        <?php if ($newLot && $canCreate): ?>
        <section class="ps-card">
            <h2>Novo lote</h2>
            <form method="post" action="<?= $h($base) ?>?novo_lote=1" class="ps-fields">
                <input type="hidden" name="csrf" value="<?= $h($_SESSION['ps_csrf']) ?>"><input type="hidden" name="action" value="create_lot">
                <label>Número do lote<input name="numero" maxlength="60" required value="<?= $h($_POST['numero'] ?? '') ?>"></label>
                <label>Hospital<select name="hospital_id" required><option value="">Selecione</option><?php foreach ($options['hospitais'] as $o): ?><option value="<?= (int)$o['id'] ?>" <?= (int)($_POST['hospital_id'] ?? 0)===(int)$o['id']?'selected':'' ?>><?= $h($o['nome']) ?></option><?php endforeach; ?></select></label>
                <label>Seguradora<select name="seguradora_id" required><option value="">Selecione</option><?php foreach ($options['seguradoras'] as $o): ?><option value="<?= (int)$o['id'] ?>" <?= (int)($_POST['seguradora_id'] ?? 0)===(int)$o['id']?'selected':'' ?>><?= $h($o['nome']) ?></option><?php endforeach; ?></select></label>
                <label>Competência<input type="month" name="competencia" required value="<?= $h($_POST['competencia'] ?? date('Y-m')) ?>"></label>
                <label>Recebido em<input type="date" name="recebido_em" required value="<?= $h($_POST['recebido_em'] ?? date('Y-m-d')) ?>"></label>
                <div class="ps-actions"><button class="btn btn-primary" type="submit">Criar lote</button><a href="<?= $h($base) ?>">Cancelar</a></div>
            </form>
        </section>
        <?php endif; ?>
        <section class="ps-card">
            <form class="ps-search" method="get" action="<?= $h($base) ?>"><label>Pesquisar lote ou hospital<input type="search" name="q" maxlength="100" value="<?= $h($search) ?>" placeholder="Número do lote ou hospital"></label><button type="submit" class="btn btn-primary">Filtrar</button><a href="<?= $h($base) ?>">Limpar</a></form>
            <div class="ps-table-wrap"><table><thead><tr><th>Lote</th><th>Hospital / Seguradora</th><th>Competência</th><th>Auditadas / Contas</th><th>Cobrado</th><th>Glosado</th><th>Liberado</th><th>Ação</th></tr></thead><tbody>
                <?php foreach ($lots['rows'] as $r): ?><tr><td><?= $h($r['numero']) ?></td><td><?= $h($r['hospital']) ?><small><?= $h($r['seguradora']) ?></small></td><td><?= $h(substr($r['competencia'],5,2).'/'.substr($r['competencia'],0,4)) ?></td><td><?= (int)$r['finalizadas'] ?> / <?= (int)$r['contas'] ?></td><td><?= $money($r['cobrado']) ?></td><td><?= $money($r['glosado']) ?></td><td><?= $money($r['liberado']) ?></td><td><a href="<?= $h($base.'?lote='.$r['id']) ?>">Abrir lote</a></td></tr><?php endforeach; ?>
                <?php if (!$lots['rows']): ?><tr><td colspan="8" class="ps-empty">Nenhum lote encontrado. Use “Novo lote” para começar.</td></tr><?php endif; ?>
            </tbody></table></div>
            <nav class="ps-actions" aria-label="Paginação dos lotes"><span><?= (int)$lots['count'] ?> lote(s) · Página <?= (int)$lots['page'] ?></span><?php if ($lots['page']>1): ?><a href="<?= $h($base.'?'.http_build_query(['q'=>$search,'pagina'=>$lots['page']-1])) ?>">Anterior</a><?php endif; ?><?php if ($lots['page']*20<$lots['count']): ?><a href="<?= $h($base.'?'.http_build_query(['q'=>$search,'pagina'=>$lots['page']+1])) ?>">Próxima</a><?php endif; ?></nav>
        </section>
    <?php endif; ?>
    <?php if ($lot && !$unavailable): ?>
        <section class="ps-card ps-lot">
            <div><h2>Lote <?= $h($lot['numero']) ?></h2><p><?= $h($lot['hospital']) ?> · <?= $h($lot['seguradora']) ?> · Competência <?= $h(substr($lot['competencia'],5,2).'/'.substr($lot['competencia'],0,4)) ?></p></div>
            <div class="ps-totals"><span>Contas<strong><?= count($accounts) ?></strong></span><span>Cobrado<strong><?= $money(array_sum(array_column($accounts,'cobrado'))) ?></strong></span><span>Glosado<strong><?= $money(array_sum(array_column($accounts,'glosado'))) ?></strong></span><span>Liberado<strong><?= $money(array_sum(array_column($accounts,'liberado'))) ?></strong></span></div>
        </section>
        <?php if ($editing): ?>
        <section class="ps-card">
            <div class="ps-section-head"><h2><?= $account ? 'Conta '.$h($account['numero']) : 'Nova conta de PS' ?></h2><a href="<?= $h($base.'?lote='.$lotId) ?>">Voltar às contas do lote</a></div>
            <form method="post" action="<?= $h($base.'?lote='.$lotId.($accountId?'&conta='.$accountId:'&nova=1')) ?>" id="ps-account-form">
                <input type="hidden" name="csrf" value="<?= $h($_SESSION['ps_csrf']) ?>"><input type="hidden" name="action" value="save_account"><input type="hidden" name="type" value="<?= $accountId ? 'update' : 'create' ?>"><input type="hidden" name="versao" value="<?= (int)$form['versao'] ?>">
                <fieldset <?= !$writable ? 'disabled' : '' ?>>
                    <legend>Atendimento e cobrança</legend>
                    <div class="ps-fields ps-fields--attendance">
                        <?php foreach (['numero'=>['Número da conta',60],'paciente'=>['Paciente',180],'matricula'=>['Matrícula',80],'atendimento'=>['Número do atendimento',80]] as $key=>$info): ?><label><?= $info[0] ?><input name="<?= $key ?>" maxlength="<?= $info[1] ?>" value="<?= $h($form[$key]) ?>" <?= $key!=='matricula'?'required':'' ?>></label><?php endforeach; ?>
                        <label>Entrada<input type="datetime-local" name="entrada" value="<?= $h($form['entrada']) ?>" required></label>
                        <label>Saída<input type="datetime-local" name="saida" value="<?= $h($form['saida']) ?>"></label>
                        <label>Modalidade<select name="modalidade" id="ps-mode"><option value="aberta" <?= $form['modalidade']==='aberta'?'selected':'' ?>>Conta aberta</option><option value="pacote" <?= $form['modalidade']==='pacote'?'selected':'' ?>>Pacote</option></select></label>
                        <label id="ps-package-label">Pacote contratado<input name="pacote" id="ps-package" maxlength="180" value="<?= $h($form['pacote']) ?>" placeholder="Nome ou código do pacote"></label>
                    </div>
                    <div class="ps-section-head"><h3>Valores por bloco</h3></div>
                    <p class="ps-hint">Preencha os totais de cada bloco. Use “Detalhar” para observações ou diferentes motivos de glosa. Em pacote, os demais blocos representam cobranças adicionais.</p>
                    <div class="ps-table-wrap"><table class="ps-blocks"><thead><tr><th>Bloco</th><th>Cobrado</th><th>Glosado</th><th>Liberado</th><th>Motivo da glosa</th><th>Detalhamento</th></tr></thead><tbody>
                        <?php foreach (ProntoSocorroAuditService::BLOCKS as $key=>$label): $block=$blocks[$key]; ?>
                        <tr data-block="<?= $key ?>" <?= $key==='pacote' && $form['modalidade']!=='pacote'?'hidden':'' ?>>
                            <th scope="row"><?= $label ?></th>
                            <td><input name="blocos[<?= $key ?>][cobrado]" inputmode="decimal" value="<?= $h($block['cobrado']) ?>" data-charged aria-label="Cobrado — <?= $label ?>"></td>
                            <td><input name="blocos[<?= $key ?>][glosa]" inputmode="decimal" value="<?= $h($block['glosa']) ?>" data-denied aria-label="Glosado — <?= $label ?>"></td>
                            <td><output data-approved aria-label="Liberado — <?= $label ?>">—</output></td>
                            <td><select name="blocos[<?= $key ?>][motivo]" data-reason aria-label="Motivo da glosa — <?= $label ?>">
                                <option value="">Selecione o motivo</option>
                                <?php $savedReason = (string)$block['motivo']; ?>
                                <?php foreach (ProntoSocorroAuditService::GLOSA_REASONS as $reason): ?>
                                    <option value="<?= $h($reason) ?>" <?= $savedReason === $reason ? 'selected' : '' ?>><?= $h($reason) ?></option>
                                <?php endforeach; ?>
                                <?php if ($savedReason !== '' && !in_array($savedReason, ProntoSocorroAuditService::GLOSA_REASONS, true)): ?>
                                    <option value="<?= $h($savedReason) ?>" selected><?= $h($savedReason) ?></option>
                                <?php endif; ?>
                            </select></td>
                            <td><details class="ps-block-details"><summary><?= $block['detalhes'] !== '' ? 'Ver detalhes' : 'Detalhar' ?></summary><label>Detalhes — <?= $label ?><textarea name="blocos[<?= $key ?>][detalhes]" rows="3" maxlength="100000" placeholder="Descreva os itens ou os motivos e valores das glosas, se necessário."><?= $h($block['detalhes']) ?></textarea></label></details></td>
                        </tr><?php endforeach; ?>
                    </tbody></table></div>
                    <div class="ps-totals ps-totals--preview" aria-live="polite"><span>Cobrado<strong id="ps-charged">—</strong></span><span>Glosado<strong id="ps-denied">—</strong></span><span>Liberado<strong id="ps-approved">—</strong></span></div>
                    <div class="ps-fields ps-fields--notes"><label>Observações da auditoria<textarea name="observacao" rows="3" maxlength="5000"><?= $h($form['observacao']) ?></textarea></label><label>Situação<select name="status"><option value="em_auditoria" <?= $form['status']==='em_auditoria'?'selected':'' ?>>Em auditoria</option><option value="finalizada" <?= $form['status']==='finalizada'?'selected':'' ?>>Auditada</option></select></label></div>
                    <?php if ($writable): ?><div class="ps-actions"><button class="btn btn-primary" type="submit">Salvar auditoria</button><a href="<?= $h($base.'?lote='.$lotId) ?>">Cancelar</a></div><?php endif; ?>
                </fieldset>
            </form>
            <?php if ($history): ?><details class="ps-history"><summary>Histórico de alterações</summary><ul><?php foreach ($history as $entry): ?><li>Versão <?= (int)$entry['versao'] ?> · <?= $h($entry['registrado_em']) ?> · <?= $h($entry['usuario'] ?? 'Usuário indisponível') ?></li><?php endforeach; ?></ul></details><?php endif; ?>
        </section>
        <?php else: ?>
        <section class="ps-card"><h2>Contas do lote</h2><div class="ps-table-wrap"><table><thead><tr><th>Conta / Atendimento</th><th>Paciente</th><th>Modalidade</th><th>Situação</th><th>Cobrado</th><th>Glosado</th><th>Liberado</th><th>Ação</th></tr></thead><tbody>
            <?php foreach ($accounts as $r): ?><tr><td><?= $h($r['numero']) ?><small><?= $h($r['atendimento']) ?></small></td><td><?= $h($r['paciente']) ?></td><td><?= $r['modalidade']==='pacote'?'Pacote':'Conta aberta' ?></td><td><span class="ps-status <?= $r['status']==='finalizada'?'ps-status--done':'' ?>"><?= $r['status']==='finalizada'?'Auditada':'Em auditoria' ?></span></td><td><?= $money($r['cobrado']) ?></td><td><?= $money($r['glosado']) ?></td><td><?= $money($r['liberado']) ?></td><td><a href="<?= $h($base.'?lote='.$lotId.'&conta='.$r['id']) ?>"><?= $canEdit?'Auditar':'Visualizar' ?></a></td></tr><?php endforeach; ?>
            <?php if (!$accounts): ?><tr><td colspan="8" class="ps-empty">Este lote ainda não tem contas. Cadastre a primeira em “Nova conta”.</td></tr><?php endif; ?>
        </tbody></table></div></section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<script src="<?= $h($BASE_URL) ?>js/contas_ps.js?v=<?= filemtime(__DIR__ . '/js/contas_ps.js') ?>" defer></script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
