<?php
// php scripts/configure_gestor_estipulante.php EMAIL HOSPITAL_ID ESTIPULANTE_ID [--todos-pacientes]
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/db.php';
require dirname(__DIR__).'/app/gestor_estipulante.php';
[$script,$email,$hospital,$estipulante]=array_pad($argv,4,null);
$baseFullcare=$hospital==='--base-fullcare';
if (!$email || (!$baseFullcare && (!ctype_digit((string)$hospital) || !ctype_digit((string)$estipulante) || (int)$hospital<1 || (int)$estipulante<1))) throw new RuntimeException('Uso: php scripts/configure_gestor_estipulante.php EMAIL HOSPITAL_ID ESTIPULANTE_ID [--todos-pacientes]');
$q=$conn->prepare('SELECT id_usuario FROM tb_user WHERE email_user=?');$q->execute([$email]);$uid=(int)$q->fetchColumn();
new GestorEstipulante($conn,$uid);
if ($baseFullcare) { $hospital=0; $estipulante=0; }
if (!$baseFullcare) foreach (['tb_hospital'=>['id_hospital',$hospital], 'tb_estipulante'=>['id_estipulante',$estipulante]] as $table=>[$field,$value]) { $q=$conn->prepare("SELECT $field FROM $table WHERE $field=?");$q->execute([$value]);if(!$q->fetchColumn()) throw new RuntimeException('Hospital ou estipulante inexistente.'); }
$all=($baseFullcare || in_array('--todos-pacientes',$argv,true))?1:0;
// Checkpoint seletivo antes de conceder acesso à base inteira.
if ($baseFullcare) {
    $q=$conn->prepare('SELECT * FROM ge_escopo WHERE usuario_id=?'); $q->execute([$uid]);
    $dir=dirname(__DIR__).'/checkpoints/gestor-base-fullcare';
    if (!is_dir($dir)) mkdir($dir,0700,true);
    $file=$dir.'/escopo-antes-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
    $fp=fopen($file,'x'); chmod($file,0600);
    fwrite($fp,json_encode(['usuario_id'=>$uid,'escopos'=>$q->fetchAll(PDO::FETCH_ASSOC)], JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)); fclose($fp);
}
$conn->beginTransaction();
try {
    $conn->prepare('INSERT INTO ge_escopo (usuario_id,hospital_id,estipulante_id,todos_pacientes) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE todos_pacientes=VALUES(todos_pacientes)')->execute([$uid,$hospital,$estipulante,$all]);
    $conn->prepare("INSERT INTO tb_access_audit (target_user_id,evento,valor_novo,motivo) VALUES (?,'escopo_estipulante_configurado',?,'Configuração via CLI')")->execute([$uid,json_encode(['hospital'=>$hospital,'estipulante'=>$estipulante,'todos_pacientes'=>$all,'base_fullcare'=>$baseFullcare])]);
    $conn->commit();echo "Escopo configurado.\n";
} catch(Throwable $e) { $conn->rollBack();throw $e; }
