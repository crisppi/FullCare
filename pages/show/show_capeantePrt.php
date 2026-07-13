<?php
include_once("check_logado.php");
include_once("globals.php");
include_once("models/internacao.php");
require_once("dao/internacaoDao.php");
include_once("models/hospital.php");
include_once("dao/hospitalDao.php");
include_once("models/patologia.php");
include_once("dao/patologiaDao.php");
include_once("models/paciente.php");
include_once("dao/pacienteDAO.php");
include_once("models/capeante.php");
include_once("dao/capeanteDAO.php");
require_once("dao/CapValoresAPDao.php");
require_once("dao/CapValoresUTIDao.php");
require_once("dao/CapValoresCCDao.php");
require_once("dao/CapValoresOutDao.php");
require_once("dao/CapValoresDiarDao.php");

$id_capeante = filter_input(INPUT_GET, "id_capeante", FILTER_SANITIZE_NUMBER_INT);
$idsParam = (string)($_GET['ids'] ?? '');
$modelo = (string)($_GET['modelo'] ?? 'resumido');
$modeloCompleto = $modelo === 'completo';

$h = static function ($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$fmtDate = static function ($value): string {
    if (empty($value) || $value === '0000-00-00') {
        return '-';
    }

    $ts = strtotime((string)$value);
    return $ts ? date('d/m/Y', $ts) : '-';
};

$fmtMoney = static function ($value): string {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
};

function cap_print_brl_to_float($value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }

    $value = preg_replace('/[^\d.,\-]/', '', (string)$value);
    if (strpos($value, ',') !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } else {
        $value = str_replace(',', '.', $value);
    }

    $number = (float)$value;
    return is_finite($number) ? $number : 0.0;
}

function cap_print_group_from_row($row, array $map): array
{
    if (!$row) {
        return [];
    }
    if (is_object($row)) {
        $row = get_object_vars($row);
    }
    if (!is_array($row)) {
        return [];
    }

    $lines = [];
    foreach ($map as $label => $prefix) {
        $qtd = (int)($row[$prefix . '_qtd'] ?? 0);
        $cob = cap_print_brl_to_float($row[$prefix . '_cobrado'] ?? $row[$prefix . '_cob'] ?? 0);
        $glo = cap_print_brl_to_float($row[$prefix . '_glosado'] ?? $row[$prefix . '_glo'] ?? 0);
        $lib = cap_print_brl_to_float($row[$prefix . '_liberado'] ?? $row[$prefix . '_lib'] ?? null);
        if (!isset($row[$prefix . '_liberado']) && !isset($row[$prefix . '_lib'])) {
            $lib = max(0, $cob - $glo);
        }
        $obs = trim((string)($row[$prefix . '_obs'] ?? ''));
        if (!$qtd && !$cob && !$glo && !$lib && $obs === '') {
            continue;
        }
        $lines[] = [
            'desc' => $label,
            'qtd' => $qtd,
            'cobrado' => $cob,
            'glosado' => $glo,
            'liberado' => $lib,
            'obs' => $obs,
        ];
    }

    return $lines;
}

function cap_print_group_from_db(PDO $conn, int $fkCapeante, string $table, array $map): array
{
    if ($fkCapeante <= 0) {
        return [];
    }

    $stmt = $conn->prepare("SELECT * FROM {$table} WHERE fk_capeante = :fk LIMIT 1");
    $stmt->bindValue(':fk', $fkCapeante, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return cap_print_group_from_row($row ?: null, $map);
}

$monthsPt = [
    1 => 'janeiro',
    2 => 'fevereiro',
    3 => 'março',
    4 => 'abril',
    5 => 'maio',
    6 => 'junho',
    7 => 'julho',
    8 => 'agosto',
    9 => 'setembro',
    10 => 'outubro',
    11 => 'novembro',
    12 => 'dezembro',
];
$printDate = 'São Paulo, ' . date('d') . ' de ' . $monthsPt[(int)date('n')] . ' de ' . date('Y') . '.';

$rowsConta = [
    ['Valor Apresentado', $fmtMoney($capeante['valor_apresentado_capeante'] ?? 0)],
    ['Valor Final', $fmtMoney($capeante['valor_final_capeante'] ?? 0)],
];

$rowsGlosas = [
    ['Glosa Total', $fmtMoney($capeante['valor_glosa_total'] ?? 0)],
    ['Glosa Médica', $fmtMoney($capeante['valor_glosa_med'] ?? 0)],
    ['Glosa Enfermagem', $fmtMoney($capeante['valor_glosa_enf'] ?? 0)],
];

$rowsSeguimento = [
    ['Honorários', $fmtMoney($capeante['valor_honorarios'] ?? 0)],
    ['MatMed', $fmtMoney($capeante['valor_matmed'] ?? 0)],
    ['SADT', $fmtMoney($capeante['valor_sadt'] ?? 0)],
    ['Oxigenioterapia', $fmtMoney($capeante['valor_oxig'] ?? 0)],
    ['Taxas', $fmtMoney($capeante['valor_taxa'] ?? 0)],
];

$rowsGlosasSeguimento = [
    ['Honorários', $fmtMoney($capeante['glosa_honorarios'] ?? 0)],
    ['MatMed', $fmtMoney($capeante['glosa_matmed'] ?? 0)],
    ['SADT', $fmtMoney($capeante['glosa_sadt'] ?? 0)],
    ['Oxigenioterapia', $fmtMoney($capeante['glosa_oxig'] ?? 0)],
    ['Taxas', $fmtMoney($capeante['glosa_taxas'] ?? 0)],
];

$assinaturas = [
    ['Médico(a) Auditor(a)', $capeante['nome_med'] ?? ''],
    ['Enfermeiro(a) Auditor(a)', $capeante['nome_enf'] ?? ''],
    ['Administrativo(a)', $capeante['nome_adm'] ?? ''],
    ['Responsável Hospital', $capeante['nome_aud_hosp'] ?? ''],
];

$mapDiarias = [
    'Quarto / Apto' => 'ac_quarto',
    'Day Clinic' => 'ac_dayclinic',
    'UTI' => 'ac_uti',
    'UTI / Semi' => 'ac_utisemi',
    'Enfermaria' => 'ac_enfermaria',
    'Berçário' => 'ac_bercario',
    'Acompanhante' => 'ac_acompanhante',
    'Isolamento' => 'ac_isolamento',
];
$mapApto = [
    'Terapias' => 'ap_terapias',
    'Taxas' => 'ap_taxas',
    'Mat. Consumo' => 'ap_mat_consumo',
    'Medicamentos' => 'ap_medicametos',
    'Gases' => 'ap_gases',
    'OPME' => 'ap_mat_espec',
    'Exames' => 'ap_exames',
    'Hemoderivados' => 'ap_hemoderivados',
    'Honorários' => 'ap_honorarios',
];
$mapUti = [
    'Terapias' => 'uti_terapias',
    'Taxas' => 'uti_taxas',
    'Mat. Consumo' => 'uti_mat_consumo',
    'Medicamentos' => 'uti_medicametos',
    'Gases' => 'uti_gases',
    'OPME' => 'uti_mat_espec',
    'Exames' => 'uti_exames',
    'Hemoderivados' => 'uti_hemoderivados',
    'Honorários' => 'uti_honorarios',
];
$mapCc = [
    'Terapias' => 'cc_terapias',
    'Taxas' => 'cc_taxas',
    'Mat. Consumo' => 'cc_mat_consumo',
    'Medicamentos' => 'cc_medicametos',
    'Gases' => 'cc_gases',
    'OPME' => 'cc_mat_espec',
    'Exames' => 'cc_exames',
    'Hemoderivados' => 'cc_hemoderivados',
    'Honorários' => 'cc_honorarios',
];
$mapOutros = [
    'Pacote' => 'outros_pacote',
    'Remoção' => 'outros_remocao',
];

$sumDetail = static function (array $groups, string $key): float {
    $total = 0.0;
    foreach ($groups as $rows) {
        foreach ($rows as $row) {
            $total += (float)($row[$key] ?? 0);
        }
    }
    return $total;
};

$capeanteIds = [];
foreach (preg_split('/[\s,;]+/', $idsParam) ?: [] as $rawId) {
    $id = (int)$rawId;
    if ($id > 0) {
        $capeanteIds[] = $id;
    }
}
if (empty($capeanteIds) && (int)$id_capeante > 0) {
    $capeanteIds[] = (int)$id_capeante;
}
$capeanteIds = array_values(array_unique($capeanteIds));

$capeanteDao = new capeanteDAO($conn, $BASE_URL);
$loadCapeante = static function (capeanteDAO $dao, int $id): ?array {
    $rows = $dao->selectAllcapeante('ca.id_capeante = "' . $id . '"', null, null);
    return $rows[0] ?? null;
};

$buildPrintContext = static function (?array $capeante, int $idCapeante) use (
    $modeloCompleto,
    $conn,
    $fmtMoney,
    $mapDiarias,
    $mapApto,
    $mapUti,
    $mapCc,
    $mapOutros,
    $sumDetail
): array {
    $rowsConta = [
        ['Valor Apresentado', $fmtMoney($capeante['valor_apresentado_capeante'] ?? 0)],
        ['Valor Final', $fmtMoney($capeante['valor_final_capeante'] ?? 0)],
    ];

    $rowsGlosas = [
        ['Glosa Total', $fmtMoney($capeante['valor_glosa_total'] ?? 0)],
        ['Glosa Médica', $fmtMoney($capeante['valor_glosa_med'] ?? 0)],
        ['Glosa Enfermagem', $fmtMoney($capeante['valor_glosa_enf'] ?? 0)],
    ];

    $rowsSeguimento = [
        ['Honorários', $fmtMoney($capeante['valor_honorarios'] ?? 0)],
        ['MatMed', $fmtMoney($capeante['valor_matmed'] ?? 0)],
        ['SADT', $fmtMoney($capeante['valor_sadt'] ?? 0)],
        ['Oxigenioterapia', $fmtMoney($capeante['valor_oxig'] ?? 0)],
        ['Taxas', $fmtMoney($capeante['valor_taxa'] ?? 0)],
    ];

    $rowsGlosasSeguimento = [
        ['Honorários', $fmtMoney($capeante['glosa_honorarios'] ?? 0)],
        ['MatMed', $fmtMoney($capeante['glosa_matmed'] ?? 0)],
        ['SADT', $fmtMoney($capeante['glosa_sadt'] ?? 0)],
        ['Oxigenioterapia', $fmtMoney($capeante['glosa_oxig'] ?? 0)],
        ['Taxas', $fmtMoney($capeante['glosa_taxas'] ?? 0)],
    ];

    $assinaturas = [
        ['Médico(a) Auditor(a)', $capeante['nome_med'] ?? ''],
        ['Enfermeiro(a) Auditor(a)', $capeante['nome_enf'] ?? ''],
        ['Administrativo(a)', $capeante['nome_adm'] ?? ''],
        ['Responsável Hospital', $capeante['nome_aud_hosp'] ?? ''],
    ];

    $gruposDetalhados = [];
    $observacoesFinais = '';
    if ($capeante && $modeloCompleto) {
        $capValoresDiarDao = new CapValoresDiarDAO($conn);
        $capValoresApDao = new CapValoresAPDAO($conn);
        $capValoresUtiDao = new CapValoresUTIDAO($conn);
        $capValoresCcDao = new CapValoresCCDAO($conn);
        $capValoresOutDao = new CapValoresOutDAO($conn);

        $outRow = $capValoresOutDao->findByCapeante($idCapeante);
        $observacoesFinais = trim((string)($outRow['comentarios_obs'] ?? ''));

        $gruposDetalhados = [
            'Diárias' => cap_print_group_from_row($capValoresDiarDao->findByCapeante($idCapeante), $mapDiarias),
            'Despesas no Apto / Enfermaria' => cap_print_group_from_row($capValoresApDao->findByCapeante($idCapeante), $mapApto),
            'Despesas na UTI' => cap_print_group_from_row($capValoresUtiDao->findByCapeante($idCapeante), $mapUti),
            'Despesas no Centro Cirúrgico' => cap_print_group_from_row($capValoresCcDao->findByCapeante($idCapeante), $mapCc),
            'Outros' => cap_print_group_from_row($outRow, $mapOutros),
        ];

        foreach ([
            'Diárias' => ['tb_cap_valores_diar', $mapDiarias],
            'Despesas no Apto / Enfermaria' => ['tb_cap_valores_ap', $mapApto],
            'Despesas na UTI' => ['tb_cap_valores_uti', $mapUti],
            'Despesas no Centro Cirúrgico' => ['tb_cap_valores_cc', $mapCc],
            'Outros' => ['tb_cap_valores_out', $mapOutros],
        ] as $title => [$table, $map]) {
            if (empty($gruposDetalhados[$title])) {
                $gruposDetalhados[$title] = cap_print_group_from_db($conn, $idCapeante, $table, $map);
            }
        }
    }

    return [
        'id' => $idCapeante,
        'capeante' => $capeante,
        'rowsConta' => $rowsConta,
        'rowsGlosas' => $rowsGlosas,
        'rowsSeguimento' => $rowsSeguimento,
        'rowsGlosasSeguimento' => $rowsGlosasSeguimento,
        'assinaturas' => $assinaturas,
        'gruposDetalhados' => $gruposDetalhados,
        'totaisDetalhados' => [
            ['Cobrado', $fmtMoney($sumDetail($gruposDetalhados, 'cobrado'))],
            ['Glosado', $fmtMoney($sumDetail($gruposDetalhados, 'glosado'))],
            ['Liberado', $fmtMoney($sumDetail($gruposDetalhados, 'liberado'))],
        ],
        'observacoesFinais' => $observacoesFinais,
    ];
};

$capeantePrints = [];
foreach ($capeanteIds as $id) {
    $capeantePrints[] = $buildPrintContext($loadCapeante($capeanteDao, $id), $id);
}
if (!$capeantePrints) {
    $capeantePrints[] = $buildPrintContext(null, 0);
}

$firstCapeante = $capeantePrints[0]['capeante'] ?? null;
$isBulkPrint = count($capeantePrints) > 1;
$selectedIds = implode(',', array_column($capeantePrints, 'id'));
$printBaseUrl = $isBulkPrint
    ? rtrim($BASE_URL, '/') . '/show_capeantePrt.php?ids=' . rawurlencode($selectedIds)
    : rtrim($BASE_URL, '/') . '/contas/prontuario/' . rawurlencode((string)($capeantePrints[0]['id'] ?? $id_capeante));
$printModeloJoin = strpos($printBaseUrl, '?') === false ? '?' : '&';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <link rel="icon" type="image/png" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <link rel="shortcut icon" type="image/png" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <link rel="apple-touch-icon" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capeante <?= $isBulkPrint ? 'selecionados' : ($firstCapeante ? $h($firstCapeante['id_capeante'] ?? '') : '') ?></title>
    <style>
        @font-face {
            font-family: 'Allura';
            font-style: normal;
            font-weight: 400;
            src: url('/FullCare/fonts/Allura-Regular.ttf') format('truetype');
        }

        :root {
            --print-ink: #252b33;
            --print-muted: #657080;
            --print-line: #b9c2cc;
            --print-soft: #f1f4f7;
            --print-band: #e5e9ee;
            --print-accent: #5d256f;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background: #e8edf3;
            color: var(--print-ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.25;
        }

        .print-toolbar {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 12px;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 0 14px;
            border: 1px solid #2f6f9f;
            border-radius: 8px;
            background: #2f6f9f;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .print-btn--ghost {
            border-color: #c6d0dc;
            background: #fff;
            color: #344054;
        }

        .print-btn--active {
            border-color: var(--print-accent);
            background: var(--print-accent);
            color: #fff;
        }

        #main-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 18px;
            padding: 10mm 11mm;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .18);
        }

        .print-page {
            display: flex;
            flex-direction: column;
            min-height: 277mm;
        }

        .print-page + .print-page {
            margin-top: 10mm;
            page-break-before: always;
            break-before: page;
        }

        .print-header {
            display: grid;
            grid-template-columns: 45mm 1fr 37mm;
            align-items: center;
            gap: 8mm;
            padding-bottom: 6mm;
            border-bottom: 1px solid var(--print-line);
        }

        .print-logo {
            width: 39mm;
            max-height: 18mm;
            object-fit: contain;
        }

        .print-title {
            text-align: center;
        }

        .print-title small {
            display: block;
            color: var(--print-muted);
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .18em;
            line-height: 1;
            text-transform: uppercase;
        }

        .print-title h1 {
            margin: 3px 0 0;
            color: var(--print-ink);
            font-size: 19px;
            font-weight: 800;
            line-height: 1.1;
        }

        .print-idbox {
            justify-self: end;
            min-width: 34mm;
            padding: 5mm 4mm;
            border: 1px solid var(--print-line);
            border-radius: 3mm;
            background: var(--print-soft);
            text-align: center;
        }

        .print-idbox span {
            display: block;
            color: var(--print-muted);
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .print-idbox strong {
            display: block;
            margin-top: 2px;
            color: var(--print-accent);
            font-size: 18px;
            line-height: 1;
        }

        .info-strip,
        .info-grid,
        .summary-grid,
        .signature-grid {
            display: grid;
            gap: 2.5mm;
        }

        .info-strip {
            grid-template-columns: 1.45fr 1.3fr .75fr;
            margin-top: 6mm;
        }

        .info-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin-top: 2.5mm;
        }

        .info-box,
        .value-box {
            min-height: 13mm;
            padding: 2.5mm 3mm;
            border: 1px solid #d6dde5;
            border-radius: 2mm;
            background: #fff;
        }

        .info-box label,
        .value-box label {
            display: block;
            margin-bottom: 1.2mm;
            color: var(--print-muted);
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .info-box strong,
        .value-box strong {
            display: block;
            color: var(--print-ink);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.15;
        }

        .section {
            margin-top: 5mm;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0 0 2.5mm;
            padding: 2mm 3mm;
            border: 1px solid var(--print-line);
            border-radius: 1.5mm;
            background: var(--print-band);
            color: #303740;
            font-size: 12px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }

        .summary-grid--two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .summary-grid--three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .summary-grid--five {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .summary-grid--detail {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 4mm;
        }

        .value-box {
            min-height: 16mm;
            background: #fbfcfe;
            text-align: center;
        }

        .value-box strong {
            color: #28384a;
            font-size: 14px;
        }

        .signature-area {
            margin-top: auto;
            padding-top: 10mm;
            page-break-inside: avoid;
        }

        .signature-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .signature-box {
            min-height: 23mm;
            padding: 0 2mm;
            text-align: center;
        }

        .signature-name {
            min-height: 12mm;
            border-bottom: 1px solid #6b7280;
            font-family: 'Allura', cursive;
            font-size: 21px;
            font-style: italic;
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .signature-role {
            margin-top: 2mm;
            color: var(--print-muted);
            font-size: 9px;
            font-weight: 700;
        }

        .print-date {
            margin-top: 10mm;
            padding-top: 4mm;
            border-top: 1px solid var(--print-line);
            color: #475467;
            font-size: 11px;
            text-align: center;
        }

        .not-found {
            margin-top: 20mm;
            padding: 8mm;
            border: 1px solid #f3c1c1;
            border-radius: 3mm;
            background: #fff7f7;
            color: #991b1b;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
        }

        .detail-section {
            margin-top: 4mm;
            page-break-inside: avoid;
        }

        .detail-section-title {
            margin: 0;
            padding: 1.8mm 2.5mm;
            border: 1px solid var(--print-line);
            border-bottom: 0;
            border-radius: 1.5mm 1.5mm 0 0;
            background: var(--print-band);
            color: #303740;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9px;
            line-height: 1.15;
        }

        .detail-table th,
        .detail-table td {
            padding: 1.6mm 1.8mm;
            border: 1px solid var(--print-line);
            vertical-align: top;
        }

        .detail-table th {
            background: #f6f8fb;
            color: #475467;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .detail-table td:nth-child(2),
        .detail-table th:nth-child(2) {
            text-align: center;
        }

        .detail-table td:nth-child(3),
        .detail-table td:nth-child(4),
        .detail-table td:nth-child(5),
        .detail-table th:nth-child(3),
        .detail-table th:nth-child(4),
        .detail-table th:nth-child(5) {
            text-align: right;
        }

        .detail-table .empty-row {
            color: var(--print-muted);
            text-align: center;
        }

        .print-note {
            margin-top: 4mm;
            padding: 3mm;
            border: 1px solid #d6dde5;
            border-radius: 2mm;
            background: #fbfcfe;
            color: #344054;
            font-size: 10px;
            line-height: 1.35;
            page-break-inside: avoid;
        }

        .print-note strong {
            display: block;
            margin-bottom: 1mm;
            color: var(--print-muted);
            font-size: 8px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        @media print {
            html,
            body {
                width: 210mm;
                min-height: 297mm;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-toolbar {
                display: none !important;
            }

            #main-container {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .print-page {
                min-height: calc(297mm - 16mm);
            }

            .print-page--complete {
                min-height: auto;
            }

            .print-page + .print-page {
                margin-top: 0;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
        integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<body>
    <div class="print-toolbar">
        <a class="print-btn <?= !$modeloCompleto ? 'print-btn--active' : 'print-btn--ghost' ?>"
            href="<?= $h($printBaseUrl) ?>">Modelo resumido</a>
        <a class="print-btn <?= $modeloCompleto ? 'print-btn--active' : 'print-btn--ghost' ?>"
            href="<?= $h($printBaseUrl . $printModeloJoin . 'modelo=completo') ?>">Modelo completo</a>
        <button type="button" class="print-btn" onclick="window.print()">Imprimir</button>
        <button type="button" class="print-btn print-btn--ghost" onclick="generatePdf()">Gerar PDF</button>
    </div>

    <main id="main-container">
        <?php foreach ($capeantePrints as $printIndex => $print): ?>
            <?php
            $capeante = $print['capeante'];
            $rowsConta = $print['rowsConta'];
            $rowsGlosas = $print['rowsGlosas'];
            $rowsSeguimento = $print['rowsSeguimento'];
            $rowsGlosasSeguimento = $print['rowsGlosasSeguimento'];
            $assinaturas = $print['assinaturas'];
            $gruposDetalhados = $print['gruposDetalhados'];
            $totaisDetalhados = $print['totaisDetalhados'];
            $observacoesFinais = $print['observacoesFinais'];
            ?>
        <section class="print-page <?= $modeloCompleto ? 'print-page--complete' : '' ?>">
            <header class="print-header">
                <img class="print-logo" src="/FullCare/img/logo_novo.png" alt="FullCare">
                <div class="print-title">
                    <small>Conta hospitalar</small>
                    <h1>Capeante de Auditoria</h1>
                </div>
                <div class="print-idbox">
                    <span>Capeante nº</span>
                    <strong <?= $printIndex === 0 ? 'id="id-capeante"' : '' ?>><?= $capeante ? $h($capeante['id_capeante'] ?? '-') : $h($print['id'] ?: '-') ?></strong>
                </div>
            </header>

            <?php if (!$capeante): ?>
                <div class="not-found">Capeante não encontrado.</div>
            <?php else: ?>
                <div class="info-strip">
                    <div class="info-box">
                        <label>Hospital</label>
                        <strong><?= $h($capeante['nome_hosp'] ?? '-') ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Paciente</label>
                        <strong><?= $h($capeante['nome_pac'] ?? '-') ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Senha</label>
                        <strong><?= $h($capeante['senha_int'] ?? '-') ?></strong>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <label>Data Internação</label>
                        <strong><?= $h($fmtDate($capeante['data_intern_int'] ?? '')) ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Tipo Internação</label>
                        <strong><?= $h($capeante['tipo_admissao_int'] ?? '-') ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Modo Admissão</label>
                        <strong><?= $h($capeante['modo_internacao_int'] ?? '-') ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Data Inicial</label>
                        <strong><?= $h($fmtDate($capeante['data_inicial_capeante'] ?? '')) ?></strong>
                    </div>
                    <div class="info-box">
                        <label>Data Final</label>
                        <strong><?= $h($fmtDate($capeante['data_final_capeante'] ?? '')) ?></strong>
                    </div>
                </div>

                <?php if (!$modeloCompleto): ?>
                    <section class="section">
                        <h2 class="section-title">Consolidado da Conta</h2>
                        <div class="summary-grid summary-grid--two">
                            <?php foreach ($rowsConta as [$label, $value]): ?>
                                <div class="value-box">
                                    <label><?= $h($label) ?></label>
                                    <strong><?= $h($value) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="section">
                        <h2 class="section-title">Glosas Consolidadas</h2>
                        <div class="summary-grid summary-grid--three">
                            <?php foreach ($rowsGlosas as [$label, $value]): ?>
                                <div class="value-box">
                                    <label><?= $h($label) ?></label>
                                    <strong><?= $h($value) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="section">
                        <h2 class="section-title">Valores por Seguimento</h2>
                        <div class="summary-grid summary-grid--five">
                            <?php foreach ($rowsSeguimento as [$label, $value]): ?>
                                <div class="value-box">
                                    <label><?= $h($label) ?></label>
                                    <strong><?= $h($value) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="section">
                        <h2 class="section-title">Glosas por Seguimento</h2>
                        <div class="summary-grid summary-grid--five">
                            <?php foreach ($rowsGlosasSeguimento as [$label, $value]): ?>
                                <div class="value-box">
                                    <label><?= $h($label) ?></label>
                                    <strong><?= $h($value) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="section">
                        <h2 class="section-title">Consolidado Detalhado</h2>
                        <div class="summary-grid summary-grid--two">
                            <?php foreach ($rowsConta as [$label, $value]): ?>
                                <div class="value-box">
                                    <label><?= $h($label) ?></label>
                                    <strong><?= $h($value) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="summary-grid summary-grid--detail">
                            <?php foreach ($totaisDetalhados as [$label, $value]): ?>
                                <div class="value-box">
                                    <label><?= $h($label) ?></label>
                                    <strong><?= $h($value) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <?php foreach ($gruposDetalhados as $tituloGrupo => $linhasGrupo): ?>
                        <section class="detail-section">
                            <h2 class="detail-section-title"><?= $h($tituloGrupo) ?></h2>
                            <table class="detail-table">
                                <colgroup>
                                    <col style="width: 25%">
                                    <col style="width: 7%">
                                    <col style="width: 15%">
                                    <col style="width: 15%">
                                    <col style="width: 15%">
                                    <col style="width: 23%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Qtd.</th>
                                        <th>Cobrado</th>
                                        <th>Glosado</th>
                                        <th>Liberado</th>
                                        <th>Observação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($linhasGrupo)): ?>
                                        <tr>
                                            <td class="empty-row" colspan="6">Sem lançamentos</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($linhasGrupo as $linha): ?>
                                            <tr>
                                                <td><?= $h($linha['desc'] ?? '') ?></td>
                                                <td><?= $h($linha['qtd'] ?? 0) ?></td>
                                                <td><?= $h($fmtMoney($linha['cobrado'] ?? 0)) ?></td>
                                                <td><?= $h($fmtMoney($linha['glosado'] ?? 0)) ?></td>
                                                <td><?= $h($fmtMoney($linha['liberado'] ?? 0)) ?></td>
                                                <td><?= $h($linha['obs'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </section>
                    <?php endforeach; ?>

                    <?php if ($observacoesFinais !== ''): ?>
                        <div class="print-note">
                            <strong>Observações finais</strong>
                            <?= nl2br($h($observacoesFinais)) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <footer class="signature-area">
                    <div class="signature-grid">
                        <?php foreach ($assinaturas as [$role, $name]): ?>
                            <div class="signature-box">
                                <div class="signature-name"><?= $h($name) ?></div>
                                <div class="signature-role"><?= $h($role) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="print-date"><?= $h($printDate) ?></div>
                </footer>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    </main>

    <script>
        function generatePdf() {
            const element = document.getElementById('main-container');
            const idCapeante = <?= json_encode($isBulkPrint ? 'Selecionados' : (string)($capeantePrints[0]['id'] ?? 'capeante')) ?>;
            const hoje = new Date();
            const dia = String(hoje.getDate()).padStart(2, '0');
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const ano = hoje.getFullYear();
            const modelo = <?= json_encode($modeloCompleto ? 'Completo' : 'Resumido') ?>;
            const nomeArquivo = `CapNo${idCapeante}_${modelo}_Data_${dia}_${mes}_${ano}.pdf`;

            const options = {
                margin: [0.8, 0.8, 0.8, 0.8],
                filename: nomeArquivo,
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 3,
                    useCORS: true,
                    scrollY: 0
                },
                jsPDF: {
                    unit: 'cm',
                    format: 'a4',
                    orientation: 'portrait'
                },
                pagebreak: {
                    mode: ['avoid-all', 'css', 'legacy']
                }
            };

            html2pdf().set(options).from(element).save();
        }
    </script>
</body>

</html>
