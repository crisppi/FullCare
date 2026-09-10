<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../ajax/_auth_scope.php';
require_once __DIR__ . '/../app/services/ProntoSocorroAuditService.php';
$checks = 0;
function verify($ok, string $name): void { global $checks; if (!$ok) throw new RuntimeException($name); $checks++; echo "[ok] $name\n"; }
function rejects(callable $fn, string $name): void { try { $fn(); } catch (DomainException $e) { verify(true,$name); return; } throw new RuntimeException('Não rejeitou: '.$name); }
$data = ['numero'=>'TEST-PS','paciente'=>'Paciente sintético PS','matricula'=>'TEST','atendimento'=>'TEST-AT','entrada'=>'2026-09-09T10:00','saida'=>'2026-09-09T11:00','modalidade'=>'aberta','pacote'=>'','status'=>'em_auditoria','observacao'=>'Teste automatizado', 'items'=>[['categoria'=>'material','descricao'=>'Item sintético','quantidade'=>3,'unitario'=>'0,10','glosa'=>'0,10','motivo'=>'Divergência sintética']]];
$v = ProntoSocorroAuditService::validateAccount($data);
verify($v['cobrado']==='0.30' && $v['glosado']==='0.10' && $v['liberado']==='0.20','Cálculo exato em centavos');
foreach (['-1','1.234,56','abc','1.234',[], '1e3'] as $bad) rejects(fn()=>ProntoSocorroAuditService::cents($bad),'Rejeita moeda inválida');
$bad=$data; $bad['items'][0]['glosa']='0.31'; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Glosa acima do cobrado');
$bad=$data; $bad['items'][0]['motivo']=''; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Glosa sem justificativa');
$bad=$data; $bad['saida']='2026-09-09T09:00'; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Saída antes da entrada');
$bad=$data; $bad['entrada']='2026-02-30T10:00'; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Data inexistente');
$bad=$data; $bad['items']=[]; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Conta sem itens');
$bad=$data; $bad['items'][0]['quantidade']='1.5'; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Quantidade fracionária');
$bad=$data; $bad['modalidade']='pacote'; $bad['pacote']='Pacote sintético'; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Pacote sem item do pacote');
$package=$data; $package['modalidade']='pacote'; $package['pacote']='Pacote sintético'; $package['items'][0]=['categoria'=>'pacote','descricao'=>'Pacote sintético','quantidade'=>1,'unitario'=>'100.00','glosa'=>'10.00','motivo'=>'Divergência sintética'];
verify(ProntoSocorroAuditService::validateAccount($package)['liberado']==='90.00','Conta pacote');
$bad=$package; $bad['modalidade']='aberta'; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Conta aberta com item pacote');
$bad=$package; $bad['items'][]=$package['items'][0]; rejects(fn()=>ProntoSocorroAuditService::validateAccount($bad),'Pacote duplicado');
$byBlock=$data; unset($byBlock['items']);
$byBlock['blocos']=['matmed'=>['cobrado'=>'200,10','glosa'=>'20,10','motivo'=>'Quantidade divergente','detalhes'=>'Material: glosa de 10,00; medicamento: glosa de 10,10.'],'honorario'=>['cobrado'=>'100','glosa'=>'0'],'exame'=>['cobrado'=>'50','glosa'=>'0'],'outros'=>['cobrado'=>'','glosa'=>'']];
$validatedBlocks=ProntoSocorroAuditService::validateAccount($byBlock);
verify($validatedBlocks['cobrado']==='350.10' && $validatedBlocks['liberado']==='330.00' && count($validatedBlocks['items'])===3,'Blocos somam uma única vez e ignoram linhas vazias');
$roundtrip=ProntoSocorroAuditService::toBlocks($validatedBlocks['items']);
verify($roundtrip['matmed']['detalhes']===$byBlock['blocos']['matmed']['detalhes'] && $roundtrip['matmed']['glosa']==='20.10','Detalhamento preservado sem alterar totais');
$legacyItems=$v['items']; $legacyItems[]=['categoria'=>'medicamento','descricao'=>'Medicamento sintético','quantidade'=>2,'unitario'=>'10.00','glosa'=>'1.00','motivo'=>'Valor divergente do contratado'];
$legacyBlocks=ProntoSocorroAuditService::toBlocks($legacyItems);
verify($legacyBlocks['matmed']['cobrado']==='20.30' && $legacyBlocks['matmed']['glosa']==='1.10','Agrupa materiais e medicamentos de contas existentes');
verify($legacyBlocks['matmed']['motivo']==='Outros' && str_contains($legacyBlocks['matmed']['detalhes'],'Divergência sintética') && str_contains($legacyBlocks['matmed']['detalhes'],'Valor divergente do contratado'),'Preserva diferentes motivos e descrições antigas');
$invalidBlock=$byBlock; $invalidBlock['blocos']['matmed']['glosa']='999'; rejects(fn()=>ProntoSocorroAuditService::validateAccount($invalidBlock),'Rejeita excesso de glosa por bloco');
$invalidBlock=$byBlock; $invalidBlock['blocos']['matmed']['motivo']=''; rejects(fn()=>ProntoSocorroAuditService::validateAccount($invalidBlock),'Motivo obrigatório no bloco glosado');
$invalidBlock=$byBlock; $invalidBlock['blocos']=['exame'=>['cobrado'=>'','glosa'=>'']]; rejects(fn()=>ProntoSocorroAuditService::validateAccount($invalidBlock),'Rejeita conta sem blocos preenchidos');
$invalidBlock=$byBlock; $invalidBlock['blocos']['pacote']=['cobrado'=>'100','glosa'=>'0']; rejects(fn()=>ProntoSocorroAuditService::validateAccount($invalidBlock),'Conta aberta não aceita bloco pacote');
$packageBlocks=$byBlock; $packageBlocks['modalidade']='pacote'; $packageBlocks['pacote']='Pacote PS'; $packageBlocks['blocos']['pacote']=['cobrado'=>'500','glosa'=>'50','motivo'=>'Valor divergente do contratado'];
verify(ProntoSocorroAuditService::validateAccount($packageBlocks)['liberado']==='780.00','Pacote soma adicionais e glosas dos blocos');
require_once __DIR__ . '/../app/security/FullCareAccess.php';
$_SERVER['SCRIPT_NAME']='/FullCare/contas_ps.php'; $_SERVER['REQUEST_URI']='/FullCare/contas_ps.php'; $_SERVER['REQUEST_METHOD']='GET';
verify(FullCareAccess::currentRequestAccess()===['module'=>'contas','action'=>'view'],'Acesso direto exige permissão de Contas');
$_SERVER['REQUEST_METHOD']='POST'; $_POST['type']='update';
verify(FullCareAccess::currentRequestAccess()===['module'=>'contas','action'=>'edit'],'Edição usa permissão edit');
$_POST['type']='create';
verify(FullCareAccess::currentRequestAccess()===['module'=>'contas','action'=>'create'],'Cadastro usa permissão create');
unset($_POST['type']);
if (in_array('--database',$argv,true)) {
    require_once __DIR__ . '/../db.php';
    ProntoSocorroAuditService::migrate($conn);
    require_once __DIR__ . '/../app/version.php';
    verify((bool)preg_match('/^v?\d+(?:\.\d+){1,3}$/',app_latest_version($conn)),'Migração não altera versão de exibição');
    $ctx=['user_id'=>0,'is_diretoria'=>true,'is_system_admin'=>false,'is_seguradora'=>false,'cargo_norm'=>'diretoria','seguradora_id'=>0];
    $svc=new ProntoSocorroAuditService($conn,$ctx);
    $opts=$svc->options();
    if (!$opts['hospitais'] || !$opts['seguradoras']) throw new RuntimeException('É necessário ao menos um hospital e uma seguradora.');
    $conn->beginTransaction();
    try {
        $lotData=['numero'=>'TEST-PS-'.bin2hex(random_bytes(6)),'hospital_id'=>$opts['hospitais'][0]['id'],'seguradora_id'=>$opts['seguradoras'][0]['id'],'competencia'=>'2026-09','recebido_em'=>'2026-09-09'];
        $lot=$svc->createLot($lotData); verify($svc->lot($lot)['numero']===$lotData['numero'],'Criar e ler lote');
        $id=$svc->saveAccount($lot,0,$data); $saved=$svc->account($id);
        verify($saved['liberado']==='0.20' && count($saved['items'])===1,'Persistência da conta e itens');
        $package['numero']='TEST-PACKAGE'; $pid=$svc->saveAccount($lot,0,$package); verify($svc->account($pid)['pacote']==='Pacote sintético','Persistência de pacote');
        $changed=$data; $changed['versao']=1; $changed['status']='finalizada'; $changed['items'][0]['glosa']='0.20';
        $svc->saveAccount($lot,$id,$changed); verify($svc->account($id)['liberado']==='0.10' && count($svc->history($id))===2,'Editar, finalizar e preservar histórico');
        rejects(fn()=>$svc->saveAccount($lot,$id,$changed),'Evita sobrescrever edição concorrente');
        $rows=$svc->lots($lotData['numero'])['rows']; verify(count($rows)===1 && (int)$rows[0]['contas']===2 && (int)$rows[0]['finalizadas']===1 && $rows[0]['liberado']==='90.10','Totais e situação por lote');
        $blockEdit=$byBlock; $blockEdit['versao']=2;
        $svc->saveAccount($lot,$id,$blockEdit);
        $reloaded=$svc->account($id);
        verify($reloaded['liberado']==='330.00' && $reloaded['items'][0]['detalhes']===$byBlock['blocos']['matmed']['detalhes'],'Grava e reabre blocos com detalhamento');
        verify(count($svc->history($id))===3,'Conversão para blocos mantém histórico das versões anteriores');
        $outsider=new ProntoSocorroAuditService($conn,['user_id'=>0,'cargo_norm'=>'auditor','is_diretoria'=>false,'is_system_admin'=>false,'is_seguradora'=>false,'seguradora_id'=>0]);
        rejects(fn()=>$outsider->account($id),'Escopo impede ler conta de outro hospital');
        rejects(fn()=>$outsider->saveAccount($lot,0,$data),'Escopo impede gravar conta de outro hospital');
        verify($outsider->lots($lotData['numero'])['count']===0,'Listagem respeita escopo');
        $insurer=new ProntoSocorroAuditService($conn,['user_id'=>0,'cargo_norm'=>'seguradora','is_diretoria'=>false,'is_system_admin'=>false,'is_seguradora'=>true,'seguradora_id'=>-1]);
        rejects(fn()=>$insurer->lot($lot),'Escopo por seguradora');
        $lotData['numero'].='-2'; $lot2=$svc->createLot($lotData);
        rejects(fn()=>$svc->saveAccount($lot2,$id,$changed),'Conta não pode ser alterada por outro lote');
        try { $svc->saveAccount($lot,0,$data); throw new RuntimeException('Conta duplicada aceita'); } catch (PDOException $e) { verify((int)$e->errorInfo[1]===1062,'Número da conta único no lote'); }
    } finally { $conn->rollBack(); }
    verify($svc->lots($lotData['numero'])['count']===0,'Dados sintéticos revertidos');
}
echo "$checks verificações concluídas.\n";
