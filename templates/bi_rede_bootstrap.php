<?php
include_once("check_logado.php");
require_once("templates/header.php");

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Conexao invalida.");
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function fmtMoney($value): string
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function fmtInt($value): string
{
    return number_format((int)$value, 0, ',', '.');
}

function fmtFloat($value, int $dec = 1): string
{
    return number_format((float)$value, $dec, ',', '.');
}

function fmtPct($value, int $dec = 1): string
{
    return fmtFloat($value, $dec) . '%';
}

function biBindParams(PDOStatement $stmt, array $params): void
{
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

function biRedeFetchOptions(PDO $conn, string $sql, string $valueKey, string $labelKey): array
{
    try {
        $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
    $options = [];
    foreach ($rows as $row) {
        $value = $row[$valueKey] ?? null;
        $label = $row[$labelKey] ?? null;
        if ($value === null || $label === null) {
            continue;
        }
        $options[] = [
            'value' => $value,
            'label' => $label,
        ];
    }
    return $options;
}

$hoje = date('Y-m-d');
$filterValues = [
    'data_ini' => filter_input(INPUT_GET, 'data_ini') ?: date('Y-m-d', strtotime('-180 days')),
    'data_fim' => filter_input(INPUT_GET, 'data_fim') ?: $hoje,
    'hospital_id' => filter_input(INPUT_GET, 'hospital_id', FILTER_VALIDATE_INT) ?: '',
    'seguradora_id' => filter_input(INPUT_GET, 'seguradora_id', FILTER_VALIDATE_INT) ?: '',
    'regiao' => trim((string)(filter_input(INPUT_GET, 'regiao') ?? '')),
    'tipo_admissao' => trim((string)(filter_input(INPUT_GET, 'tipo_admissao') ?? '')),
    'modo_internacao' => trim((string)(filter_input(INPUT_GET, 'modo_internacao') ?? '')),
    'uti' => trim((string)(filter_input(INPUT_GET, 'uti') ?? '')),
];

$filterOptions = [
    'hospitais' => biRedeFetchOptions($conn, "SELECT id_hospital, nome_hosp FROM tb_hospital ORDER BY nome_hosp", 'id_hospital', 'nome_hosp'),
    'seguradoras' => biRedeFetchOptions($conn, "SELECT id_seguradora, seguradora_seg FROM tb_seguradora ORDER BY seguradora_seg", 'id_seguradora', 'seguradora_seg'),
    'tipos_admissao' => biRedeFetchOptions($conn, "SELECT DISTINCT tipo_admissao_int AS label, tipo_admissao_int AS value FROM tb_internacao WHERE tipo_admissao_int IS NOT NULL AND tipo_admissao_int <> '' ORDER BY tipo_admissao_int", 'value', 'label'),
    'modos_internacao' => biRedeFetchOptions($conn, "SELECT DISTINCT modo_internacao_int AS label, modo_internacao_int AS value FROM tb_internacao WHERE modo_internacao_int IS NOT NULL AND modo_internacao_int <> '' ORDER BY modo_internacao_int", 'value', 'label'),
    'regioes' => [
        ['value' => 'Norte', 'label' => 'Norte'],
        ['value' => 'Nordeste', 'label' => 'Nordeste'],
        ['value' => 'Centro-Oeste', 'label' => 'Centro-Oeste'],
        ['value' => 'Sudeste', 'label' => 'Sudeste'],
        ['value' => 'Sul', 'label' => 'Sul'],
    ],
];

$biRegionStates = [
    'Norte' => ['AC', 'AP', 'AM', 'PA', 'RO', 'RR', 'TO'],
    'Nordeste' => ['AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE'],
    'Centro-Oeste' => ['DF', 'GO', 'MT', 'MS'],
    'Sudeste' => ['ES', 'MG', 'RJ', 'SP'],
    'Sul' => ['PR', 'RS', 'SC'],
];

function biRedeBuildWhere(array $filters, string $dateField, string $alias = 'i', bool $withUti = false): array
{
    $where = "{$dateField} BETWEEN :data_ini AND :data_fim";
    $params = [
        ':data_ini' => $filters['data_ini'],
        ':data_fim' => $filters['data_fim'],
    ];
    $joins = [];

    if (!empty($filters['hospital_id'])) {
        $where .= " AND {$alias}.fk_hospital_int = :hospital_id";
        $params[':hospital_id'] = (int)$filters['hospital_id'];
    }
    if (!empty($filters['tipo_admissao'])) {
        $where .= " AND {$alias}.tipo_admissao_int = :tipo_admissao";
        $params[':tipo_admissao'] = $filters['tipo_admissao'];
    }
    if (!empty($filters['modo_internacao'])) {
        $where .= " AND {$alias}.modo_internacao_int = :modo_internacao";
        $params[':modo_internacao'] = $filters['modo_internacao'];
    }
    if (!empty($filters['seguradora_id'])) {
        $joins[] = "LEFT JOIN tb_paciente pa ON pa.id_paciente = {$alias}.fk_paciente_int";
        $where .= " AND pa.fk_seguradora_pac = :seguradora_id";
        $params[':seguradora_id'] = (int)$filters['seguradora_id'];
    }

    if (!empty($filters['regiao'])) {
        $regions = [
            'Norte' => ['AC', 'AP', 'AM', 'PA', 'RO', 'RR', 'TO'],
            'Nordeste' => ['AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE'],
            'Centro-Oeste' => ['DF', 'GO', 'MT', 'MS'],
            'Sudeste' => ['ES', 'MG', 'RJ', 'SP'],
            'Sul' => ['PR', 'RS', 'SC'],
        ];
        $states = $regions[$filters['regiao']] ?? [];
        if ($states) {
            $joins[] = "LEFT JOIN tb_hospital h ON h.id_hospital = {$alias}.fk_hospital_int";
            $stateList = "'" . implode("','", array_map('strtoupper', $states)) . "'";
            $where .= " AND UPPER(COALESCE(h.estado_hosp, '')) IN ({$stateList})";
        }
    }

    if ($withUti && !empty($filters['uti'])) {
        $joins[] = "LEFT JOIN tb_uti ut ON ut.fk_internacao_uti = {$alias}.id_internacao";
        if ($filters['uti'] === 's') {
            $where .= " AND ut.fk_internacao_uti IS NOT NULL";
        } elseif ($filters['uti'] === 'n') {
            $where .= " AND ut.fk_internacao_uti IS NULL";
        }
    }

    $joins = array_unique($joins);

    return [
        'where' => $where,
        'params' => $params,
        'joins' => $joins ? implode("\n", $joins) : '',
    ];
}
?>
