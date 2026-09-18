<?php
// Somente dados fictícios em tabelas temporárias. Não altera cadastros reais.
if (PHP_SAPI !== 'cli') { http_response_code(404);exit; }
require __DIR__.'/validate_gestor_estipulante.php';
require_once dirname(__DIR__).'/app/security/gestor_scope.php';
require_once dirname(__DIR__).'/dao/hospitalDao.php';
require_once dirname(__DIR__).'/dao/pacienteDao.php';
require_once dirname(__DIR__).'/dao/censoDao.php';
require_once dirname(__DIR__).'/app/bi_cid_options.php';
$_SESSION=['id_usuario'=>1,'ativo'=>'s','nivel'=>1,'cargo'=>'Gestor estipulante - Médico'];
$GLOBALS['ge_profile']='gestor_estipulante_med';
$conn->exec('UPDATE ge_escopo SET todos_pacientes=0, hospital_id=hospital_id+10');
$conn->exec('UPDATE tb_hospital SET id_hospital=id_hospital+10');
$conn->exec('UPDATE tb_internacao SET fk_hospital_int=fk_hospital_int+10');
$conn->exec("UPDATE tb_paciente SET deletado_pac='n'");
$conn->exec("UPDATE tb_hospital SET deletado_hosp='n'");
$hospitalDao=new HospitalDAO($conn,'/');$patientDao=new PacienteDAO($conn,'/');
verify(count($hospitalDao->findGeral())===1,'Cadastro original de hospitais respeita vínculos');
verify(count($hospitalDao->selectAllhospital())===1,'Listagem original de hospitais respeita escopo');
verify(count($patientDao->findGeral())===1,'Select original de pacientes respeita estipulante');
verify(count($patientDao->selectAllpaciente())===1,'Lista original de pacientes respeita estipulante');
verify(count($conn->query('SELECT * FROM tb_internacao ac WHERE '.ge_internacao_sql())->fetchAll())===1,'Internações nativas limitadas ao par hospital/estipulante');
$conn->exec("INSERT INTO tb_paciente (id_paciente,nome_pac,fk_estipulante_pac,deletado_pac) VALUES (3,'Fictício sem internação',1,'n')");
verify(count($patientDao->findGeral())===2,'Paciente cadastrado pode ser selecionado antes da primeira internação');
$joins=[];$where=[];$params=[];bi_apply_internacao_option_filters([], 'i.data_intern_int',$joins,$where,$params);
verify(count($where)===1 && str_contains($where[0],'ge_escopo'),'Filtros do BI aplicam o escopo nativo');
verify((int)$conn->query('SELECT COUNT(*) FROM tb_internacao i WHERE '.implode(' AND ',$where))->fetchColumn()===1,'BI não agrega internações de outro hospital ou estipulante');
foreach(['tb_censo','tb_hospitalUser'] as $table) {
 $ddl=$conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
 $ddl=preg_replace('/^CREATE TABLE/','CREATE TEMPORARY TABLE',$ddl);
 $ddl=preg_replace('/,?\n  CONSTRAINT[^\n]+/','',$ddl);$conn->exec($ddl);
    $temporaryDdl = $conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    if (stripos($temporaryDdl, 'CREATE TEMPORARY TABLE') !== 0) {
        throw new RuntimeException('Teste abortado: tabela não temporária: '.$table);
    }
}
$conn->exec('INSERT INTO tb_censo (id_censo,fk_hospital_censo,fk_paciente_censo) VALUES (1,11,1),(2,11,2),(3,12,1)');
$censo=new CensoDAO($conn,'/');verify(count($censo->selectAllCensoList())===1,'Censo original aplica escopo');
$conn->exec('UPDATE ge_escopo SET todos_pacientes=1 WHERE usuario_id=1');
verify(count($censo->selectAllCensoList())===2,'Escopo explícito de hospital amplia apenas aquele hospital');
$_SESSION['id_usuario']=4;
verify($hospitalDao->findGeral()===[] && $patientDao->findGeral()===[] && $censo->selectAllCensoList()===[],'Sem vínculo: cadastros e censo nativos vazios');
$conn->exec('INSERT INTO ge_escopo (usuario_id,hospital_id,estipulante_id,todos_pacientes) VALUES (4,0,0,1)');
verify(count($hospitalDao->findGeral())===2,'Base FullCare: hospitais existentes compartilhados');
verify(count($patientDao->findGeral())===3,'Base FullCare: pacientes inclusive sem internação');
$conn->exec("INSERT INTO tb_paciente (id_paciente,nome_pac,deletado_pac) VALUES (4,'Fictício sem vínculo','n')");
verify(count($patientDao->findGeral())===4,'Base FullCare inclui novo cadastro sem estipulante');
verify(count($empty->census())===3,'Base FullCare aplicada ao acompanhamento');
verify(count($empty->team(1))===3,'Responsável com acesso à base compartilhada disponível');
$_SESSION['id_usuario']=1;
verify(count($patientDao->findGeral())===3,'Outros usuários mantêm seus vínculos restritos');
$_SESSION['id_usuario']=4;
$GLOBALS['ge_profile']='gerente_estipulante';verify(!ge_writer(),'Gerente permanece somente em consulta');
verify(!isset(ge_native_routes()['process_tuss.php']) && !isset(ge_native_routes()['process_prorrogacao.php']),'Rotas de autorização excluídas');
$GLOBALS['ge_profile']='';verify(ge_internacao_sql()==='1=1' && ge_patient_sql()==='1=1','Perfis originais preservam seu fluxo');
echo "Integração com componentes nativos validada.\n";
