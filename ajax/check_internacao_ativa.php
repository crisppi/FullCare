<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$ROOT = dirname(__DIR__);
chdir($ROOT);

require_once 'globals.php';
require_once 'db.php';
require_once 'models/internacao.php';
require_once 'dao/internacaoDao.php';
require_once __DIR__ . '/_auth_scope.php';

ajax_require_active_session();

$response = ['success' => true, 'hasActive' => false];

try {
    $ctx = ajax_user_context($conn);
    $pacienteId = filter_input(INPUT_GET, 'id_paciente', FILTER_VALIDATE_INT);
    if (!$pacienteId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id_paciente obrigatório']);
        exit;
    }

    $scopeParams = [];
    $scopeSql = ajax_scope_clause_for_internacao($ctx, 'ac', $scopeParams, 'cia');

    $stmt = $conn->prepare("SELECT ac.id_internacao, ac.data_intern_int, ac.hora_intern_int, ho.nome_hosp
            FROM tb_internacao ac
            LEFT JOIN tb_hospital ho ON ho.id_hospital = ac.fk_hospital_int
            WHERE ac.fk_paciente_int = :paciente_id
              AND ac.internado_int = 's'
              {$scopeSql}
            ORDER BY ac.id_internacao DESC
            LIMIT 1");
    ajax_bind_params($stmt, array_merge([':paciente_id' => (int)$pacienteId], $scopeParams));
    $stmt->execute();
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($info) {
        $dataFmt = null;
        if (!empty($info['data_intern_int']) && $info['data_intern_int'] !== '0000-00-00') {
            $dt = DateTime::createFromFormat('Y-m-d', $info['data_intern_int']) ?: new DateTime($info['data_intern_int']);
            if ($dt) {
                $dataFmt = $dt->format('d/m/Y');
            }
        }

        $response['hasActive'] = true;
        $response['active'] = [
            'id_internacao'   => (int) $info['id_internacao'],
            'hospital'        => $info['nome_hosp'] ?? null,
            'data_internacao' => $info['data_intern_int'] ?? null,
            'hora'            => $info['hora_intern_int'] ?? null,
            'data_formatada'  => $dataFmt,
        ];
    }

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro interno',
    ]);
}
