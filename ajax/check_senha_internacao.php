<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$ROOT = dirname(__DIR__);
chdir($ROOT);

require_once 'globals.php';
require_once 'db.php';
require_once __DIR__ . '/_auth_scope.php';

if (empty($_SESSION['id_usuario']) || strtolower((string)($_SESSION['ativo'] ?? '')) !== 's') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'nao_autenticado']);
    exit;
}

$senha = isset($_GET['senha']) ? trim((string) $_GET['senha']) : '';
$ignore = filter_input(INPUT_GET, 'ignore', FILTER_VALIDATE_INT);

if ($senha === '') {
    echo json_encode(['success' => true, 'exists' => false]);
    exit;
}

try {
    $ctx = ajax_user_context($conn);
    $params = [
        ':senha' => $senha,
    ];
    if ($ignore) {
        $params[':ignore'] = (int)$ignore;
    }

    $scopeSql = ajax_scope_clause_for_internacao($ctx, 'ac', $params, 'csi');

    $sql = "SELECT ac.id_internacao
              FROM tb_internacao ac
             WHERE ac.senha_int = :senha";
    if ($ignore) {
        $sql .= " AND ac.id_internacao <> :ignore";
    }
    $sql .= $scopeSql . " LIMIT 1";

    $stmt = $conn->prepare($sql);
    ajax_bind_params($stmt, $params);
    $stmt->execute();
    $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'exists' => $exists]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao verificar senha',
    ]);
}
