<?php
require_once("templates/header.php");
require_once("models/message.php");

function centralE($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function centralDateTs(?string $value): ?int
{
    if (!$value) return null;
    $ts = strtotime(substr($value, 0, 10));
    return $ts === false ? null : $ts;
}

function centralDays(?string $from, ?int $until = null): int
{
    $fromTs = centralDateTs($from);
    if (!$fromTs) return 0;
    return max(0, (int)floor((($until ?? time()) - $fromTs) / 86400));
}

function centralResponsible(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') return 'Não atribuído';
    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $value = (string)strstr($value, '@', true);
    }
    $value = trim((string)preg_replace('/[._-]+/', ' ', $value));
    return $value !== '' ? mb_convert_case($value, MB_CASE_TITLE, 'UTF-8') : 'Não atribuído';
}

function centralUrl(array $params = []): string
{
    $filtered = array_filter($params, static fn($v) => $v !== null && $v !== '');
    $query = http_build_query($filtered);
    return 'central_operacional.php' . ($query ? '?' . $query : '');
}

$hospital = trim((string)filter_input(INPUT_GET, 'hospital', FILTER_SANITIZE_SPECIAL_CHARS));
$paciente = trim((string)filter_input(INPUT_GET, 'paciente', FILTER_SANITIZE_SPECIAL_CHARS));
$tipo = trim((string)filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_SPECIAL_CHARS));
$prioridade = trim((string)filter_input(INPUT_GET, 'prioridade', FILTER_SANITIZE_SPECIAL_CHARS));
$natureza = trim((string)filter_input(INPUT_GET, 'natureza', FILTER_SANITIZE_SPECIAL_CHARS));
$responsavel = trim((string)filter_input(INPUT_GET, 'responsavel', FILTER_SANITIZE_SPECIAL_CHARS));
$seguradoraId = (int)(filter_input(INPUT_GET, 'seguradora_id', FILTER_VALIDATE_INT) ?: 0);
$limiteSemVisita = max(1, min(60, (int)(filter_input(INPUT_GET, 'dias_sem_visita') ?: 7)));
$limite = (int)(filter_input(INPUT_GET, 'limite_pag') ?: 20);
$limite = in_array($limite, [10, 20, 50, 100], true) ? $limite : 20;
$pagina = max(1, (int)(filter_input(INPUT_GET, 'pag') ?: 1));

$seguradoras = [];
$items = [];
$loadError = null;
$todayTs = centralDateTs(date('Y-m-d')) ?? time();

try {
    $seguradoras = $conn->query("SELECT id_seguradora, seguradora_seg FROM tb_seguradora WHERE COALESCE(deletado_seg, 'n') <> 's' ORDER BY seguradora_seg")
        ->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $where = [];
    $params = [];
    if ($hospital !== '') {
        $where[] = 'ho.nome_hosp LIKE :hospital';
        $params[':hospital'] = '%' . $hospital . '%';
    }
    if ($paciente !== '') {
        $where[] = 'pa.nome_pac LIKE :paciente';
        $params[':paciente'] = '%' . $paciente . '%';
    }
    if ($seguradoraId > 0) {
        $where[] = 'pa.fk_seguradora_pac = :seguradora';
        $params[':seguradora'] = $seguradoraId;
    }
    if ($responsavel !== '') {
        $where[] = '(i.usuario_create_int LIKE :responsavel_int OR ca.usuario_create_cap LIKE :responsavel_cap)';
        $params[':responsavel_int'] = '%' . $responsavel . '%';
        $params[':responsavel_cap'] = '%' . $responsavel . '%';
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT
            i.id_internacao, i.data_intern_int, i.internado_int, i.senha_int,
            i.internado_uti_int, i.internacao_uti_int, i.usuario_create_int,
            pa.nome_pac, ho.nome_hosp, se.seguradora_seg,
            COALESCE(NULLIF(se.dias_visita_uti_seg, 0), NULLIF(se.dias_visita_seg, 0), 7) AS intervalo_visita,
            COALESCE(NULLIF(se.longa_permanencia_seg, 0), 30) AS limite_permanencia,
            (SELECT MAX(COALESCE(DATE(v.data_visita_vis), DATE(v.data_lancamento_vis)))
               FROM tb_visita v
              WHERE v.fk_internacao_vis = i.id_internacao
                AND (v.retificado IS NULL OR v.retificado IN (0, '0', '', 'n', 'N'))) AS ultima_visita,
            (SELECT MAX(DATE(v.data_visita_vis))
               FROM tb_visita v
              WHERE v.fk_internacao_vis = i.id_internacao
                AND LOWER(COALESCE(v.visita_med_vis, '')) IN ('s', 'sim', '1')) AS ultima_visita_med,
            alt.data_alta_alt, alt.tipo_alta_alt,
            ca.id_capeante, ca.data_create_cap, ca.usuario_create_cap, ca.conta_faturada_cap,
            COALESCE(ges.evento_adverso, 0) AS evento_adverso,
            COALESCE(ges.alto_custo, 0) AS alto_custo,
            COALESCE(ges.opme, 0) AS opme
        FROM tb_internacao i
        LEFT JOIN tb_paciente pa ON pa.id_paciente = i.fk_paciente_int
        LEFT JOIN tb_hospital ho ON ho.id_hospital = i.fk_hospital_int
        LEFT JOIN tb_seguradora se ON se.id_seguradora = pa.fk_seguradora_pac
        LEFT JOIN (
            SELECT a1.fk_id_int_alt, MAX(a1.data_alta_alt) AS data_alta_alt, MAX(a1.tipo_alta_alt) AS tipo_alta_alt
            FROM tb_alta a1 GROUP BY a1.fk_id_int_alt
        ) alt ON alt.fk_id_int_alt = i.id_internacao
        LEFT JOIN tb_capeante ca ON ca.fk_int_capeante = i.id_internacao
        LEFT JOIN (
            SELECT fk_internacao_ges,
                   MAX(CASE WHEN LOWER(COALESCE(evento_adverso_ges, 'n')) IN ('s','sim','1') THEN 1 ELSE 0 END) AS evento_adverso,
                   MAX(CASE WHEN LOWER(COALESCE(alto_custo_ges, 'n')) IN ('s','sim','1') THEN 1 ELSE 0 END) AS alto_custo,
                   MAX(CASE WHEN LOWER(COALESCE(opme_ges, 'n')) IN ('s','sim','1') THEN 1 ELSE 0 END) AS opme
            FROM tb_gestao GROUP BY fk_internacao_ges
        ) ges ON ges.fk_internacao_ges = i.id_internacao
        {$whereSql}
        ORDER BY i.id_internacao DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $internacaoIds = array_values(array_unique(array_filter(array_map(static fn($r) => (int)$r['id_internacao'], $rows))));
    $prorrogacoes = [];
    if ($internacaoIds) {
        $placeholders = implode(',', array_fill(0, count($internacaoIds), '?'));
        $prStmt = $conn->prepare("SELECT fk_internacao_pror, MAX(prorrog1_fim_pror) AS ultima_prorrogacao FROM tb_prorrogacao WHERE fk_internacao_pror IN ({$placeholders}) GROUP BY fk_internacao_pror");
        $prStmt->execute($internacaoIds);
        foreach ($prStmt->fetchAll(PDO::FETCH_ASSOC) as $pr) {
            $prorrogacoes[(int)$pr['fk_internacao_pror']] = $pr['ultima_prorrogacao'];
        }
    }

    $seenInternacao = [];
    foreach ($rows as $row) {
        $id = (int)$row['id_internacao'];
        $active = strtolower(trim((string)$row['internado_int'])) === 's';
        $age = centralDays($row['data_intern_int']);
        $base = [
            'internacao' => $id,
            'paciente' => $row['nome_pac'] ?: '--',
            'hospital' => $row['nome_hosp'] ?: '--',
            'seguradora' => $row['seguradora_seg'] ?: '--',
            'data' => $row['data_intern_int'],
            'responsavel' => centralResponsible($row['usuario_create_int']),
            'uti' => strtolower(trim((string)($row['internado_uti_int'] ?: $row['internacao_uti_int']))) === 's',
            'evento_adverso' => (int)$row['evento_adverso'] === 1,
            'alto_custo' => (int)$row['alto_custo'] === 1,
            'opme' => (int)$row['opme'] === 1,
        ];
        $add = static function (array $data) use (&$items): void { $items[] = $data; };

        if (!isset($seenInternacao[$id])) {
            $seenInternacao[$id] = true;

            if ($active && trim((string)$row['senha_int']) === '') {
                $add($base + ['tipo' => 'sem_senha', 'tipo_label' => 'Internação sem senha', 'prioridade' => $age >= 2 ? 'critica' : 'alta', 'dias' => $age, 'detalhe' => 'Senha ainda não informada.', 'acao' => 'Completar internação', 'url' => rtrim($BASE_URL, '/') . '/internacoes/editar/' . $id]);
            }

            if ($active) {
                $visitInterval = max(1, (int)$row['intervalo_visita']);
                $lastVisit = $row['ultima_visita'] ?: $row['data_intern_int'];
                $daysVisit = centralDays($lastVisit);
                if ($daysVisit > $visitInterval) {
                    $lastMed = $row['ultima_visita_med'] ?: $row['data_intern_int'];
                    $daysMed = centralDays($lastMed);
                    $visitDetail = 'Periodicidade contratada: ' . $visitInterval . ' dias.';
                    if ($daysMed > $limiteSemVisita) {
                        $visitDetail .= ' Sem visita médica há ' . $daysMed . ' dias.';
                    }
                    $add($base + ['tipo' => 'visita_vencida', 'tipo_label' => 'Visita vencida', 'prioridade' => $daysVisit > ($visitInterval * 2) ? 'critica' : 'alta', 'dias' => $daysVisit, 'detalhe' => $visitDetail, 'acao' => 'Lançar visita', 'url' => rtrim($BASE_URL, '/') . '/visitas/nova/internacao/' . $id]);
                } else {
                    $daysToVisit = $visitInterval - $daysVisit;
                    if ($daysToVisit <= 2) {
                        $add($base + ['tipo' => 'visita_a_vencer', 'tipo_label' => 'Visita próxima do prazo', 'prioridade' => 'media', 'dias' => 0, 'dias_para_vencer' => $daysToVisit, 'natureza' => 'preventivo', 'detalhe' => 'Periodicidade contratada: ' . $visitInterval . ' dias.', 'acao' => 'Programar visita', 'url' => rtrim($BASE_URL, '/') . '/visitas/nova/internacao/' . $id]);
                    }
                }

                $lastProrrog = $prorrogacoes[$id] ?? null;
                if (!$lastProrrog || (centralDateTs($lastProrrog) ?? 0) < $todayTs) {
                    $daysProrrog = $lastProrrog ? centralDays($lastProrrog) : $age;
                    $add($base + ['tipo' => 'prorrogacao_aberta', 'tipo_label' => 'Prorrogação em aberto', 'prioridade' => $daysProrrog >= 2 ? 'critica' : 'alta', 'dias' => $daysProrrog, 'detalhe' => $lastProrrog ? 'Última cobertura terminou em ' . date('d/m/Y', centralDateTs($lastProrrog)) . '.' : 'Nenhuma prorrogação registrada.', 'acao' => 'Editar prorrogação', 'url' => rtrim($BASE_URL, '/') . '/internacoes/editar/' . $id . '?section=prorrog#collapseProrrog']);
                } else {
                    $daysToProrrog = max(0, (int)floor(((centralDateTs($lastProrrog) ?? $todayTs) - $todayTs) / 86400));
                    if ($daysToProrrog <= 2) {
                        $add($base + ['tipo' => 'prorrogacao_a_vencer', 'tipo_label' => 'Prorrogação próxima do prazo', 'prioridade' => 'media', 'dias' => 0, 'dias_para_vencer' => $daysToProrrog, 'natureza' => 'preventivo', 'detalhe' => 'Cobertura termina em ' . date('d/m/Y', centralDateTs($lastProrrog)) . '.', 'acao' => 'Revisar prorrogação', 'url' => rtrim($BASE_URL, '/') . '/internacoes/editar/' . $id . '?section=prorrog#collapseProrrog']);
                    }
                }

            }

            if (!empty($row['data_alta_alt']) && trim((string)$row['tipo_alta_alt']) === '') {
                $daysAlta = centralDays($row['data_alta_alt']);
                $add($base + ['tipo' => 'alta_sem_fechamento', 'tipo_label' => 'Alta sem fechamento', 'prioridade' => $daysAlta >= 2 ? 'critica' : 'alta', 'dias' => $daysAlta, 'detalhe' => 'Data de alta sem motivo de fechamento.', 'acao' => 'Fechar alta', 'url' => rtrim($BASE_URL, '/') . '/edit_alta.php?type=alta&id_internacao=' . $id]);
            }
        }

        if (!empty($row['id_capeante']) && in_array(strtolower(trim((string)$row['conta_faturada_cap'])), ['', 'n', '0'], true)) {
            $daysConta = centralDays($row['data_create_cap'] ?: $row['data_intern_int']);
            $add($base + ['tipo' => 'conta_pendente', 'tipo_label' => 'Conta não faturada', 'prioridade' => $daysConta >= 15 ? 'alta' : 'media', 'dias' => $daysConta, 'responsavel' => centralResponsible($row['usuario_create_cap']), 'detalhe' => 'Conta #' . (int)$row['id_capeante'] . ' aguardando faturamento.', 'acao' => 'Abrir conta', 'url' => rtrim($BASE_URL, '/') . '/contas/auditar/' . (int)$row['id_capeante']]);
        }
    }
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}

foreach ($items as &$item) {
    $item['natureza'] = $item['natureza'] ?? 'vencido';
    $score = 0;
    $motivos = [];
    $baseScores = [
        'sem_senha' => 20, 'visita_vencida' => 25, 'prorrogacao_aberta' => 25,
        'alta_sem_fechamento' => 20, 'conta_pendente' => 12,
        'visita_a_vencer' => 18, 'prorrogacao_a_vencer' => 20,
    ];
    $score += $baseScores[$item['tipo']] ?? 10;
    $motivos[] = 'Tipo da ocorrência +' . ($baseScores[$item['tipo']] ?? 10);
    if ($item['natureza'] === 'preventivo') {
        $daysToDue = max(0, (int)($item['dias_para_vencer'] ?? 0));
        $urgency = $daysToDue === 0 ? 25 : ($daysToDue === 1 ? 20 : 15);
        $score += $urgency;
        $motivos[] = 'Proximidade do prazo +' . $urgency;
        $item['tempo_label'] = $daysToDue === 0 ? 'vence hoje' : 'vence em ' . $daysToDue . ' dia' . ($daysToDue === 1 ? '' : 's');
    } else {
        $delayScore = min(30, max(0, (int)$item['dias']));
        $score += $delayScore;
        if ($delayScore > 0) $motivos[] = 'Tempo em atraso +' . $delayScore;
        $item['tempo_label'] = (int)$item['dias'] . ' dias';
    }
    if (!empty($item['uti'])) { $score += 20; $motivos[] = 'Paciente em UTI +20'; }
    if (!empty($item['evento_adverso'])) { $score += 25; $motivos[] = 'Evento adverso +25'; }
    if (!empty($item['alto_custo'])) { $score += 15; $motivos[] = 'Alto custo +15'; }
    if (!empty($item['opme'])) { $score += 10; $motivos[] = 'OPME +10'; }
    $item['score'] = min(100, $score);
    $item['score_motivos'] = $motivos;
    $item['risk_labels'] = array_values(array_filter([
        !empty($item['uti']) ? 'UTI' : null,
        !empty($item['evento_adverso']) ? 'Evento adverso' : null,
        !empty($item['alto_custo']) ? 'Alto custo' : null,
        !empty($item['opme']) ? 'OPME' : null,
    ]));
    $item['prioridade'] = $item['score'] >= 70 ? 'critica' : ($item['score'] >= 40 ? 'alta' : 'media');
}
unset($item);

if ($tipo !== '') $items = array_values(array_filter($items, static fn($i) => $i['tipo'] === $tipo));
if ($prioridade !== '') $items = array_values(array_filter($items, static fn($i) => $i['prioridade'] === $prioridade));
if ($natureza !== '') $items = array_values(array_filter($items, static fn($i) => $i['natureza'] === $natureza));

$priorityOrder = ['critica' => 0, 'alta' => 1, 'media' => 2, 'baixa' => 3];
usort($items, static function ($a, $b) use ($priorityOrder) {
    return [$priorityOrder[$a['prioridade']] ?? 9, -$a['score'], -$a['dias'], -$a['internacao']]
        <=> [$priorityOrder[$b['prioridade']] ?? 9, -$b['score'], -$b['dias'], -$b['internacao']];
});

$summary = ['total' => count($items), 'critica' => 0, 'alta' => 0, 'media' => 0, 'preventivo' => 0];
foreach ($items as $item) {
    if (isset($summary[$item['prioridade']])) $summary[$item['prioridade']]++;
    if ($item['natureza'] === 'preventivo') $summary['preventivo']++;
}
$totalPages = max(1, (int)ceil(count($items) / $limite));
$pagina = min($pagina, $totalPages);
$pageItems = array_slice($items, ($pagina - 1) * $limite, $limite);
$baseParams = [
    'hospital' => $hospital, 'paciente' => $paciente, 'tipo' => $tipo, 'prioridade' => $prioridade, 'natureza' => $natureza,
    'responsavel' => $responsavel, 'seguradora_id' => $seguradoraId,
    'dias_sem_visita' => $limiteSemVisita, 'limite_pag' => $limite,
];
?>

<link rel="stylesheet" href="<?= centralE(rtrim($BASE_URL, '/') . '/css/central_operacional.css?v=' . filemtime(__DIR__ . '/../css/central_operacional.css')) ?>">

<main class="container-fluid central-page">
    <header class="central-hero">
        <div>
            <p class="central-kicker">Gestão integrada</p>
            <h1>Central Operacional</h1>
        </div>
        <span class="central-updated"><i class="bi bi-clock-history"></i> Atualizado agora</span>
    </header>

    <section class="central-summary" aria-label="Resumo da fila">
        <a class="central-summary-card central-summary-card--total" href="<?= centralE(centralUrl($baseParams + ['prioridade' => ''])) ?>"><span>Total na fila</span><strong><?= (int)$summary['total'] ?></strong></a>
        <a class="central-summary-card central-summary-card--critical" href="<?= centralE(centralUrl($baseParams + ['prioridade' => 'critica'])) ?>"><span>Críticas</span><strong><?= (int)$summary['critica'] ?></strong></a>
        <a class="central-summary-card central-summary-card--high" href="<?= centralE(centralUrl($baseParams + ['prioridade' => 'alta'])) ?>"><span>Alta prioridade</span><strong><?= (int)$summary['alta'] ?></strong></a>
        <a class="central-summary-card central-summary-card--medium" href="<?= centralE(centralUrl($baseParams + ['prioridade' => 'media'])) ?>"><span>Média prioridade</span><strong><?= (int)$summary['media'] ?></strong></a>
        <a class="central-summary-card central-summary-card--preventive" href="<?= centralE(centralUrl($baseParams + ['natureza' => 'preventivo', 'prioridade' => ''])) ?>"><span>Alertas preventivos</span><strong><?= (int)$summary['preventivo'] ?></strong></a>
    </section>

    <section class="central-panel">
        <form method="get" class="central-filters">
            <input class="form-control form-control-sm" name="hospital" placeholder="Hospital" value="<?= centralE($hospital) ?>">
            <input class="form-control form-control-sm" name="paciente" placeholder="Paciente" value="<?= centralE($paciente) ?>">
            <select class="form-select form-select-sm" name="seguradora_id">
                <option value="">Todas as seguradoras</option>
                <?php foreach ($seguradoras as $seg): ?><option value="<?= (int)$seg['id_seguradora'] ?>" <?= $seguradoraId === (int)$seg['id_seguradora'] ? 'selected' : '' ?>><?= centralE($seg['seguradora_seg']) ?></option><?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm" name="tipo">
                <option value="">Todos os tipos</option>
                <option value="sem_senha" <?= $tipo === 'sem_senha' ? 'selected' : '' ?>>Internação sem senha</option>
                <option value="visita_vencida" <?= $tipo === 'visita_vencida' ? 'selected' : '' ?>>Visita vencida</option>
                <option value="prorrogacao_aberta" <?= $tipo === 'prorrogacao_aberta' ? 'selected' : '' ?>>Prorrogação em aberto</option>
                <option value="alta_sem_fechamento" <?= $tipo === 'alta_sem_fechamento' ? 'selected' : '' ?>>Alta sem fechamento</option>
                <option value="conta_pendente" <?= $tipo === 'conta_pendente' ? 'selected' : '' ?>>Conta não faturada</option>
            </select>
            <select class="form-select form-select-sm" name="prioridade">
                <option value="">Todas as prioridades</option>
                <option value="critica" <?= $prioridade === 'critica' ? 'selected' : '' ?>>Crítica</option>
                <option value="alta" <?= $prioridade === 'alta' ? 'selected' : '' ?>>Alta</option>
                <option value="media" <?= $prioridade === 'media' ? 'selected' : '' ?>>Média</option>
            </select>
            <select class="form-select form-select-sm" name="natureza">
                <option value="">Vencidos e preventivos</option>
                <option value="vencido" <?= $natureza === 'vencido' ? 'selected' : '' ?>>Pendências vencidas</option>
                <option value="preventivo" <?= $natureza === 'preventivo' ? 'selected' : '' ?>>Alertas preventivos</option>
            </select>
            <input class="form-control form-control-sm" name="responsavel" placeholder="Responsável" value="<?= centralE($responsavel) ?>">
            <input type="hidden" name="dias_sem_visita" value="<?= (int)$limiteSemVisita ?>">
            <input type="hidden" name="limite_pag" value="<?= (int)$limite ?>">
            <div class="central-filter-actions">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i> Filtrar</button>
                <a class="btn btn-outline-secondary btn-sm" href="central_operacional.php" title="Limpar filtros"><i class="bi bi-trash3"></i></a>
            </div>
        </form>

        <?php if ($loadError): ?><div class="alert alert-warning central-alert">Não foi possível carregar toda a fila. <?= centralE($loadError) ?></div><?php endif; ?>

        <div class="table-responsive">
            <table class="table table-sm central-table">
                <thead><tr><th>Prioridade</th><th>Ocorrência</th><th>Paciente</th><th>Hospital</th><th>Responsável</th><th>Prazo</th><th>Contexto</th><th>Ação</th></tr></thead>
                <tbody>
                <?php if (!$pageItems): ?>
                    <tr><td colspan="8" class="central-empty"><i class="bi bi-check-circle"></i> Nenhuma pendência para os filtros aplicados.</td></tr>
                <?php else: foreach ($pageItems as $item): ?>
                    <tr>
                        <td><span class="central-priority central-priority--<?= centralE($item['prioridade']) ?>"><?= centralE(ucfirst($item['prioridade'])) ?></span><span class="central-score" title="Composição: <?= centralE(implode(' | ', $item['score_motivos'])) ?>"><?= (int)$item['score'] ?> pontos</span></td>
                        <td><strong class="central-occurrence"><?= centralE($item['tipo_label']) ?></strong><small class="central-nature central-nature--<?= centralE($item['natureza']) ?>"><?= $item['natureza'] === 'preventivo' ? 'Preventivo' : 'Vencido' ?></small></td>
                        <td><strong class="central-patient-name"><?= centralE($item['paciente']) ?></strong><div class="central-secondary">Internação #<?= (int)$item['internacao'] ?></div></td>
                        <td><strong class="central-hospital-name"><?= centralE($item['hospital']) ?></strong><div class="central-secondary"><?= centralE($item['seguradora']) ?></div></td>
                        <td><?= centralE($item['responsavel']) ?></td>
                        <td><strong><?= centralE($item['tempo_label']) ?></strong></td>
                        <td><span class="central-detail"><?= centralE($item['detalhe']) ?></span><?php if ($item['risk_labels']): ?><span class="central-risk-list"><?php foreach ($item['risk_labels'] as $risk): ?><span class="central-risk"><?= centralE($risk) ?></span><?php endforeach; ?></span><?php endif; ?></td>
                        <td><a class="btn btn-outline-primary btn-sm central-action" href="<?= centralE($item['url']) ?>"><?= centralE($item['acao']) ?></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="central-footer">
            <span>Exibindo <?= count($pageItems) ?> de <?= count($items) ?> pendências</span>
            <?php if ($totalPages > 1): ?><nav><ul class="pagination pagination-sm mb-0">
                <?php for ($p = max(1, $pagina - 3); $p <= min($totalPages, $pagina + 3); $p++): ?><li class="page-item <?= $p === $pagina ? 'active' : '' ?>"><a class="page-link" href="<?= centralE(centralUrl($baseParams + ['pag' => $p])) ?>"><?= $p ?></a></li><?php endfor; ?>
            </ul></nav><?php endif; ?>
        </footer>
    </section>
</main>
