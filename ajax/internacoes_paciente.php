<?php
header('Content-Type: application/json; charset=utf-8');

// Muda o diretório de trabalho para a raiz do projeto (um nível acima de /ajax)
$ROOT = dirname(__DIR__);
chdir($ROOT);

// Agora pode requerer usando caminhos relativos à raiz
require_once 'globals.php';
require_once 'db.php';
require_once 'ajax/_auth_scope.php';
require_once 'models/message.php';
require_once 'models/internacao.php'; // opcional, mas não atrapalha (require_once)
require_once 'dao/internacaoDao.php';

try {
    ajax_require_active_session();
    $ctx = ajax_user_context($conn);
    $pacId = filter_input(INPUT_GET, 'id_paciente', FILTER_VALIDATE_INT);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    if (!$pacId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id_paciente obrigatório']);
        exit;
    }

    if (!ajax_assert_patient_access($conn, $ctx, (int)$pacId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'acesso_negado']);
        exit;
    }

    $scopeParams = [];
    $scopeSql = ajax_scope_clause_for_internacao($ctx, 'ac', $scopeParams, 'ipp');

    $stmtTotal = $conn->prepare("SELECT COUNT(*) AS total
                                   FROM tb_internacao ac
                                  WHERE ac.fk_paciente_int = :pac {$scopeSql}");
    ajax_bind_params($stmtTotal, array_merge([':pac' => (int)$pacId], $scopeParams));
    $stmtTotal->execute();
    $total = (int)($stmtTotal->fetchColumn() ?: 0);

    $sql = "SELECT
                ac.id_internacao,
                ac.data_intern_int,
                ac.hora_intern_int,
                ac.internado_int,
                ac.fk_hospital_int,
                ho.nome_hosp,
                al.data_alta_alt,
                al.hora_alta_alt,
                (
                    SELECT COUNT(*)
                      FROM tb_prorrogacao pr
                     WHERE pr.fk_internacao_pror = ac.id_internacao
                ) AS prorrogacoes,
                (
                    SELECT COUNT(*)
                      FROM tb_visita vi
                     WHERE vi.fk_internacao_vis = ac.id_internacao
                       AND (vi.retificado IS NULL OR vi.retificado = 0)
                ) AS visitas_total
            FROM tb_internacao ac
            LEFT JOIN tb_hospital ho ON ho.id_hospital = ac.fk_hospital_int
            LEFT JOIN tb_alta al ON al.id_alta = (
                SELECT al2.id_alta
                  FROM tb_alta al2
                 WHERE al2.fk_id_int_alt = ac.id_internacao
                 ORDER BY COALESCE(al2.data_alta_alt, '0000-00-00') DESC, al2.id_alta DESC
                 LIMIT 1
            )
            WHERE ac.fk_paciente_int = :pac {$scopeSql}
            ORDER BY ac.data_intern_int DESC, ac.id_internacao DESC
            LIMIT :limit OFFSET :offset";
    $stmtRows = $conn->prepare($sql);
    ajax_bind_params($stmtRows, array_merge([
        ':pac' => (int)$pacId,
        ':limit' => (int)$limit,
        ':offset' => (int)$offset,
    ], $scopeParams));
    $stmtRows->execute();
    $rows = $stmtRows->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // formata datas
    $fmtDate = function ($d) {
        if (!$d || $d === '0000-00-00')
            return '';
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt ? $dt->format('d/m/Y') : '';
    };

    $payload = array_map(function ($r) use ($fmtDate) {
        return [
            'id_internacao' => (int) ($r['id_internacao'] ?? 0),
            'admissao' => $fmtDate($r['data_intern_int'] ?? null),
            'alta' => $fmtDate($r['data_alta_alt'] ?? null),
            'hora_admissao' => $r['hora_intern_int'] ?? null,
            'hora_alta' => $r['hora_alta_alt'] ?? null,
            'unidade' => trim($r['nome_hosp'] ?? ''),
            'medico' => '', // TODO: incluir no SELECT se precisar
            'status' => (isset($r['internado_int']) && $r['internado_int'] === 's') ? 'Internado' : 'Alta',
            'prorrogacoes' => (int)($r['prorrogacoes'] ?? 0),
            'visitas' => (int)($r['visitas_total'] ?? 0)
        ];
    }, $rows ?: []);

    echo json_encode([
        'success' => true,
        'total' => (int) $total,
        'page' => $page,
        'limit' => $limit,
        'rows' => $payload
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno'
    ]);
    exit;
}
