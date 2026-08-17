<?php

include_once("check_logado.php");
include_once("globals.php");
require_once(__DIR__ . "/app/schemaEnsurer.php");
require_once(__DIR__ . "/utils/audit_logger.php");

header('Content-Type: application/json; charset=utf-8');

ensure_user_login_security_columns($conn);
ensure_user_mfa_schema($conn);

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$csrf = (string)filter_input(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
if (!csrf_is_valid($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Atualize a página.']);
    exit;
}

if (!FullCareAccess::can($conn, 'usuarios', 'edit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para resetar MFA.']);
    exit;
}

$userId = (int)filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($userId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Usuário inválido.']);
    exit;
}

try {
    $stmtUser = $conn->prepare("SELECT id_usuario, usuario_user, email_user FROM tb_user WHERE id_usuario = :id LIMIT 1");
    $stmtUser->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmtUser->execute();
    $target = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!is_array($target)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE tb_user
           SET mfa_enabled = 0,
               mfa_secret = NULL,
               mfa_confirmed_at = NULL,
               mfa_last_used_step = NULL,
               mfa_recovery_generated_at = NULL
         WHERE id_usuario = :id
         LIMIT 1
    ");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $delete = $conn->prepare("DELETE FROM tb_user_mfa_recovery_code WHERE user_id = :id");
    $delete->bindValue(':id', $userId, PDO::PARAM_INT);
    $delete->execute();

    fullcareAuditLog($conn, [
        'action' => 'mfa.admin_reset',
        'entity_type' => 'usuario',
        'entity_id' => $userId,
        'summary' => 'MFA resetado por administrador.',
        'before' => $target,
        'context' => [
            'admin_user_id' => (int)($_SESSION['id_usuario'] ?? 0),
        ],
        'source' => 'process_usuario_mfa_reset.php',
    ], $BASE_URL);

    echo json_encode([
        'success' => true,
        'message' => 'MFA resetado. O usuário poderá configurar novamente no próximo acesso.',
    ]);
} catch (Throwable $e) {
    error_log('[MFA][ADMIN_RESET] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível resetar o MFA agora.']);
}
