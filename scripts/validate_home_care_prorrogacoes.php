<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(403); exit; }
require_once __DIR__.'/../ajax/_auth_scope.php';
require_once __DIR__.'/../app/services/HomeCareExtensionService.php';
$checks=0;
function checkHc(bool $ok,string $label): void { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; echo "[ok] $label\n"; }
function rejectHc(callable $fn,string $label): void { try { $fn(); } catch (DomainException $e) { checkHc(true,$label); return; } throw new RuntimeException('Não rejeitou: '.$label); }
$plan=['inicio'=>'2026-09-01','fim'=>'2026-09-30','fornecedor'=>'Prestador sintético','modalidade'=>'atendimento_multiprofissional','plano'=>'Plano sintético de teste','equipe'=>'Equipe sintética / frequência registrada','materiais'=>'Materiais de teste','equipamentos'=>'Equipamento de teste','autorizacao'=>'TEST-HC'];
checkHc(HomeCareExtensionService::validatePlan($plan)['fim']==='2026-09-30','Plano e período válidos');
$bad=$plan; $bad['fim']='2026-08-31'; rejectHc(fn()=>HomeCareExtensionService::validatePlan($bad),'Período invertido');
$bad=$plan; $bad['fim']='2026-02-30'; rejectHc(fn()=>HomeCareExtensionService::validatePlan($bad),'Data inexistente');
$bad=$plan; $bad['modalidade']='invalida'; rejectHc(fn()=>HomeCareExtensionService::validatePlan($bad),'Modalidade inválida');
$bad=$plan; $bad['equipe']=''; rejectHc(fn()=>HomeCareExtensionService::validatePlan($bad),'Equipe e frequência obrigatórias');
$bad=$plan; $bad['plano']=[]; rejectHc(fn()=>HomeCareExtensionService::validatePlan($bad),'Estrutura inválida');
require_once __DIR__.'/../app/security/FullCareAccess.php';
$_SERVER['SCRIPT_NAME']='/FullCare/home_care_prorrogacao.php'; $_SERVER['REQUEST_URI']='/FullCare/cuidado-continuado/home-care/prorrogacoes'; $_SERVER['REQUEST_METHOD']='GET';
checkHc(FullCareAccess::currentRequestAccess()===['module'=>'cuidado_continuado','action'=>'view'],'Permissão do módulo correto');
$_SERVER['REQUEST_METHOD']='POST'; $_POST['type']='update';
checkHc(FullCareAccess::currentRequestAccess()===['module'=>'cuidado_continuado','action'=>'edit'],'Decisão exige edição');
unset($_POST['type']);
if (in_array('--database',$argv,true)) {
    require_once __DIR__.'/../db.php';
    HomeCareExtensionService::migrate($conn);
    $case=(int)$conn->query('SELECT i.id_internacao FROM tb_internacao i JOIN tb_paciente p ON p.id_paciente=i.fk_paciente_int WHERE NOT EXISTS (SELECT 1 FROM tb_hc_plano pl WHERE pl.internacao_id=i.id_internacao) ORDER BY i.id_internacao LIMIT 1')->fetchColumn();
    if (!$case) throw new RuntimeException('Necessário um caso sem plano autorizado para o teste transacional.');
    $ctx=['user_id'=>0,'is_diretoria'=>true,'is_system_admin'=>false,'is_seguradora'=>false,'cargo_norm'=>'diretoria','seguradora_id'=>0];
    $svc=new HomeCareExtensionService($conn,$ctx);
    $conn->beginTransaction();
    try {
        $s=$conn->prepare("INSERT INTO tb_home_care_avaliacao(fk_internacao_hc,status_hc,fornecedor_hc) VALUES(?,'implantado','Prestador sintético')"); $s->execute([$case]);
        $activeCount=$svc->cases('',1,true)['count'];
        checkHc($activeCount>0,'Listagem inclui home care implantado sem exigir plano');
        $baseline=$svc->createInitial($case,$plan);
        checkHc(count($svc->plans($case))===1,'Registro do plano inicial');
        rejectHc(fn()=>$svc->createInitial($case,$plan),'Evita plano inicial duplicado');
        $proposal=$plan; $proposal['inicio']='2026-10-01'; $proposal['fim']='2026-10-31'; $proposal['justificativa']='Justificativa sintética de teste';
        $bad=$proposal; $bad['inicio']='2026-09-29'; rejectHc(fn()=>$svc->request($case,$baseline,$bad),'Impede sobreposição ao período autorizado');
        $bad=$proposal; $bad['justificativa']=''; rejectHc(fn()=>$svc->request($case,$baseline,$bad),'Justificativa obrigatória');
        $request=$svc->request($case,$baseline,$proposal);
        rejectHc(fn()=>$svc->request($case,$baseline,$proposal),'Impede solicitação pendente duplicada');
        checkHc(count($svc->plans($case))===1,'Solicitação não altera autorização vigente');
        $decision=$proposal; $decision['decisao']='aprovada'; $decision['parecer']='Parecer sintético'; $decision['versao']=1;
        $bad=$decision; $bad['fim']='2026-11-01'; rejectHc(fn()=>$svc->decide($case,$request,$bad),'Autorização não excede pedido');
        $bad=$decision; $bad['fim']='2026-10-15'; rejectHc(fn()=>$svc->decide($case,$request,$bad),'Mudança no plano exige aprovação parcial');
        $partial=$decision; $partial['decisao']='parcial'; $partial['fim']='2026-10-15'; $partial['equipe']='Equipe ajustada no teste';
        $svc->decide($case,$request,$partial);
        $plans=$svc->plans($case); $requests=$svc->requests($case);
        checkHc(count($plans)===2 && $plans[0]['fim']==='2026-10-15' && $plans[0]['equipe']==='Equipe ajustada no teste','Aprovação parcial cria plano com período e recursos autorizados');
        checkHc($requests[0]['fim']==='2026-10-31' && $requests[0]['equipe']===$proposal['equipe'] && $requests[0]['status']==='parcial','Pedido original preservado');
        rejectHc(fn()=>$svc->decide($case,$request,$partial),'Decisão não pode ser sobrescrita');
        rejectHc(fn()=>$svc->request($case,$baseline,$proposal),'Rejeita plano de referência antigo');
        $next=$proposal; $next['inicio']='2026-10-16'; $next['fim']='2026-10-31';
        $rid=$svc->request($case,(int)$plans[0]['id'],$next);
        $svc->decide($case,$rid,['decisao'=>'negada','parecer'=>'Negativa sintética','versao'=>1]);
        checkHc(count($svc->plans($case))===2 && $svc->requests($case)[0]['status']==='negada','Negativa não cria período autorizado');
        $rid=$svc->request($case,(int)$plans[0]['id'],$next);
        $approval=$next; $approval['decisao']='aprovada'; $approval['parecer']='Aprovação sintética'; $approval['versao']=1;
        $svc->decide($case,$rid,$approval);
        checkHc(count($svc->plans($case))===3 && $svc->plans($case)[0]['fim']==='2026-10-31','Aprovação integral e múltiplos ciclos');
        $outsider=new HomeCareExtensionService($conn,['user_id'=>0,'is_diretoria'=>false,'is_system_admin'=>false,'is_seguradora'=>false,'cargo_norm'=>'auditor','seguradora_id'=>0]);
        rejectHc(fn()=>$outsider->plans($case),'Escopo impede leitura de planos de outro hospital');
        rejectHc(fn()=>$outsider->request($case,$baseline,$proposal),'Escopo impede solicitação de outro hospital');
        rejectHc(fn()=>$outsider->decide($case,$rid,$approval),'Escopo impede decisão de outro hospital');
        $seg=new HomeCareExtensionService($conn,['user_id'=>0,'is_diretoria'=>false,'is_system_admin'=>false,'is_seguradora'=>true,'cargo_norm'=>'seguradora','seguradora_id'=>-1]);
        rejectHc(fn()=>$seg->context($case),'Escopo por seguradora');
        checkHc($outsider->cases('')['count']===0,'Listagem respeita escopo');
        checkHc($outsider->cases('',1,true)['count']===0,'Pacientes em home care respeitam escopo');
        $s=$conn->prepare("INSERT INTO tb_home_care_avaliacao(fk_internacao_hc,status_hc) VALUES(?,'implantacao')"); $s->execute([$case]);
        checkHc($svc->cases('',1,true)['count']===$activeCount-1,'Última avaliação em implantação remove caso dos implantados');
        $s=$conn->prepare("INSERT INTO tb_home_care_avaliacao(fk_internacao_hc,status_hc) VALUES(?,'descontinuado')"); $s->execute([$case]);
        checkHc($svc->cases('',1,true)['count']===$activeCount-1,'Caso descontinuado fica fora dos pacientes ativos');
        $next['inicio']='2026-11-01'; $next['fim']='2026-11-30';
        rejectHc(fn()=>$svc->request($case,(int)$svc->plans($case)[0]['id'],$next),'Caso descontinuado não recebe nova solicitação');
    } finally { $conn->rollBack(); }
    $s=$conn->prepare('SELECT COUNT(*) FROM tb_hc_plano WHERE internacao_id=?'); $s->execute([$case]);
    checkHc((int)$s->fetchColumn()===0,'Dados sintéticos revertidos');
}
echo "$checks verificações concluídas.\n";
