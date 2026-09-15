<?php
require_once __DIR__.'/check_logado.php';
require_once __DIR__.'/globals.php';
require_once __DIR__.'/ajax/_auth_scope.php';
require_once __DIR__.'/app/services/HomeCareExtensionService.php';
FullCareAccess::enforce($conn,$BASE_URL,'cuidado_continuado','view');
$canCreate=FullCareAccess::can($conn,'cuidado_continuado','create');
$canDecide=FullCareAccess::can($conn,'cuidado_continuado','edit');
$service=new HomeCareExtensionService($conn,ajax_user_context($conn));
$base=$BASE_URL.'cuidado-continuado/home-care/prorrogacoes';
$h=static fn($v)=>htmlspecialchars(is_scalar($v)?(string)$v:'',ENT_QUOTES,'UTF-8');
$date=static fn($v)=>$v?date('d/m/Y',strtotime((string)$v)):'—';
$case=max(0,(int)($_GET['caso']??0)); $requestId=max(0,(int)($_GET['solicitacao']??0));
$new=isset($_GET['nova']); $error=''; $context=null; $plans=[]; $requests=[]; $selected=null;
$search=is_string($_GET['q']??'')?mb_substr(trim($_GET['q']??''),0,100):'';
$_SESSION['hc_extension_csrf']=$_SESSION['hc_extension_csrf']??bin2hex(random_bytes(32));
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['hc_extension_csrf'],$_POST['csrf'])) throw new DomainException('Formulário expirado. Atualize a página e tente novamente.');
        $action=$_POST['acao']??'';
        if ($action==='initial') {
            FullCareAccess::enforce($conn,$BASE_URL,'cuidado_continuado','create');
            $service->createInitial($case,$_POST);
            $_SESSION['hc_extension_notice']='Plano inicial autorizado registrado.';
            $url=$base.'?caso='.$case;
        } elseif ($action==='request') {
            FullCareAccess::enforce($conn,$BASE_URL,'cuidado_continuado','create');
            $id=$service->request($case,(int)($_POST['plano_id']??0),$_POST);
            $_SESSION['hc_extension_notice']='Solicitação de prorrogação registrada para análise.';
            $url=$base.'?caso='.$case.'&solicitacao='.$id;
        } elseif ($action==='decide') {
            FullCareAccess::enforce($conn,$BASE_URL,'cuidado_continuado','edit');
            $service->decide($case,$requestId,$_POST);
            $_SESSION['hc_extension_notice']='Decisão registrada. O histórico foi atualizado.';
            $url=$base.'?caso='.$case.'&solicitacao='.$requestId;
        } else throw new DomainException('Ação inválida.');
        header('Location: '.$url,true,303); exit;
    }
} catch (DomainException $e) { $error=$e->getMessage(); }
catch (Throwable $e) { error_log('[HC PRORROGACAO] '.$e->getMessage()); $error='Não foi possível salvar. Tente novamente.'; }
$listing=['rows'=>[],'count'=>0,'page'=>1]; $unavailable=false;
try {
    if ($case) {
        $context=$service->context($case); $plans=$service->plans($case); $requests=$service->requests($case);
        if ($requestId) {
            foreach ($requests as $r) if ((int)$r['id']===$requestId) $selected=$r;
            if (!$selected) throw new DomainException('Solicitação não encontrada neste caso.');
        }
    } else $listing=$service->cases($search,max(1,(int)($_GET['pagina']??1)));
} catch (DomainException $e) { $error=$e->getMessage(); $context=null; http_response_code(404); }
catch (Throwable $e) { error_log('[HC PRORROGACAO] '.$e->getMessage()); $error='As prorrogações de home care não estão disponíveis neste ambiente.'; $unavailable=true; }
$latest=$plans[0]??null;
$pending=null; foreach ($requests as $r) if ($r['status']==='pendente') { $pending=$r; break; }
$active=$context && in_array($context['status_hc'],['implantado','implantacao'],true);
$action=(!$latest && $context)?'initial':(($new && !$pending && $active)?'request':null);
if ($selected && $selected['status']==='pendente' && $canDecide) $action='decide';
if ($action!=='decide' && (!$canCreate || !$active)) $action=null;
$values=['inicio'=>'','fim'=>'','fornecedor'=>$context['fornecedor_hc']??'','modalidade'=>$context['modalidade_aprovada_hc']??$context['modalidade_sugerida_hc']??'','plano'=>$context['plano_transicao_hc']??'','equipe'=>'','materiais'=>'','equipamentos'=>$context['equipamentos_hc']??'','autorizacao'=>'','justificativa'=>'','parecer'=>'','decisao'=>'aprovada'];
if ($action==='request') {
    $values=array_merge($values,array_intersect_key($latest,$values));
    $values['inicio']=(new DateTimeImmutable($latest['fim']))->modify('+1 day')->format('Y-m-d');
    $values['fim']=''; $values['autorizacao']='';
}
if ($action==='decide') $values=array_merge($values,array_intersect_key($selected,$values));
if ($error && is_string($_POST['acao']??null) && $_POST['acao']===$action) $values=array_merge($values,array_intersect_key($_POST,$values));
$fields=['inicio'=>'Início','fim'=>'Fim','fornecedor'=>'Prestador / fornecedor','modalidade'=>'Modalidade','plano'=>'Plano de cuidado / serviços','equipe'=>'Equipe e frequência dos atendimentos','materiais'=>'Materiais e medicamentos','equipamentos'=>'Equipamentos'];
$source=null; $authorized=null;
if ($selected) foreach ($plans as $plan) { if ((int)$plan['id']===(int)$selected['plano_id']) $source=$plan; if ((int)($plan['solicitacao_id']??0)===$requestId) $authorized=$plan; }
$notice=$_SESSION['hc_extension_notice']??''; unset($_SESSION['hc_extension_notice']);
require_once __DIR__.'/templates/header.php';
?>
<link rel="stylesheet" href="<?= $h($BASE_URL) ?>css/home_care_prorrogacao.css?v=<?= filemtime(__DIR__.'/css/home_care_prorrogacao.css') ?>">
<main class="hcx-page">
    <header class="fc-module-header fc-module-header--cuidado">
        <div class="fc-module-header__copy"><p class="fc-module-header__kicker">Cuidado continuado · Home care</p><h1 class="fc-module-header__title">Prorrogações de home care</h1><p class="fc-module-header__subtitle">Planos autorizados, solicitações de continuidade e decisões por período.</p></div>
        <div class="fc-module-header__actions"><a class="btn btn-light" href="<?= $h($BASE_URL) ?>cuidado-continuado/home-care/pacientes">Pacientes em home care</a><a class="btn btn-light" href="<?= $h($BASE_URL.'cuidado-continuado/home-care') ?>">Gestão de home care</a><?php if ($case): ?><a class="btn btn-light" href="<?= $h($base) ?>">Todos os casos</a><?php endif; ?></div>
    </header>
    <?php if ($error): ?><div class="hcx-alert hcx-alert--error" role="alert"><?= $h($error) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="hcx-alert" role="status"><?= $h($notice) ?></div><?php endif; ?>
    <?php if (!$case && !$unavailable): ?>
    <section class="hcx-card">
        <form method="get" class="hcx-actions"><label>Paciente ou hospital<input name="q" type="search" value="<?= $h($search) ?>" maxlength="100" placeholder="Pesquisar caso"></label><button class="btn btn-primary" type="submit">Filtrar</button><a href="<?= $h($base) ?>">Limpar</a></form>
        <div class="hcx-table-wrap"><table><thead><tr><th>Paciente / caso</th><th>Hospital / seguradora</th><th>Situação do home care</th><th>Último período autorizado até</th><th>Pendentes</th><th>Ação</th></tr></thead><tbody>
            <?php foreach ($listing['rows'] as $r): ?><tr><td><?= $h($r['nome_pac']) ?><small>#<?= (int)$r['id_internacao'] ?></small></td><td><?= $h($r['nome_hosp']) ?><small><?= $h($r['seguradora_seg']) ?></small></td><td><?= $h(['implantado'=>'Implantado','implantacao'=>'Em implantação','descontinuado'=>'Descontinuado','em_avaliacao'=>'Em avaliação','negado'=>'Negado','elegivel'=>'Elegível'][$r['status_hc']]??str_replace('_',' ',(string)$r['status_hc'])) ?></td><td><?= $date($r['autorizado_ate']) ?><?php if ($r['autorizado_ate'] && $r['autorizado_ate']<date('Y-m-d')): ?><small class="hcx-expired">Período vencido</small><?php endif; ?></td><td><?= (int)$r['pendentes'] ?></td><td><a href="<?= $h($base.'?caso='.$r['id_internacao']) ?>">Abrir</a></td></tr><?php endforeach; ?>
            <?php if (!$listing['rows']): ?><tr><td colspan="6" class="hcx-empty">Nenhum caso encontrado. A avaliação do paciente é registrada na Gestão de home care.</td></tr><?php endif; ?>
        </tbody></table></div>
        <nav class="hcx-actions" aria-label="Paginação"><span><?= (int)$listing['count'] ?> caso(s) · Página <?= (int)$listing['page'] ?></span><?php if ($listing['page']>1): ?><a href="<?= $h($base.'?'.http_build_query(['q'=>$search,'pagina'=>$listing['page']-1])) ?>">Anterior</a><?php endif; ?><?php if ($listing['page']*25<$listing['count']): ?><a href="<?= $h($base.'?'.http_build_query(['q'=>$search,'pagina'=>$listing['page']+1])) ?>">Próxima</a><?php endif; ?></nav>
    </section>
    <?php endif; ?>
    <?php if ($context && !$unavailable): ?>
    <section class="hcx-card hcx-heading"><div><h2><?= $h($context['nome_pac']) ?></h2><p><?= $h($context['nome_hosp']) ?> · <?= $h($context['seguradora_seg']) ?> · Caso #<?= $case ?></p></div><div class="hcx-actions"><a href="<?= $h($BASE_URL.'cuidado-continuado/home-care/avaliar/'.$case) ?>">Avaliação do caso</a><?php if ($latest && $canCreate && $active && !$pending): ?><a class="btn btn-primary" href="<?= $h($base.'?caso='.$case.'&nova=1') ?>">Solicitar prorrogação</a><?php endif; ?><?php if ($pending): ?><a class="btn btn-outline-primary" href="<?= $h($base.'?caso='.$case.'&solicitacao='.$pending['id']) ?>">Abrir solicitação pendente</a><?php endif; ?></div></section>
    <?php if (!$active): ?><p class="hcx-alert">Novos períodos podem ser registrados quando o caso estiver implantado ou em implantação.</p><?php endif; ?>
    <?php if ($latest && !$selected): ?>
    <section class="hcx-card"><h2>Último plano autorizado</h2><div class="hcx-summary"><span>Período<strong><?= $date($latest['inicio']) ?> a <?= $date($latest['fim']) ?></strong></span><span>Autorização<strong><?= $h($latest['autorizacao']) ?></strong></span><span>Fornecedor<strong><?= $h($latest['fornecedor']) ?></strong></span><span>Modalidade<strong><?= $h(HomeCareExtensionService::MODES[$latest['modalidade']]??$latest['modalidade']) ?></strong></span></div><details><summary>Ver plano e recursos</summary><dl class="hcx-plan-details"><?php foreach (['plano','equipe','materiais','equipamentos'] as $key): ?><div><dt><?= $fields[$key] ?></dt><dd><?= $h($latest[$key]?:'—') ?></dd></div><?php endforeach; ?></dl></details></section>
    <?php endif; ?>
    <?php if ($selected): ?>
    <section class="hcx-card"><div class="hcx-heading"><h2>Solicitação #<?= $requestId ?> · <?= HomeCareExtensionService::STATUSES[$selected['status']] ?></h2><a href="<?= $h($base.'?caso='.$case) ?>">Voltar ao caso</a></div><p class="hcx-muted">Solicitada por <?= $h($selected['solicitante']??'Usuário indisponível') ?> · <?= $h($selected['solicitado_em']) ?></p><h3>Justificativa</h3><p class="hcx-text"><?= $h($selected['justificativa']) ?></p>
        <div class="hcx-table-wrap"><table class="hcx-comparison"><thead><tr><th>Plano</th><th>Autorização de referência</th><th>Solicitado</th><?php if ($authorized): ?><th>Autorizado nesta prorrogação</th><?php endif; ?></tr></thead><tbody><?php foreach ($fields as $key=>$label): ?><tr><th scope="row"><?= $label ?></th><?php foreach (array_filter([$source,$selected,$authorized]) as $column): ?><td><?= in_array($key,['inicio','fim'],true)?$date($column[$key]):$h($key==='modalidade'?(HomeCareExtensionService::MODES[$column[$key]]??$column[$key]):($column[$key]?:'—')) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
        <?php if ($selected['status']!=='pendente'): ?><h3>Parecer</h3><p class="hcx-text"><?= $h($selected['parecer']) ?></p><p class="hcx-muted">Decisão registrada por <?= $h($selected['decisor']??'Usuário indisponível') ?> · <?= $h($selected['decidido_em']) ?><?= $authorized?' · Autorização: '.$h($authorized['autorizacao']):'' ?></p><?php endif; ?>
    </section>
    <?php endif; ?>
    <?php if ($action): ?>
    <section class="hcx-card"><h2><?= ['initial'=>'Registrar plano inicial autorizado','request'=>'Solicitar prorrogação','decide'=>'Registrar decisão'][$action] ?></h2>
        <?php if ($action==='initial'): ?><p class="hcx-muted">Informe o período e os serviços já autorizados. Esse será o plano de referência para as prorrogações.</p><?php endif; ?>
        <form method="post" id="hcx-form" action="<?= $h($base.'?caso='.$case.($action==='request'?'&nova=1':($action==='decide'?'&solicitacao='.$requestId:''))) ?>">
            <input type="hidden" name="csrf" value="<?= $h($_SESSION['hc_extension_csrf']) ?>"><input type="hidden" name="acao" value="<?= $action ?>"><input type="hidden" name="type" value="<?= $action==='decide'?'update':'create' ?>"><input type="hidden" name="plano_id" value="<?= (int)($latest['id']??0) ?>"><input type="hidden" name="versao" value="<?= (int)($selected['versao']??0) ?>">
            <?php if ($action==='decide'): ?><div class="hcx-grid hcx-grid--decision"><label>Decisão<select name="decisao" id="hcx-decision"><?php foreach (['aprovada','parcial','negada'] as $status): ?><option value="<?= $status ?>" <?= $values['decisao']===$status?'selected':'' ?>><?= HomeCareExtensionService::STATUSES[$status] ?></option><?php endforeach; ?></select></label><label>Parecer / justificativa da decisão<textarea name="parecer" rows="2" maxlength="5000" required><?= $h($values['parecer']) ?></textarea></label></div><p class="hcx-muted">Na aprovação parcial, ajuste abaixo o período e os serviços autorizados. O pedido original fica preservado.</p><?php endif; ?>
            <fieldset id="hcx-plan-fields"><legend><?= $action==='request'?'Plano solicitado':'Plano autorizado' ?></legend><div class="hcx-grid">
                <label>Início do período<input type="date" name="inicio" value="<?= $h($values['inicio']) ?>" required></label><label>Fim do período<input type="date" name="fim" value="<?= $h($values['fim']) ?>" required></label><label>Prestador / fornecedor<input name="fornecedor" maxlength="120" value="<?= $h($values['fornecedor']) ?>" required></label><label>Modalidade<select name="modalidade" required><option value="">Selecione</option><?php foreach (HomeCareExtensionService::MODES as $key=>$label): ?><option value="<?= $key ?>" <?= $values['modalidade']===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
                <?php if ($action!=='request'): ?><label>Número da autorização / protocolo<input name="autorizacao" maxlength="100" value="<?= $h($values['autorizacao']) ?>" required></label><?php endif; ?>
            </div><div class="hcx-grid hcx-grid--care"><?php foreach (['plano','equipe','materiais','equipamentos'] as $key): ?><label><?= $fields[$key] ?><textarea name="<?= $key ?>" rows="3" maxlength="5000" <?= in_array($key,['plano','equipe'],true)?'required':'' ?>><?= $h($values[$key]) ?></textarea></label><?php endforeach; ?></div></fieldset>
            <?php if ($action==='request'): ?><label>Justificativa da prorrogação<textarea name="justificativa" rows="3" maxlength="5000" required><?= $h($values['justificativa']) ?></textarea></label><?php endif; ?>
            <div class="hcx-actions"><button type="submit" class="btn btn-primary"><?= ['initial'=>'Registrar plano','request'=>'Enviar para análise','decide'=>'Registrar decisão'][$action] ?></button><a href="<?= $h($base.'?caso='.$case) ?>">Cancelar</a></div>
        </form>
    </section>
    <?php endif; ?>
    <section class="hcx-card"><h2>Histórico de prorrogações</h2><div class="hcx-table-wrap"><table><thead><tr><th>Solicitação</th><th>Período solicitado</th><th>Situação</th><th>Solicitante</th><th>Ação</th></tr></thead><tbody><?php foreach ($requests as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><?= $date($r['inicio']) ?> a <?= $date($r['fim']) ?></td><td><span class="hcx-badge hcx-badge--<?= $h($r['status']) ?>"><?= HomeCareExtensionService::STATUSES[$r['status']] ?></span></td><td><?= $h($r['solicitante']??'Usuário indisponível') ?></td><td><a href="<?= $h($base.'?caso='.$case.'&solicitacao='.$r['id']) ?>">Abrir</a></td></tr><?php endforeach; ?><?php if (!$requests): ?><tr><td colspan="5" class="hcx-empty">Nenhuma prorrogação registrada.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php endif; ?>
</main>
<script src="<?= $h($BASE_URL) ?>js/home_care_prorrogacao.js?v=<?= filemtime(__DIR__.'/js/home_care_prorrogacao.js') ?>" defer></script>
<?php require_once __DIR__.'/templates/footer.php'; ?>
