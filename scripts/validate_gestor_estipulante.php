<?php
// Testes de integração: tabelas TEMPORARY isoladas, na conexão atual, sem pacientes reais.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/db.php';
require dirname(__DIR__).'/app/gestor_estipulante.php';
require dirname(__DIR__).'/app/auth_session.php';
require dirname(__DIR__).'/api/mobile/src/auth.php';
require dirname(__DIR__).'/app/security/FullCareAccess.php';
function verify(bool $ok,string $label): void { if(!$ok) throw new RuntimeException('FAIL: '.$label);echo 'OK: '.$label.PHP_EOL; }
function denied(callable $fn,string $label): void { try {$fn();}catch(DomainException $e){verify(true,$label);return;}throw new RuntimeException('FAIL: '.$label); }
foreach(['tb_user','tb_access_profile','tb_access_profile_permission','tb_user_permission_override','tb_internacao','tb_alta','tb_paciente','tb_hospital','tb_estipulante','ge_escopo','ge_caso','ge_evolucao','ge_pendencia','ge_historico'] as $table) {
    $ddl=$conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    $ddl=preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $ddl);
    $ddl=preg_replace('/,?\n  CONSTRAINT[^\n]+/', '', $ddl);
    $conn->exec($ddl);
    $temporaryDdl = $conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    if (stripos($temporaryDdl, 'CREATE TEMPORARY TABLE') !== 0) {
        throw new RuntimeException('Teste abortado: tabela não temporária: '.$table);
    }
}
$conn->exec("INSERT INTO tb_access_profile (id_access_profile,nome,slug) VALUES (1,'Med','gestor_estipulante_med'),(2,'Enf','gestor_estipulante_enf'),(3,'Gerente','gerente_estipulante')");
$conn->exec("INSERT INTO tb_user (id_usuario,usuario_user,ativo_user,fk_access_profile) VALUES (1,'Médico teste','s',1),(2,'Enfermeiro teste','s',2),(3,'Gerente teste','s',3),(4,'Sem escopo','s',1)");
$conn->exec("INSERT INTO tb_hospital (id_hospital,nome_hosp) VALUES (1,'Hospital teste'),(2,'Outro hospital')");
$conn->exec("INSERT INTO tb_estipulante (id_estipulante,nome_est) VALUES (1,'Estipulante teste'),(2,'Outro estipulante')");
$conn->exec("INSERT INTO tb_paciente (id_paciente,nome_pac,fk_estipulante_pac) VALUES (1,'Paciente fictício A',1),(2,'Paciente fictício B',2)");
$conn->exec("INSERT INTO tb_internacao (id_internacao,fk_paciente_int,fk_hospital_int,internado_int,data_intern_int) VALUES (1,1,1,'s','2026-01-01'),(2,2,1,'s','2026-01-01'),(3,1,2,'s','2026-01-01')");
$conn->exec('INSERT INTO ge_escopo (usuario_id,hospital_id,estipulante_id) VALUES (1,1,1),(2,1,1),(3,1,1)');
$med=new GestorEstipulante($conn,1);$enf=new GestorEstipulante($conn,2);$manager=new GestorEstipulante($conn,3);$empty=new GestorEstipulante($conn,4);
verify(count($med->census())===1,'Censo limitado a hospital e estipulante');verify($empty->census()===[],'Sem vínculo não expõe pacientes');
denied(fn()=>$med->caseAccess(2),'Bloqueia outro estipulante');denied(fn()=>$med->caseAccess(3),'Bloqueia outro hospital');
denied(fn()=>$manager->save(1,['acao'=>'plano']),'Gerente não grava');
$med->save(1,['acao'=>'plano','versao'=>0,'responsavel_id'=>2,'previsao_alta'=>'2026-12-01','barreiras'=>'Aguardar transporte','plano_alta'=>'Continuidade']);
denied(fn()=>$med->save(1,['acao'=>'plano','versao'=>0]),'Concorrência não sobrescreve plano');
$evo=['acao'=>'evolucao','status'=>'rascunho','situacao'=>'Estável','origem'=>'Equipe do hospital','plano'=>'Acompanhar'];
$med->save(1,$evo);$eid=(int)$conn->query('SELECT MAX(id) FROM ge_evolucao')->fetchColumn();
verify(count($manager->records('ge_evolucao',1))===0,'Rascunho privado ao autor');
denied(fn()=>$enf->save(1,array_merge($evo,['evolucao_id'=>$eid,'status'=>'finalizada'])),'Outro autor não finaliza rascunho');
$med->save(1,array_merge($evo,['evolucao_id'=>$eid,'status'=>'finalizada']));verify(count($manager->records('ge_evolucao',1))===1,'Gerente consulta evolução finalizada');
denied(fn()=>$med->save(1,array_merge($evo,['evolucao_id'=>$eid])),'Evolução finalizada imutável');
$enf->save(1,array_merge($evo,['status'=>'finalizada','complemento_de'=>$eid]));verify(count($manager->records('ge_evolucao',1))===2,'Enfermeiro registra complemento');
denied(fn()=>$med->save(1,['acao'=>'evolucao','status'=>'finalizada']),'Finalização exige conteúdo clínico');
denied(fn()=>$med->save(1,['acao'=>'pendencia','descricao'=>'Teste','responsavel_id'=>4,'prazo'=>'2026-01-02T12:00']),'Responsável precisa de escopo');
$med->save(1,['acao'=>'pendencia','descricao'=>'Transporte','responsavel_id'=>2,'prazo'=>'2026-01-02T12:00']);
verify((int)$med->census()[0]['tarefas_vencidas']===1,'Alerta de tarefa vencida');
denied(fn()=>$med->save(1,['acao'=>'alta','alta_em'=>date('Y-m-d\TH:i'),'alta_destino'=>'Domicílio']),'Pendência aberta bloqueia encerramento');
$tid=(int)$conn->query('SELECT MAX(id) FROM ge_pendencia')->fetchColumn();$enf->save(1,['acao'=>'concluir','pendencia_id'=>$tid]);
$med->save(1,['acao'=>'alta','alta_em'=>date('Y-m-d\TH:i'),'alta_destino'=>'Domicílio']);
denied(fn()=>$med->save(1,$evo),'Caso encerrado bloqueia gravações');
verify($conn->query('SELECT internado_int FROM tb_internacao WHERE id_internacao=1')->fetchColumn()==='s','Encerramento preserva internação de origem');
verify(count($med->records('ge_historico',1))===7,'Ações mantêm histórico');
verify(mobileFindUserById($conn,1)===null,'Perfil dedicado bloqueado na API móvel de auditoria');
verify(fullcare_post_login_target('/', ['fk_access_profile'=>1])==='/gestor_estipulante.php','Login direciona ao módulo');
$_SERVER['SCRIPT_NAME']='/gestor_estipulante.php';$_SERVER['REQUEST_URI']='/gestor_estipulante.php';$_SERVER['REQUEST_METHOD']='GET';
verify(FullCareAccess::currentRequestAccess()===['module'=>'gestor_estipulante','action'=>'view'],'Rota usa módulo dedicado');
$conn->exec('UPDATE ge_escopo SET todos_pacientes=1 WHERE usuario_id=1');verify(count($med->census())===2,'Escopo hospitalar explícito inclui todos os estipulantes só desse hospital');
echo "Validação concluída; tabelas temporárias descartadas ao fechar a conexão.\n";
