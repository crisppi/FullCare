<?php
require_once __DIR__ . '/../globals.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}
if ($method === 'POST') {
    $csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($csrf === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $csrf)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Token inválido.']);
        exit;
    }
    // O guard já rejeitou a sessão vencida: atividade tardia não reabre o login.
    $_SESSION['idle_last_activity'] = time();
}
echo json_encode(fullcare_idle_status(time()));
