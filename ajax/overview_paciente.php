<?php
// ajax/overview_paciente.php
header('Content-Type: application/json; charset=utf-8');
session_start();

// muda contexto p/ raiz
$ROOT = dirname(__DIR__);
chdir($ROOT);

require_once 'globals.php';
require_once 'db.php';
require_once 'ajax/_auth_scope.php';
require_once 'models/message.php';
require_once 'models/internacao.php';
require_once 'dao/internacaoDao.php';
require_once 'models/visita.php';
require_once 'dao/visitaDao.php';

ajax_require_active_session();

try {
  $ctx = ajax_user_context($conn);
  $pacId = filter_input(INPUT_GET, 'id_paciente', FILTER_VALIDATE_INT);
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

  $fmtDate = function ($d, $fmt='d/m/Y') {
    if (!$d || $d === '0000-00-00') return null;
    $dt = DateTime::createFromFormat('Y-m-d', $d) ?: new DateTime($d);
    return $dt ? $dt->format($fmt) : null;
  };

  // 1) Internação atual (escopo aplicado)
  $internacaoAtual = null;
  $scopeIntParams = [];
  $scopeIntSql = ajax_scope_clause_for_internacao($ctx, 'ac', $scopeIntParams, 'ovp');
  $stmtInt = $conn->prepare("SELECT
        ac.id_internacao,
        ac.data_intern_int,
        ac.hora_intern_int,
        ac.fk_hospital_int,
        ac.internado_int,
        ac.acomodacao_int,
        ac.especialidade_int,
        ac.grupo_patologia_int
      FROM tb_internacao ac
      WHERE ac.fk_paciente_int = :pac
        AND ac.internado_int = 's'
        {$scopeIntSql}
      ORDER BY ac.id_internacao DESC
      LIMIT 1");
  ajax_bind_params($stmtInt, array_merge([':pac' => (int)$pacId], $scopeIntParams));
  $stmtInt->execute();
  $int = $stmtInt->fetch(PDO::FETCH_ASSOC) ?: null;
  if ($int) {
    $internacaoAtual = [
      'id_internacao' => (int)($int['id_internacao'] ?? 0),
      'data'          => $fmtDate($int['data_intern_int'] ?? null),
      'hora'          => $int['hora_intern_int'] ?? null,
      'hospital_id'   => (int)($int['fk_hospital_int'] ?? 0),
      'status'        => ($int['internado_int'] ?? '') === 's' ? 'Internado' : 'Alta',
      'acomodacao'    => $int['acomodacao_int'] ?? null,
      'especialidade' => $int['especialidade_int'] ?? null,
      'grupo_pat'     => $int['grupo_patologia_int'] ?? null,
    ];
  }

  // 2) Última visita (escopo aplicado)
  $ultimaVisita = null;
  $stmtVis = $conn->prepare("SELECT
      vi.id_visita,
      vi.data_visita_vis,
      vi.visita_no_vis,
      vi.usuario_create,
      ho.nome_hosp,
      DATEDIFF(CURRENT_DATE(), vi.data_visita_vis) AS dias_desde_ultima_visita
    FROM tb_visita vi
    JOIN tb_internacao ac ON ac.id_internacao = vi.fk_internacao_vis
    LEFT JOIN tb_hospital ho ON ho.id_hospital = ac.fk_hospital_int
    WHERE ac.fk_paciente_int = :pac
      AND (vi.retificado IS NULL OR vi.retificado = 0)
      {$scopeIntSql}
    ORDER BY vi.data_visita_vis DESC, vi.id_visita DESC
    LIMIT 1");
  ajax_bind_params($stmtVis, array_merge([':pac' => (int)$pacId], $scopeIntParams));
  $stmtVis->execute();
  $v = $stmtVis->fetch(PDO::FETCH_ASSOC) ?: null;
  if ($v) {
    $ultimaVisita = [
      'id_visita'     => (int)($v['id_visita'] ?? 0),
      'data'          => $fmtDate($v['data_visita_vis'] ?? null),
      'visita_no'     => (int)($v['visita_no_vis'] ?? 0),
      'usuario'       => $v['usuario_create'] ?? null,
      'hospital'      => $v['nome_hosp'] ?? null,
      'dias_desde'    => isset($v['dias_desde_ultima_visita']) ? (int)$v['dias_desde_ultima_visita'] : null
    ];
  }

  // 3) Prorrogações recentes — Aguardando seu DAO de prorrogação
  $prorrogacoes = []; // TODO quando você enviar o DAO/consulta

  // 4) Exames recentes — Aguardando seu DAO/consulta de exames
  $exames = []; // TODO quando você enviar o DAO/consulta

  echo json_encode([
    'success'          => true,
    'internacao_atual' => $internacaoAtual,
    'ultima_visita'    => $ultimaVisita,
    'prorrogacoes'     => $prorrogacoes,
    'exames'           => $exames
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error'   => 'Erro interno',
  ]);
}
