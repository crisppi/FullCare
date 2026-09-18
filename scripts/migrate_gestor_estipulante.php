<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/db.php';
$checkpoint = dirname(__DIR__) . '/checkpoints/gestor-estipulante-20260917/banco-antes.json';
if (!is_file($checkpoint)) throw new RuntimeException('Crie o checkpoint primeiro.');
$sql = file_get_contents(dirname(__DIR__) . '/sql/20260917_gestor_estipulante.sql');
foreach (explode(';', $sql) as $statement) if (trim($statement) !== '') $conn->exec($statement);
$conn->beginTransaction();
try {
    $profiles = ['gestor_estipulante_med' => 'Gestor estipulante — Médico', 'gestor_estipulante_enf' => 'Gestor estipulante — Enfermeiro', 'gerente_estipulante' => 'Gerente do estipulante'];
    $ids = [];
    foreach ($profiles as $slug => $name) {
        $find = $conn->prepare('SELECT id_access_profile FROM tb_access_profile WHERE slug = ?');
        $find->execute([$slug]);
        $id = $find->fetchColumn();
        if (!$id) {
            $id = (int)$conn->query('SELECT COALESCE(MAX(id_access_profile), 0) + 1 FROM tb_access_profile')->fetchColumn();
            $conn->prepare('INSERT INTO tb_access_profile (id_access_profile, nome, slug, descricao) VALUES (?, ?, ?, ?)')->execute([$id, $name, $slug, 'Acompanhamento clínico de internações no escopo autorizado.']);
        }
        $ids[$slug] = $id;
        foreach (['view', 'create', 'edit', 'discharge'] as $action) {
            $conn->prepare('INSERT INTO tb_access_profile_permission (profile_id,module,action,allowed) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE allowed=VALUES(allowed)')->execute([$id, 'gestor_estipulante', $action, $action === 'view' || $slug !== 'gerente_estipulante' ? 1 : 0]);
        }
    }
    $find = $conn->prepare('SELECT id_usuario FROM tb_user WHERE LOWER(email_user) = ?');
    $find->execute(['gestor@fullcare.com.br']);
    $id = $find->fetchColumn();
    if (!$id) {
        $password = getenv('GESTOR_INITIAL_PASSWORD');
        if (!$password) throw new RuntimeException('Informe GESTOR_INITIAL_PASSWORD.');
        $conn->prepare("INSERT INTO tb_user (usuario_user,email_user,login_user,senha_user,senha_default_user,ativo_user,nivel_user,fk_access_profile,cargo_user,data_create_user) VALUES (?,?,?,?, 'n','s','1',?, 'Gestor estipulante - Médico',CURRENT_DATE)")->execute(['Gestor estipulante', 'gestor@fullcare.com.br', 'gestor@fullcare.com.br', password_hash($password, PASSWORD_DEFAULT), $ids['gestor_estipulante_med']]);
        $id = $conn->lastInsertId();
        echo "Usuário criado: gestor@fullcare.com.br (ID $id).\n";
    } else {
        echo "Usuário já existe; senha e cadastro preservados (ID $id).\n";
    }
    $conn->prepare('INSERT INTO schema_version (version,description,applied_by,file_name) SELECT ?,?,?,? WHERE NOT EXISTS (SELECT 1 FROM schema_version WHERE version=?)')->execute(['20260917_gestor_estipulante','Gestão de casos dos estipulantes','Codex','20260917_gestor_estipulante.sql','20260917_gestor_estipulante']);
    $conn->commit();
    echo "Migração concluída. Nenhum escopo clínico concedido automaticamente.\n";
} catch (Throwable $e) { $conn->rollBack(); throw $e; }
