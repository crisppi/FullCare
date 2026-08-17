<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/globals.php';
FullCareAccess::enforce($conn, $BASE_URL, 'permissoes', 'manage');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(419);
    exit('Sessão expirada. Recarregue a página e tente novamente.');
}

$profileId = (int)($_POST['profile_id'] ?? 0);
$stmt = $conn->prepare("SELECT nome, slug FROM tb_access_profile WHERE id_access_profile = :id AND ativo = 1 LIMIT 1");
$stmt->execute([':id' => $profileId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile || $profile['slug'] === 'superadministrador') {
    http_response_code(422);
    exit('Nível inválido ou protegido.');
}

$modules = ['dashboard','manual','pacientes','hospitais','seguradoras','estipulantes','censo','internacoes','visitas','gestao','contas','altas','usuarios','permissoes','cadastros_clinicos','cuidado_continuado','bi_operacional','bi_estrategico','solicitacoes'];
$actions = ['view','create','edit','delete','discharge','revert_discharge','close_management','reopen_management','finalize_account','reopen_account','generate_pdf','export','manage'];
$posted = is_array($_POST['permissions'] ?? null) ? $_POST['permissions'] : [];

try {
    $conn->beginTransaction();
    $upsert = $conn->prepare("INSERT INTO tb_access_profile_permission (profile_id, module, action, allowed)
        VALUES (:profile, :module, :action, :allowed)
        ON DUPLICATE KEY UPDATE allowed = VALUES(allowed), atualizado_em = CURRENT_TIMESTAMP");
    foreach ($modules as $module) {
        foreach ($actions as $action) {
            $upsert->execute([
                ':profile' => $profileId, ':module' => $module, ':action' => $action,
                ':allowed' => isset($posted[$module][$action]) ? 1 : 0,
            ]);
        }
    }
    $audit = $conn->prepare("INSERT INTO tb_access_audit
        (actor_user_id, target_user_id, evento, valor_anterior, valor_novo, motivo, ip)
        VALUES (:user, NULL, 'alteracao_matriz_perfil', NULL, :details, :motivo, :ip)");
    $audit->execute([
        ':user' => (int)$_SESSION['id_usuario'],
        ':details' => json_encode(['profile_id' => $profileId, 'perfil' => $profile['nome']], JSON_UNESCAPED_UNICODE),
        ':motivo' => 'Matriz de permissões atualizada pela administração',
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    $conn->commit();
    $_SESSION['msg'] = 'Matriz de acesso atualizada com segurança.';
    $_SESSION['type'] = 'success';
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Falha ao atualizar matriz de acesso: ' . $e->getMessage());
    $_SESSION['msg'] = 'Não foi possível atualizar a matriz. Nenhuma alteração foi aplicada.';
    $_SESSION['type'] = 'error';
}

header('Location: ' . $BASE_URL . 'administracao/permissoes?perfil=' . $profileId);
exit;
