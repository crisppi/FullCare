<!DOCTYPE html>
<html lang="pt-br">
<script src="js/timeout.js"></script>

<head>
    <link rel="icon" type="image/png" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <link rel="shortcut icon" type="image/png" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">
    <link rel="apple-touch-icon" href="/FullCare/assets/fullcare-icon.png?v=fullcare2">

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>
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


    // Pegar o id da internacao
    // Pegar o id da internacao
    $id_capeante = filter_input(INPUT_GET, "id_capeante", FILTER_SANITIZE_NUMBER_INT);
    $fk_int_capeante = filter_input(INPUT_GET, "fk_int_capeante", FILTER_SANITIZE_NUMBER_INT);
    $where = $fk_int_capeante;
    $condicoes = [
        strlen($id_capeante) ? 'ca.id_capeante LIKE "%' . $id_capeante . '%"' : null,
    ];

    $condicoes = array_filter($condicoes);
    // REMOVE POSICOES VAZIAS DO FILTRO
    $where = implode(' AND ', $condicoes);
    $internacao;
    $order = null;
    $obLimite = null;
    $capeanteDao = new capeanteDAO($conn, $BASE_URL);

    //Instanciar o metodo internacao   
    $internacao = $capeanteDao->selectAllcapeante($where, $order, $obLimite);

    $alertaEventoAdverso = null;
    $idInternacaoEvento = isset($internacao[0]['id_internacao']) ? (int)$internacao[0]['id_internacao'] : 0;
    if ($idInternacaoEvento > 0) {
        $stmtEvento = $conn->prepare("
            SELECT
                ge.tipo_evento_adverso_gest,
                ge.rel_evento_adverso_ges,
                ge.evento_data_ges
            FROM tb_gestao ge
            WHERE ge.fk_internacao_ges = :id_internacao
              AND LOWER(COALESCE(ge.evento_adverso_ges, '')) = 's'
            ORDER BY ge.id_gestao DESC
            LIMIT 1
        ");
        $stmtEvento->bindValue(':id_internacao', $idInternacaoEvento, PDO::PARAM_INT);
        $stmtEvento->execute();
        $alertaEventoAdverso = $stmtEvento->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    $capeante = $internacao[0] ?? null;
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
    $backUrl = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($backUrl === '' || stripos($backUrl, (string)($_SERVER['HTTP_HOST'] ?? '')) === false) {
        $backUrl = rtrim($BASE_URL, '/') . '/contas/senhas-finalizadas';
    }
    ?>
    <link rel="stylesheet" href="<?= $h(rtrim($BASE_URL, '/') . '/css/listagem_padrao.css?v=' . @filemtime(__DIR__ . '/../../css/listagem_padrao.css')) ?>">
    <style>
        .capeante-show-page {
            padding: 12px 12px 18px;
            color: #344054;
            font-family: var(--app-font-family, "Inter", Arial, Helvetica, sans-serif);
        }

        .capeante-show-card {
            border: 1px solid #dbe4ee;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 10px 24px -22px rgba(15, 23, 42, .28);
            overflow: hidden;
        }

        .capeante-show-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 16px;
            border-bottom: 1px solid #e8edf3;
            background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        }

        .capeante-show-kicker {
            margin: 0 0 4px;
            color: #7b5a9a;
            font-size: .58rem;
            font-weight: 800;
            letter-spacing: .12em;
            line-height: 1;
            text-transform: uppercase;
        }

        .capeante-show-title {
            margin: 0;
            color: #24384f;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .capeante-show-pills {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            flex-wrap: wrap;
        }

        .capeante-show-pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 9px;
            border: 1px solid #d7e8f3;
            border-radius: 999px;
            background: #f4faff;
            color: #2f6f9f;
            font-size: .68rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .capeante-show-body {
            padding: 12px 16px 14px;
        }

        .capeante-alert {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 12px;
            padding: 10px 12px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fff7f7;
            color: #7f1d1d;
        }

        .capeante-alert__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: .78rem;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .capeante-alert strong {
            display: block;
            margin-bottom: 3px;
            color: #991b1b;
            font-size: .78rem;
            line-height: 1.1;
        }

        .capeante-alert span {
            color: #7f1d1d;
            font-size: .7rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .capeante-info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .capeante-info-item {
            min-height: 54px;
            padding: 8px 10px;
            border: 1px solid #e8edf3;
            border-radius: 9px;
            background: #fff;
        }

        .capeante-info-item--wide {
            grid-column: span 2;
        }

        .capeante-info-label {
            display: block;
            margin-bottom: 4px;
            color: #667085;
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .02em;
            line-height: 1;
            text-transform: uppercase;
        }

        .capeante-info-value {
            display: block;
            color: #344054;
            font-size: .76rem;
            font-weight: 700;
            line-height: 1.18;
            overflow-wrap: anywhere;
        }

        .capeante-values {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 190px));
            gap: 8px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e8edf3;
        }

        .capeante-value-card {
            padding: 9px 10px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            background: #f8fbff;
        }

        .capeante-value-card strong {
            display: block;
            margin-top: 3px;
            color: #1f5f8f;
            font-size: .88rem;
            line-height: 1.1;
        }

        .capeante-show-actions {
            display: flex;
            justify-content: flex-start;
            padding: 10px 16px 14px;
            border-top: 1px solid #e8edf3;
            background: #fff;
        }

        .capeante-back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 32px;
            padding: 0 11px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            color: #344054;
            font-size: .72rem;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
        }

        .capeante-back-btn:hover,
        .capeante-back-btn:focus {
            border-color: #2f6f9f;
            background: #f4faff;
            color: #1f5f8f;
            text-decoration: none;
        }

        @media (max-width: 991.98px) {
            .capeante-show-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .capeante-show-pills {
                justify-content: flex-start;
            }

            .capeante-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .capeante-info-grid,
            .capeante-values {
                grid-template-columns: 1fr;
            }

            .capeante-info-item--wide {
                grid-column: span 1;
            }
        }
    </style>

    <div id="main-container" class="container-fluid capeante-show-page">
        <div class="capeante-show-shell">
            <?php if (!$capeante): ?>
                <div class="capeante-show-card">
                <div class="capeante-show-head">
                    <div>
                        <p class="capeante-show-kicker">Capeantes</p>
                        <h1 class="capeante-show-title">Conta não encontrada</h1>
                    </div>
                </div>
                <div class="capeante-show-actions">
                    <a class="capeante-back-btn" href="<?= $h($backUrl) ?>">Voltar</a>
                </div>
                </div>
            <?php else: ?>
                <div class="capeante-show-card">
                <div class="capeante-show-head">
                    <div>
                        <p class="capeante-show-kicker">Capeante</p>
                        <h1 class="capeante-show-title">Dados da internação do paciente: <?= $h($capeante['nome_pac'] ?? '') ?></h1>
                    </div>
                    <div class="capeante-show-pills">
                        <span class="capeante-show-pill">Internação: <?= $h($capeante['id_internacao'] ?? '-') ?></span>
                        <span class="capeante-show-pill">Capeante: <?= $h($capeante['id_capeante'] ?? $id_capeante) ?></span>
                        <span class="capeante-show-pill">Visita: <?= $h($fmtDate($capeante['data_visita_int'] ?? '')) ?></span>
                    </div>
                </div>

                <div class="capeante-show-body">
                    <?php if ($alertaEventoAdverso): ?>
                        <div class="capeante-alert">
                            <span class="capeante-alert__icon">!</span>
                            <div>
                                <strong>Alerta de evento adverso nesta conta</strong>
                                <span>
                                    Tipo: <?= $h($alertaEventoAdverso['tipo_evento_adverso_gest'] ?? 'Não informado') ?>
                                    <?php if (!empty($alertaEventoAdverso['evento_data_ges'])): ?>
                                        | Data: <?= $h($fmtDate($alertaEventoAdverso['evento_data_ges'])) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="capeante-info-grid">
                        <div class="capeante-info-item capeante-info-item--wide">
                            <span class="capeante-info-label">Hospital</span>
                            <span class="capeante-info-value"><?= $h($capeante['nome_hosp'] ?? '-') ?></span>
                        </div>
                        <div class="capeante-info-item">
                            <span class="capeante-info-label">Data Internação</span>
                            <span class="capeante-info-value"><?= $h($fmtDate($capeante['data_intern_int'] ?? '')) ?></span>
                        </div>
                        <div class="capeante-info-item">
                            <span class="capeante-info-label">Tipo Internação</span>
                            <span class="capeante-info-value"><?= $h($capeante['tipo_admissao_int'] ?? '-') ?></span>
                        </div>
                        <div class="capeante-info-item">
                            <span class="capeante-info-label">Modo Admissão</span>
                            <span class="capeante-info-value"><?= $h($capeante['modo_internacao_int'] ?? '-') ?></span>
                        </div>
                        <div class="capeante-info-item">
                            <span class="capeante-info-label">Especialidade</span>
                            <span class="capeante-info-value"><?= $h($capeante['especialidade_int'] ?? '-') ?></span>
                        </div>
                        <div class="capeante-info-item">
                            <span class="capeante-info-label">Grupo Patologia</span>
                            <span class="capeante-info-value"><?= $h($capeante['grupo_patologia_int'] ?? '-') ?></span>
                        </div>
                        <div class="capeante-info-item">
                            <span class="capeante-info-label">Médico</span>
                            <span class="capeante-info-value"><?= $h($capeante['titular_int'] ?? '-') ?></span>
                        </div>
                    </div>

                    <div class="capeante-values">
                        <div class="capeante-value-card">
                            <span class="capeante-info-label">Valor Apresentado</span>
                            <strong><?= $h($fmtMoney($capeante['valor_apresentado_capeante'] ?? 0)) ?></strong>
                        </div>
                        <div class="capeante-value-card">
                            <span class="capeante-info-label">Valor Final</span>
                            <strong><?= $h($fmtMoney($capeante['valor_final_capeante'] ?? 0)) ?></strong>
                        </div>
                    </div>
                </div>

                <div class="capeante-show-actions">
                    <a class="capeante-back-btn" href="<?= $h($backUrl) ?>">Voltar</a>
                </div>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js">
    </script>
    <?php
    require_once("templates/footer.php");
    ?>
</body>

</html>
