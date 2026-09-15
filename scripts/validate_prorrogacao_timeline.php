<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
require_once 'app/ProrrogacaoTimeline.php';
function checkTimeline(bool $ok, string $label): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $label\n"); exit(1); }
    echo "OK: $label\n";
}
function rejectTimeline(callable $fn, string $label): void {
    try { $fn(); } catch (DomainException $e) { checkTimeline(true, $label); return; }
    checkTimeline(false, $label);
}
$rows = [['ini'=>'2026-08-10','fim'=>'2026-08-12','acomod'=>'Apto'],['ini'=>'2026-08-12','fim'=>'2026-08-14','acomod'=>'UTI'],['ini'=>'2026-08-14','fim'=>'2026-08-16','acomod'=>'Semi']];
ProrrogacaoTimeline::assertRows($rows,'2026-08-10','2026-08-16');
checkTimeline(ProrrogacaoTimeline::coverage($rows,'2026-08-10','2026-08-16')['complete'],'Sequência Apto/UTI/Semi com alta no dia 16');
$gap=ProrrogacaoTimeline::coverage([$rows[0],$rows[2]],'2026-08-10','2026-08-16');
checkTimeline($gap['missingDays']===2 && $gap['next']===['ini'=>'2026-08-12','fim'=>'2026-08-14'],'Sugere lacuna 12→14 após alta');
checkTimeline(ProrrogacaoTimeline::coverage([$rows[1],$rows[2]],'2026-08-10','2026-08-16')['missingDays']===2,'Detecta lacuna desde a internação');
checkTimeline(ProrrogacaoTimeline::coverage([],'2026-08-10',null)['next']['ini']==='2026-08-10','Primeiro período começa na internação');
checkTimeline(ProrrogacaoTimeline::coverage($rows,'2026-08-10','2026-08-16')['next']===null,'Alta completa não sugere novo período');
foreach ([['ini'=>'2026-08-09','fim'=>'2026-08-12','acomod'=>'Apto'],['ini'=>'2026-08-14','fim'=>'2026-08-17','acomod'=>'UTI'],['ini'=>'2026-08-12','fim'=>'2026-08-12','acomod'=>'UTI'],['ini'=>'2026-02-30','fim'=>'2026-08-12','acomod'=>'UTI'],['ini'=>'2026-08-10','fim'=>'','acomod'=>'Apto']] as $bad) {
    rejectTimeline(static fn()=>ProrrogacaoTimeline::assertRows([$bad],'2026-08-10','2026-08-16'),'Rejeita data fora dos limites/inválida');
}
rejectTimeline(static fn()=>ProrrogacaoTimeline::assertRows([$rows[0],$rows[0]],'2026-08-10',null),'Rejeita duplicidade');
checkTimeline(ProrrogacaoTimeline::days('2026-08-10','2026-08-12')===2,'Diárias sem duplicar dia da troca');
if (getenv('FULLCARE_TEST_TEMP_TABLES') !== '1') exit;
// Tabelas TEMPORARY só existem nesta conexão e não alteram dados da aplicação.
require_once 'db.php';
require_once 'dao/prorrogacaoDao.php';
$conn->exec('CREATE TEMPORARY TABLE tb_internacao (id_internacao INT PRIMARY KEY, data_intern_int DATE) ENGINE=InnoDB');
$conn->exec('CREATE TEMPORARY TABLE tb_alta (id_alta INT PRIMARY KEY AUTO_INCREMENT, fk_id_int_alt INT, data_alta_alt DATE) ENGINE=InnoDB');
$conn->exec('CREATE TEMPORARY TABLE tb_user (id_usuario INT PRIMARY KEY) ENGINE=InnoDB');
$conn->exec('CREATE TEMPORARY TABLE tb_prorrogacao (id_prorrogacao INT PRIMARY KEY AUTO_INCREMENT, fk_internacao_pror INT, fk_visita_pror INT NULL, fk_usuario_pror INT NULL, acomod1_pror VARCHAR(80), isol_1_pror CHAR(1), prorrog1_ini_pror DATE, prorrog1_fim_pror DATE, diarias_1 INT) ENGINE=InnoDB');
$conn->exec("INSERT INTO tb_internacao VALUES (1,'2026-08-10'); INSERT INTO tb_alta (fk_id_int_alt,data_alta_alt) VALUES (1,'2026-08-16')");
$dao = new prorrogacaoDAO($conn,'');
function objectTimeline(array $r): prorrogacao {
    $p=new prorrogacao(); $p->fk_internacao_pror=1; $p->acomod1_pror=$r['acomod']; $p->prorrog1_ini_pror=$r['ini']; $p->prorrog1_fim_pror=$r['fim']; $p->diarias_1=999; return $p;
}
$first=objectTimeline($rows[0]); $id=$dao->create($first);
checkTimeline($id>0 && (int)$first->diarias_1===2,'DAO recalcula diárias e preserva ID após commit');
$last=objectTimeline($rows[2]);$dao->create($last);
$middle=objectTimeline($rows[1]);$dao->create($middle);
rejectTimeline(static fn()=>$dao->create(objectTimeline($rows[0])),'DAO bloqueia sobreposição');
$batch=[array_merge($rows[0],['id_prorrogacao'=>$first->id_prorrogacao,'fim'=>'2026-08-13']),array_merge($rows[1],['id_prorrogacao'=>$middle->id_prorrogacao,'ini'=>'2026-08-13']),array_merge($rows[2],['id_prorrogacao'=>$last->id_prorrogacao])];
$conn->beginTransaction();$dao->prepareBatch(1,$batch);
foreach($batch as $row){$p=objectTimeline($row);$p->id_prorrogacao=$row['id_prorrogacao'];$dao->update($p);}
$dao->finishBatch();$conn->commit();
checkTimeline((int)$conn->query('SELECT SUM(diarias_1) FROM tb_prorrogacao')->fetchColumn()===6,'Edição conjunta da troca sem conflito com valores antigos');
$conn->beginTransaction();
$dao->deleteByVisita(99);
rejectTimeline(static fn()=>$dao->create(objectTimeline(['ini'=>'2026-08-16','fim'=>'2026-08-17','acomod'=>'Apto'])),'DAO impede período posterior à alta');
$conn->rollBack();
checkTimeline((int)$conn->query('SELECT COUNT(*) FROM tb_prorrogacao')->fetchColumn()===3,'Falha mantém registros anteriores');
