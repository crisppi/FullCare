<?php
// ajax/paciente_insights.php
header('Content-Type: application/json; charset=utf-8');
session_start();

$ROOT = dirname(__DIR__);
chdir($ROOT);

require_once 'globals.php';
require_once 'db.php';
require_once 'ajax/_auth_scope.php';
require_once 'models/internacao.php';
require_once 'dao/internacaoDao.php';
require_once 'app/cuidadoContinuado.php';

ajax_require_active_session();

try {
    $ctx = ajax_user_context($conn);
    $pacienteId = filter_input(INPUT_GET, 'id_paciente', FILTER_VALIDATE_INT);
    if (!$pacienteId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'id_paciente obrigatório'
        ]);
        exit;
    }

    if (!ajax_assert_patient_access($conn, $ctx, (int)$pacienteId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'acesso_negado']);
        exit;
    }

    $scopeParams = [];
    $scopeSql = ajax_scope_clause_for_internacao($ctx, 'ac', $scopeParams, 'pins');
    $params = array_merge([':pac' => (int)$pacienteId], $scopeParams);

    $stmtTotal = $conn->prepare("SELECT COUNT(*) AS total
                                   FROM tb_internacao ac
                                  WHERE ac.fk_paciente_int = :pac {$scopeSql}");
    ajax_bind_params($stmtTotal, $params);
    $stmtTotal->execute();
    $totalInternacoes = (int)($stmtTotal->fetchColumn() ?: 0);

    $stmtDias = $conn->prepare("SELECT COALESCE(SUM(total_diarias),0) AS total_diarias
                                  FROM (
                                        SELECT DATEDIFF(COALESCE(al.data_alta_alt, CURRENT_DATE()), ac.data_intern_int) AS total_diarias
                                          FROM tb_internacao ac
                                          LEFT JOIN tb_alta al ON ac.id_internacao = al.fk_id_int_alt
                                         WHERE ac.fk_paciente_int = :pac {$scopeSql}
                                       ) interns");
    ajax_bind_params($stmtDias, $params);
    $stmtDias->execute();
    $totalDiarias = (int)($stmtDias->fetchColumn() ?: 0);

    $mp = $totalInternacoes > 0 ? round($totalDiarias / $totalInternacoes, 1) : 0;

    ensure_cuidado_continuado_schema($conn);

    $stmtCronicos = $conn->prepare("SELECT COUNT(*) AS total,
                                           GROUP_CONCAT(DISTINCT condicao ORDER BY condicao SEPARATOR ', ') AS condicoes
                                      FROM tb_paciente_cronico
                                     WHERE fk_paciente = :pac");
    $stmtCronicos->bindValue(':pac', (int)$pacienteId, PDO::PARAM_INT);
    $stmtCronicos->execute();
    $cronicosRow = $stmtCronicos->fetch(PDO::FETCH_ASSOC) ?: [];

    $cronicosTotal = (int)($cronicosRow['total'] ?? 0);
    $programas = [];
    if ($cronicosTotal > 0) {
        $programas[] = 'Gestão de Crônicos';
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'total_internacoes' => $totalInternacoes,
            'total_diarias'     => $totalDiarias,
            'mp'                => $mp,
            'cuidado_programa'  => [
                'em_programa' => !empty($programas),
                'programas' => $programas,
                'cronicos_total' => $cronicosTotal,
                'condicoes' => (string)($cronicosRow['condicoes'] ?? ''),
            ],
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro ao recuperar dados do paciente',
    ]);
}
