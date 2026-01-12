<?php
include_once __DIR__ . '/../check_logado.php';
require_once __DIR__ . '/header.php';

function biRedeFilterValue(string $key, string $fallback = ''): string
{
    $value = filter_input(INPUT_GET, $key, FILTER_DEFAULT);
    if ($value === null) {
        return $fallback;
    }
    return trim((string)$value);
}

$today = date('Y-m-d');
$filterValues = [
    'data_ini' => biRedeFilterValue('data_ini', date('Y-m-d', strtotime('-180 days'))),
    'data_fim' => biRedeFilterValue('data_fim', $today),
    'hospital_id' => biRedeFilterValue('hospital_id', ''),
    'seguradora_id' => biRedeFilterValue('seguradora_id', ''),
    'regiao' => biRedeFilterValue('regiao', ''),
    'tipo_admissao' => biRedeFilterValue('tipo_admissao', ''),
    'modo_internacao' => biRedeFilterValue('modo_internacao', ''),
    'uti' => biRedeFilterValue('uti', ''),
];

$hospitais = $conn->query("SELECT id_hospital, nome_hosp FROM tb_hospital ORDER BY nome_hosp")
    ->fetchAll(PDO::FETCH_ASSOC);
$seguradoras = $conn->query("SELECT id_seguradora, seguradora_seg FROM tb_seguradora ORDER BY seguradora_seg")
    ->fetchAll(PDO::FETCH_ASSOC);
$regioes = $conn->query("SELECT DISTINCT estado_hosp FROM tb_hospital WHERE estado_hosp IS NOT NULL AND estado_hosp <> '' ORDER BY estado_hosp")
    ->fetchAll(PDO::FETCH_COLUMN);
$tiposAdm = $conn->query("SELECT DISTINCT tipo_admissao_int FROM tb_internacao WHERE tipo_admissao_int IS NOT NULL AND tipo_admissao_int <> '' ORDER BY tipo_admissao_int")
    ->fetchAll(PDO::FETCH_COLUMN);
$modosInt = $conn->query("SELECT DISTINCT modo_internacao_int FROM tb_internacao WHERE modo_internacao_int IS NOT NULL AND modo_internacao_int <> '' ORDER BY modo_internacao_int")
    ->fetchAll(PDO::FETCH_COLUMN);

if (!function_exists('fmtInt')) {
    function fmtInt($value): string
    {
        return number_format((int)$value, 0, ',', '.');
    }
}

if (!function_exists('fmtFloat')) {
    function fmtFloat($value, int $decimals = 1): string
    {
        return number_format((float)$value, $decimals, ',', '.');
    }
}

if (!function_exists('fmtMoney')) {
    function fmtMoney($value): string
    {
        return 'R$ ' . number_format((float)$value, 2, ',', '.');
    }
}

if (!function_exists('fmtPct')) {
    function fmtPct($value, int $decimals = 1): string
    {
        return number_format((float)$value, $decimals, ',', '.') . '%';
    }
}

if (!function_exists('biBindParams')) {
    function biBindParams(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
    }
}

if (!function_exists('biRedeBuildWhere')) {
    /**
     * Monta cláusula WHERE com filtros comuns da rede hospitalar.
     */
    function biRedeBuildWhere(array $filterValues, string $dateExpr, string $alias = 'i', bool $withUti = false): array
    {
        $whereParts = [
            "{$dateExpr} BETWEEN :data_ini AND :data_fim",
        ];
        $params = [
            ':data_ini' => $filterValues['data_ini'],
            ':data_fim' => $filterValues['data_fim'],
        ];

        if ($filterValues['hospital_id'] !== '') {
            $whereParts[] = "{$alias}.fk_hospital_int = :hospital_id";
            $params[':hospital_id'] = (int)$filterValues['hospital_id'];
        }

        $joinParts = [];
        if ($filterValues['seguradora_id'] !== '') {
            $joinParts[] = "LEFT JOIN tb_paciente bi_pa ON bi_pa.id_paciente = {$alias}.fk_paciente_int";
            $joinParts[] = "LEFT JOIN tb_seguradora bi_seg ON bi_seg.id_seguradora = bi_pa.fk_seguradora_pac";
            $whereParts[] = "bi_seg.id_seguradora = :seguradora_id";
            $params[':seguradora_id'] = (int)$filterValues['seguradora_id'];
        }

        if ($filterValues['regiao'] !== '') {
            $whereParts[] = "h.estado_hosp = :regiao";
            $params[':regiao'] = $filterValues['regiao'];
        }

        if ($filterValues['tipo_admissao'] !== '') {
            $whereParts[] = "{$alias}.tipo_admissao_int = :tipo_admissao";
            $params[':tipo_admissao'] = $filterValues['tipo_admissao'];
        }

        if ($filterValues['modo_internacao'] !== '') {
            $whereParts[] = "{$alias}.modo_internacao_int = :modo_internacao";
            $params[':modo_internacao'] = $filterValues['modo_internacao'];
        }

        if ($withUti) {
            $joinParts[] = "LEFT JOIN (SELECT DISTINCT fk_internacao_uti FROM tb_uti) ut ON ut.fk_internacao_uti = {$alias}.id_internacao";
            if ($filterValues['uti'] === 's') {
                $whereParts[] = "ut.fk_internacao_uti IS NOT NULL";
            } elseif ($filterValues['uti'] === 'n') {
                $whereParts[] = "ut.fk_internacao_uti IS NULL";
            }
        }

        return [
            'where' => implode(' AND ', $whereParts),
            'params' => $params,
            'joins' => $joinParts ? "\n" . implode("\n", $joinParts) : '',
        ];
    }
}
