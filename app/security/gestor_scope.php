<?php
// Adaptadores de escopo para reutilizar as telas e os DAOs nativos do FullCare.
function ge_enabled(): bool { return in_array($GLOBALS['ge_profile'] ?? '', ['gestor_estipulante_med','gestor_estipulante_enf','gerente_estipulante'],true); }
function ge_writer(): bool { return ge_enabled() && $GLOBALS['ge_profile'] !== 'gerente_estipulante'; }
// Vínculo explícito (0,0,1): acesso à base compartilhada autorizado para esta conta.
function ge_base_sql(string $user): string {
    return "EXISTS (SELECT 1 FROM ge_escopo ge_base WHERE ge_base.usuario_id=$user AND ge_base.hospital_id=0 AND ge_base.estipulante_id=0 AND ge_base.todos_pacientes=1)";
}
function ge_hospital_sql(string $expr='tb_hospital.id_hospital'): string {
    if (!ge_enabled()) return '1=1';
    $uid=(int)($_SESSION['id_usuario']??0);
    return "(".ge_base_sql((string)$uid)." OR EXISTS (SELECT 1 FROM ge_escopo ge_s WHERE ge_s.usuario_id=$uid AND ge_s.hospital_id=$expr))";
}
function ge_estipulante_sql(string $expr='tb_estipulante.id_estipulante'): string {
    if (!ge_enabled()) return '1=1';
    $uid=(int)($_SESSION['id_usuario']??0);
    return "(".ge_base_sql((string)$uid)." OR EXISTS (SELECT 1 FROM ge_escopo ge_s WHERE ge_s.usuario_id=$uid AND ge_s.estipulante_id=$expr))";
}
function ge_pair_sql(string $hospital,string $patient): string {
    if (!ge_enabled()) return '1=1';
    $uid=(int)($_SESSION['id_usuario']??0);
    return "(".ge_base_sql((string)$uid)." OR EXISTS (SELECT 1 FROM ge_escopo ge_s JOIN tb_paciente ge_p ON ge_p.id_paciente=$patient WHERE ge_s.usuario_id=$uid AND ge_s.hospital_id=$hospital AND (ge_s.todos_pacientes=1 OR ge_s.estipulante_id=ge_p.fk_estipulante_pac) AND COALESCE(ge_p.deletado_pac,'n')<>'s'))";
}
function ge_internacao_sql(string $alias='ac'): string {
    return ge_pair_sql($alias.'.fk_hospital_int',$alias.'.fk_paciente_int');
}
function ge_patient_sql(string $alias='tb_paciente'): string {
    if (!ge_enabled()) return '1=1';
    $uid=(int)($_SESSION['id_usuario']??0);
    return "(".ge_base_sql((string)$uid)." OR EXISTS (SELECT 1 FROM ge_escopo ge_s WHERE ge_s.usuario_id=$uid AND ge_s.estipulante_id=$alias.fk_estipulante_pac) OR EXISTS (SELECT 1 FROM tb_internacao ge_i JOIN ge_escopo ge_s ON ge_s.hospital_id=ge_i.fk_hospital_int WHERE ge_s.usuario_id=$uid AND ge_s.todos_pacientes=1 AND ge_i.fk_paciente_int=$alias.id_paciente AND COALESCE(ge_i.deletado_int,'n')<>'s'))";
}
function ge_forbidden(string $message='Operação fora do escopo do gestor estipulante.'): void {
    http_response_code(403);
    if (str_contains(strtolower($_SERVER['HTTP_ACCEPT']??''),'json') || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>false,'message'=>$message]);
    } else echo htmlspecialchars($message,ENT_QUOTES,'UTF-8').' <a href="'.htmlspecialchars(($GLOBALS['BASE_URL']??'/').'gestor_estipulante.php',ENT_QUOTES,'UTF-8').'">Voltar ao acompanhamento</a>';
    exit;
}
function ge_assert_entity(PDO $db,string $entity,int $id): void {
    if (!ge_enabled()) return;
    $map=['paciente'=>['tb_paciente p','p.id_paciente',ge_patient_sql('p')], 'hospital'=>['tb_hospital h','h.id_hospital',ge_hospital_sql('h.id_hospital')], 'internacao'=>['tb_internacao i','i.id_internacao',ge_internacao_sql('i')], 'censo'=>['tb_censo c','c.id_censo',ge_pair_sql('c.fk_hospital_censo','c.fk_paciente_censo')]];
    [$table,$key,$scope]=$map[$entity];
    $q=$db->prepare("SELECT $key FROM $table WHERE $key=? AND ($scope)");$q->execute([$id]);
    if (!$q->fetchColumn()) ge_forbidden('Registro não disponível no seu escopo.');
}
function ge_native_routes(): array {
    return [
        'gestor_estipulante.php'=>['gestor_estipulante','view'],
        'list_paciente.php'=>['pacientes','view'], 'cad_paciente.php'=>['pacientes','create'], 'edit_paciente.php'=>['pacientes','edit'], 'show_paciente.php'=>['pacientes','view'], 'process_paciente.php'=>['pacientes','create'],
        'list_hospital.php'=>['hospitais','view'], 'cad_hospital.php'=>['hospitais','create'], 'edit_hospital.php'=>['hospitais','edit'], 'show_hospital.php'=>['hospitais','view'], 'process_hospital.php'=>['hospitais','create'],
        'list_internacao.php'=>['internacoes','view'], 'cad_internacao.php'=>['internacoes','create'], 'process_internacao.php'=>['internacoes','create'],
        'list_censo.php'=>['censo','view'], 'cad_censo.php'=>['censo','create'], 'process_censo.php'=>['censo','create'],
        'list_internacao_gerar_alta.php'=>['altas','discharge'], 'process_gerar_altas.php'=>['altas','discharge'],
        'bi_navegacao.php'=>['bi_operacional','view'], 'LongaPermanenciaBI.php'=>['bi_operacional','view'], 'TipoInternacaoBI.php'=>['bi_operacional','view'],
        'ajax_cid_search.php'=>['internacoes','view'], 'check_internacao_ativa.php'=>['internacoes','view'], 'check_senha_internacao.php'=>['internacoes','view'],
    ];
}
function ge_enforce_native(PDO $db,string $script): void {
    if (!ge_enabled()) return;
    if (in_array($script,['session_activity.php','logout.php','destroi.php','mfa_configuracao.php','process_mfa_configuracao.php','nova_senha.php'],true)) return;
    $route=ge_native_routes()[$script]??null;
    if (!$route) ge_forbidden('Esta função não faz parte do subproduto Gestor estipulante.');
    $post=strtoupper($_SERVER['REQUEST_METHOD']??'GET')==='POST';
    if ((!ge_writer()) && ($post || $route[1]!=='view')) ge_forbidden('O gerente do estipulante possui acesso somente de consulta.');
    if ($post && $script!=='gestor_estipulante.php' && (!is_string($_POST['csrf']??null) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'],$_POST['csrf']))) ge_forbidden('Token de formulário inválido. Recarregue a página.');
    if (in_array($script,['process_paciente.php','process_hospital.php','process_censo.php','process_internacao.php'],true)) {
        $types=$script==='process_paciente.php'||$script==='process_hospital.php'?['create','update']:['create'];
        if (!$post || !in_array($_POST['type']??'', $types,true)) ge_forbidden('Operação não permitida neste cadastro.');
    }
    $input=$post?$_POST:$_GET;
    foreach (['paciente'=>'id_paciente','hospital'=>'id_hospital','censo'=>'id_censo'] as $entity=>$key) {
        if (isset($input[$key]) && (int)$input[$key]>0) ge_assert_entity($db,$entity,(int)$input[$key]);
        if (str_starts_with($script,'edit_'.$entity) && (int)($input[$key]??0)<=0) ge_forbidden('Registro não informado.');
    }
    if ($script==='process_paciente.php') {
        $est=(int)($_POST['fk_estipulante_pac']??0);
        $q=$db->query('SELECT id_estipulante FROM tb_estipulante WHERE id_estipulante='.$est.' AND '.ge_estipulante_sql());
        if (!$q->fetchColumn()) ge_forbidden('Selecione um estipulante vinculado à sua conta.');
    }
    if ($script==='process_hospital.php' && ($_POST['type']??'')==='create') {
        $est=(int)($_POST['ge_estipulante_id']??0);
        if (!$db->query('SELECT id_estipulante FROM tb_estipulante WHERE id_estipulante='.$est.' AND '.ge_estipulante_sql())->fetchColumn()) ge_forbidden('Selecione o estipulante ao qual o hospital será vinculado.');
    }
    if (in_array($script,['process_internacao.php','process_censo.php'],true)) {
        $hospital=(int)($_POST['fk_hospital_int']??0) ?: (int)($_POST['hospital_selected']??$_POST['fk_hospital_censo']??0);
        $patient=(int)($_POST['fk_paciente_int']??$_POST['fk_paciente_censo']??0);
        ge_assert_entity($db,'hospital',$hospital);ge_assert_entity($db,'paciente',$patient);
        if (!$db->query('SELECT 1 WHERE '.ge_pair_sql((string)$hospital,(string)$patient))->fetchColumn()) ge_forbidden('Paciente e hospital não pertencem ao mesmo vínculo autorizado.');
        foreach(['select_tuss','select_prorrog','select_negoc','select_gestao'] as $key) if (($_POST[$key]??'n')==='s') ge_forbidden('Autorizações e negociação não estão habilitadas neste subproduto.');
    }
    if ($script==='process_gerar_altas.php') {
        $ids=$_POST['gerar']??[];if (!is_array($ids) || !$ids) ge_forbidden('Selecione uma internação.');
        foreach($ids as $id) {
            ge_assert_entity($db,'internacao',(int)$id);
            $prefix='alta_'.(int)$id.'_';$uti=(int)($_POST[$prefix.'uti_id']??0);
            if ($uti) { $q=$db->prepare('SELECT id_uti FROM tb_uti WHERE id_uti=? AND fk_internacao_uti=?');$q->execute([$uti,(int)$id]);if(!$q->fetchColumn()) ge_forbidden('UTI fora da internação selecionada.'); }
            if (isset($_POST[$prefix.'uti_fk']) && (int)$_POST[$prefix.'uti_fk']!==(int)$id) ge_forbidden();
        }
    }
}
