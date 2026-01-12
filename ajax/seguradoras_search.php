<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$ROOT = dirname(__DIR__);
chdir($ROOT);

require_once 'globals.php';
require_once 'db.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT id_seguradora, seguradora_seg
        FROM tb_seguradora
        WHERE seguradora_seg LIKE :like
        ORDER BY seguradora_seg
        LIMIT 10
    ");
    $like = "%{$q}%";
    $stmt->bindValue(':like', $like, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = array_map(fn($r) => [
        'id' => (int)($r['id_seguradora'] ?? 0),
        'label' => $r['seguradora_seg'] ?? '',
    ], $rows);
    echo json_encode($out);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
