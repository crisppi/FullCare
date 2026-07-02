<?php
include_once("check_logado.php");
include_once("globals.php");
require_once("app/services/OperationalIntelligenceService.php");

$service = new OperationalIntelligenceService($conn);
$drivers = $service->explainabilityDrivers();

function insight_factor_label(string $factor): string
{
    $factor = trim($factor);
    if ($factor === '') {
        return '-';
    }

    if (function_exists('mb_strtolower') && function_exists('mb_convert_case')) {
        return mb_convert_case(mb_strtolower($factor, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    return ucfirst(strtolower($factor));
}

include_once("templates/header.php");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link rel="icon" type="image/png" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <link rel="shortcut icon" type="image/png" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <link rel="apple-touch-icon" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">

    <meta charset="UTF-8">
    <title>Insights explicáveis</title>
    <style>
        .insight-card {
            border-radius: 12px;
            border: 1px solid #e7e7e7;
            background: #fff;
            margin: 0.35rem 0 0.8rem;
            padding: 1rem 1.1rem;
            box-shadow: 0 10px 25px rgba(76, 142, 187,0.08);
        }
        .container-fluid {
            margin-top: 12px !important;
            padding: 0 0 12px !important;
        }
        .container-fluid h2 {
            font-size: 1.06rem;
        }
        .container-fluid .alert,
        .container-fluid .table,
        .container-fluid .text-muted,
        .container-fluid small,
        .factor-chip {
            font-size: .78rem;
        }
        .table thead th,
        .table td {
            padding-top: .55rem;
            padding-bottom: .55rem;
        }
        .alert {
            margin-bottom: 0;
        }
        .insight-table-wrap {
            overflow-x: visible;
        }
        .insight-table {
            table-layout: fixed;
            width: 100%;
        }
        .insight-table th,
        .insight-table td {
            white-space: normal !important;
        }
        .insight-table .patient-col { width: 25%; }
        .insight-table .hospital-col { width: 18%; }
        .insight-table .operator-col { width: 10%; }
        .insight-table .days-col { width: 8%; }
        .insight-table .status-col { width: 10%; }
        .insight-table .factors-col { width: 29%; }
        .patient-name {
            color: #3e4653;
            font-weight: 500;
        }
        .days-value {
            color: #3e4653;
            font-weight: 500;
        }
        .insight-status {
            display: inline-flex;
            align-items: center;
            min-height: 18px;
            border-radius: 999px;
            padding: 2px 7px;
            font-size: .58rem;
            line-height: 1;
            font-weight: 600;
            white-space: nowrap;
        }
        .factor-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .factor-list li {
            color: #536171;
            font-size: .66rem;
            line-height: 1.18;
            font-weight: 400;
        }
        .factor-list li + li::before {
            content: "·";
            margin-right: 6px;
            color: #9aa7b6;
        }
        .insight-card .table tbody tr:nth-child(odd),
        .insight-card .table tbody tr:nth-child(odd) > * {
            background: #fff !important;
            --bs-table-bg: #fff !important;
            --bs-table-accent-bg: #fff !important;
        }
        .insight-card .table tbody tr:nth-child(even),
        .insight-card .table tbody tr:nth-child(even) > * {
            background: #f4faff !important;
            --bs-table-bg: #f4faff !important;
            --bs-table-accent-bg: #f4faff !important;
        }
        .insight-card .table-hover tbody tr:hover,
        .insight-card .table-hover tbody tr:hover > * {
            background: #e8f4fb !important;
            --bs-table-bg: #e8f4fb !important;
            --bs-table-accent-bg: #e8f4fb !important;
        }
    </style>
    <link href="<?= $BASE_URL ?>css/operational_reports.css?v=<?= @filemtime(__DIR__ . '/css/operational_reports.css') ?>" rel="stylesheet">
</head>
<body>
    <div class="container-fluid operational-report-page" style="margin-top:24px; padding:0 0 16px;">
        <div class="row mb-2">
            <div class="col-12">
                <h2 class="mb-0 fw-semibold" style="color:#2f6f9f;">Insights explicáveis</h2>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-12">
                <div class="alert alert-info">
                    <strong>Explicabilidade dos modelos</strong> — Como auditores da operadora, precisamos justificar o risco projetado. 
                    A tabela abaixo mostra fatores-chave (heurística interna) que elevaram o alerta para cada paciente, 
                    incluindo permanência longa, ausência de visita, prorrogações e eventos adversos.
                </div>
            </div>
        </div>

        <div class="insight-card">
            <?php if (!empty($drivers['available'])): ?>
            <div class="table-responsive insight-table-wrap">
                <table class="table table-hover align-middle insight-table">
                    <colgroup>
                        <col class="patient-col">
                        <col class="hospital-col">
                        <col class="operator-col">
                        <col class="days-col">
                        <col class="status-col">
                        <col class="factors-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Hospital</th>
                            <th>Operadora</th>
                            <th class="text-center">Dias internado</th>
                            <th class="text-center">Status</th>
                            <th>Fatores que elevaram o risco</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($drivers['entries'] as $entry): ?>
                        <tr>
                            <td>
                                <div class="patient-name"><?= htmlspecialchars($entry['nome_pac'] ?? 'Paciente') ?></div>
                                <small class="text-muted">Int. #<?= (int)$entry['id_internacao'] ?> · <?= date('d/m/Y', strtotime($entry['data_intern_int'])) ?></small>
                            </td>
                            <td><?= htmlspecialchars($entry['nome_hosp'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($entry['seguradora_seg'] ?? '-') ?></td>
                            <td class="text-center days-value"><?= (int)$entry['dias_atual'] ?>d</td>
                            <td class="text-center">
                                <?php
                                $statusClass = [
                                    'atrasado' => 'insight-status bg-danger text-white',
                                    'atencao'  => 'insight-status bg-warning text-dark',
                                    'no_prazo' => 'insight-status bg-success text-white'
                                ];
                                $label = [
                                    'atrasado' => 'Acima do previsto',
                                    'atencao'  => 'Fase crítica',
                                    'no_prazo' => 'Dentro do previsto'
                                ];
                                $state = $entry['status'] ?? 'no_prazo';
                                ?>
                                <span class="<?= $statusClass[$state] ?? 'badge bg-secondary' ?>">
                                    <?= $label[$state] ?? 'Monitorar' ?>
                                </span>
                            </td>
                            <td class="factors-cell">
                                <ul class="factor-list">
                                <?php foreach (($entry['factors'] ?? []) as $factor): ?>
                                    <li><?= htmlspecialchars(insight_factor_label((string)$factor)) ?></li>
                                <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <small class="text-muted"><?= htmlspecialchars($drivers['message']) ?></small>
            <?php else: ?>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($drivers['message'] ?? 'Sem dados suficientes para gerar explicabilidade.') ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
