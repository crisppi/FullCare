<?php
if (PHP_SAPI !== 'cli') { http_response_code(404);exit; }
require dirname(__DIR__).'/db.php';
$conn->beginTransaction();
try {
    $profiles=$conn->query("SELECT id_access_profile,slug FROM tb_access_profile WHERE slug IN ('gestor_estipulante_med','gestor_estipulante_enf','gerente_estipulante')")->fetchAll(PDO::FETCH_ASSOC);
    foreach($profiles as $p) foreach(['pacientes','hospitais','internacoes','censo','altas','bi_operacional'] as $module) foreach(['view','create','edit','discharge'] as $action) {
        $allowed=$action==='view' || ($p['slug']!=='gerente_estipulante' && ($module!=='bi_operacional') && ($action!=='discharge'||$module==='altas'));
        $conn->prepare('INSERT INTO tb_access_profile_permission (profile_id,module,action,allowed) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)')->execute([$p['id_access_profile'],$module,$action,$allowed?1:0]);
    }
    $conn->commit();echo "Permissões das telas nativas aplicadas.\n";
} catch(Throwable $e) {$conn->rollBack();throw $e;}
