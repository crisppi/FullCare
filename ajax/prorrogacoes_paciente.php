<?php
// ajax/prorrogacoes_paciente.php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Muda para a raiz do projeto (um nível acima de /ajax)
$ROOT = dirname(__DIR__);
chdir($ROOT);

require_once 'globals.php';
require_once 'db.php';
require_once 'ajax/_auth_scope.php';
require_once 'models/message.php';
// Você tem um DAO de prorrogação, mas aqui vou usar SQL direto para não alterar o DAO.
require_once 'models/prorrogacao.php';
require_once 'dao/prorrogacaoDao.php';

ajax_require_active_session();

try {
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
    $scopeSql = ajax_scope_clause_for_internacao($ctx, 'ac', $scopeParams, 'prp');

    // Total de prorrogações do paciente
    $sqlCount = "
        SELECT COUNT(*) AS total
        FROM tb_prorrogacao pr
        INNER JOIN tb_internacao ac ON ac.id_internacao = pr.fk_internacao_pror
        WHERE ac.fk_paciente_int = :pacId
          {$scopeSql}
    ";
    $stc = $conn->prepare($sqlCount);
    ajax_bind_params($stc, array_merge([':pacId' => (int)$pacId], $scopeParams));
    $stc->execute();
    $total = (int) ($stc->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Lista paginada
    $sql = "
        SELECT
            pr.id_prorrogacao,
            pr.fk_internacao_pror      AS id_internacao,
            pr.acomod1_pror            AS acomod,
            pr.isol_1_pror             AS isolamento,
            pr.prorrog1_ini_pror       AS ini,
            pr.prorrog1_fim_pror       AS fim,
            pr.diarias_1               AS diarias,
            ac.fk_hospital_int,
            ho.nome_hosp
        FROM tb_prorrogacao pr
        INNER JOIN tb_internacao ac ON ac.id_internacao = pr.fk_internacao_pror
        LEFT JOIN tb_hospital ho ON ho.id_hospital = ac.fk_hospital_int
        WHERE ac.fk_paciente_int = :pacId
          {$scopeSql}
        ORDER BY pr.prorrog1_ini_pror DESC, pr.id_prorrogacao DESC
        LIMIT :limit OFFSET :offset
    ";
    $stl = $conn->prepare($sql);
    ajax_bind_params($stl, array_merge([
        ':pacId' => (int)$pacId,
        ':limit' => (int)$limit,
        ':offset' => (int)$offset,
    ], $scopeParams));
    $stl->execute();
    $rows = $stl->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Formatações
    $fmt = function ($d) {
        if (!$d || $d === '0000-00-00')
            return '';
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt ? $dt->format('d/m/Y') : '';
    };

    $payload = array_map(function ($r) use ($fmt) {
        $ini = $fmt($r['ini'] ?? null);
        $fim = $fmt($r['fim'] ?? null);
        $periodo = trim($ini . ($fim ? ' a ' . $fim : ''));

        return [
            'id_prorrogacao' => (int) ($r['id_prorrogacao'] ?? 0),
            'id_internacao' => (int) ($r['id_internacao'] ?? 0),
            'hospital' => $r['nome_hosp'] ?? '',
            'acomodacao' => $r['acomod'] ?? '',
            'isolamento' => ($r['isolamento'] ?? '') === 's' ? 'Sim' : 'Não',
            'periodo' => $periodo,
            'diarias' => (int) ($r['diarias'] ?? 0),
            // Campos “genéricos” que já existem na sua tabela HTML:
            'status' => '',    // (preencher se tiver alguma regra/coluna de status)
            'observacoes' => ''     // (preencher se quiser colocar comentários)
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'rows' => $payload
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno',
    ]);
    exit;
}
