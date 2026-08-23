<?php

require_once __DIR__ . '/check_logado.php';

header('Content-Type: application/json; charset=utf-8');

$term = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($term) < 2) {
    echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$like = '%' . $term . '%';
$stmt = $conn->prepare(
    'SELECT id_cid AS id, CONCAT(cat, " - ", descricao) AS text
       FROM tb_cid
      WHERE cat LIKE :term_cat OR descricao LIKE :term_desc
      ORDER BY cat ASC, descricao ASC
      LIMIT 40'
);
$stmt->execute([
    ':term_cat' => $like,
    ':term_desc' => $like,
]);

echo json_encode(
    ['results' => $stmt->fetchAll(PDO::FETCH_ASSOC)],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
