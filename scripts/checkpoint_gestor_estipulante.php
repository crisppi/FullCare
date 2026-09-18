<?php
// Somente CLI: snapshot das tabelas compartilhadas que a implantação pode alterar.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/db.php';
$dir = dirname(__DIR__) . '/checkpoints/gestor-estipulante-20260917';
$file = $dir . '/banco-antes.json';
if (file_exists($file)) { throw new RuntimeException('Checkpoint já existe; não será sobrescrito.'); }
$tables = ['tb_user', 'tb_access_profile', 'tb_access_profile_permission', 'schema_version'];
$data = ['created_at' => date(DATE_ATOM), 'database' => $conn->query('SELECT DATABASE()')->fetchColumn(), 'tables' => []];
$conn->beginTransaction();
foreach ($tables as $table) {
    $data['tables'][$table] = ['ddl' => $conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1], 'rows' => $conn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC)];
}
$conn->commit();
$fp = fopen($file, 'x');
chmod($file, 0600);
fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
fclose($fp);
echo "Checkpoint do banco salvo (tabelas de usuários, perfis, permissões e versões).\n";
