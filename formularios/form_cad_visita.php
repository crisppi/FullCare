<?php

$BASE_URL = isset($BASE_URL) ? (string)$BASE_URL : '';
$dbConn = $GLOBALS['conn'] ?? null;
$ultimaVis = $ultimaVis ?? [];
$internacaoList = $internacaoList ?? [];
$visitasAntigas = $visitasAntigas ?? [];
$editVisitaIdParam = $editVisitaIdParam ?? null;
$editVisitaIdReal = $editVisitaIdReal ?? 0;
$editAdditionalSelects = $editAdditionalSelects ?? [];
$tussPorVisita = $tussPorVisita ?? [];
$tussPorInternacao = $tussPorInternacao ?? [];
$negPorVisita = $negPorVisita ?? [];
$negPorInternacao = $negPorInternacao ?? [];
$gestaoPorVisita = $gestaoPorVisita ?? [];
$gestaoPorInternacao = $gestaoPorInternacao ?? [];
$utiPorVisita = $utiPorVisita ?? [];
$utiPorInternacao = $utiPorInternacao ?? [];
$prorrogPorVisita = $prorrogPorVisita ?? [];
$prorrogPorInternacao = $prorrogPorInternacao ?? [];
$prorrogEditRows = $prorrogEditRows ?? [];
$visitaInterMap = $visitaInterMap ?? [];
$jsonAcomodacoes = $jsonAcomodacoes ?? '[]';
$id_internacao = $id_internacao ?? '';

function fmtYmd(?string $s): ?string
{
    return !empty($s) ? date('Y-m-d', strtotime($s)) : null;
}
function fmtDmy(?string $s): string
{
    return !empty($s) ? date('d/m/Y', strtotime($s)) : '';
}

$idSessao = (int)($_SESSION['id_usuario'] ?? 0);
$cargoSessao = $_SESSION['cargo'] ?? ($_SESSION['cargo_user'] ?? '');
$emailSessao = mb_strtolower(trim((string)($_SESSION['email_user'] ?? '')), 'UTF-8');

include_once("dao/usuarioDao.php");
$usuarioDao = new UserDAO($dbConn, $BASE_URL);

$normCargoSessao = mb_strtolower(str_replace([' ', '-'], '_', (string)$cargoSessao), 'UTF-8');
$isCrisppiSessao = $emailSessao === 'crisppi@fullcare.com.br';
$isMedSessao = strpos($normCargoSessao, 'med') === 0 || $isCrisppiSessao;
$isEnfSessao = strpos($normCargoSessao, 'enf') === 0;
$mostrarCadastroCentral = !($isMedSessao || $isEnfSessao);

$medicosAud = [];
$enfsAud = [];
try {
    $todos = $usuarioDao->findMedicosEnfermeiros();
    if (!is_array($todos)) $todos = [];
    foreach ($todos as $u) {
        $id = (int)($u['id_usuario'] ?? 0);
        if (!$id) continue;
        $cargo = (string)($u['cargo_user'] ?? '');
        $row = [
            'id_usuario'   => $id,
            'usuario_user' => (string)($u['usuario_user'] ?? ('#' . $id)),
            'cargo_user'   => $cargo,
        ];
        $cargoUpper = mb_strtoupper($cargo, 'UTF-8');
        if (strpos($cargoUpper, 'MED') === 0) {
            $medicosAud[] = $row;
        } elseif (strpos($cargoUpper, 'ENF') === 0) {
            $enfsAud[] = $row;
        }
    }
} catch (Throwable $e) {
    $medicosAud = $enfsAud = [];
}

$defaultVisitaMed = $isMedSessao ? 's' : 'n';
$defaultVisitaEnf = $isEnfSessao ? 's' : 'n';
$defaultAuditorMed = $isMedSessao ? $idSessao : '';
$defaultAuditorEnf = $isEnfSessao ? $idSessao : '';

$hoje = date('Y-m-d');

// protege contra null
$visitaAnt = !empty($ultimaVis['data_visita_vis'])
    ? date("Y-m-d", strtotime($ultimaVis['data_visita_vis']))
    : null;

$intern = !empty($ultimaVis['data_intern_int'])
    ? date("Y-m-d", strtotime($ultimaVis['data_intern_int']))
    : null;

$atual = new DateTime($hoje);

// só cria DateTime se tiver valor
$visAnt     = $visitaAnt ? new DateTime($visitaAnt) : null;
$dataIntern = $intern    ? new DateTime($intern)    : null;

// diffs seguros (fallback = 0 dias)
if ($visAnt instanceof DateTime) {
    $intervaloUltimaVis = $visAnt->diff($atual);
} else {
    $intervaloUltimaVis = new DateInterval('P0D'); // 0 dias
}

if ($dataIntern instanceof DateTime) {
    $diasIntern = $dataIntern->diff($atual);
} else {
    $diasIntern = new DateInterval('P0D'); // 0 dias
}

// (Opcional) Se você preferir inteiros de dias, também pode expor:
$intervaloUltimaVisDias = $visAnt ? $visAnt->diff($atual)->days : 0;
$diasInternDias         = $dataIntern ? $dataIntern->diff($atual)->days : 0;


$visitasDAO = new visitaDAO($dbConn, $BASE_URL);
$internacaoDAO = new internacaoDAO($dbConn, $BASE_URL);
$query2DAO = new visitaDAO($dbConn, $BASE_URL);
$id_internacao = filter_input(INPUT_GET, "id_internacao", FILTER_SANITIZE_NUMBER_INT);
$visitas = $visitasDAO->joinVisitaInternacao($id_internacao);

$visitaMax = $internacaoDAO->selectInternVisLast(); // pegar o Id max da visita

$cargo = $_SESSION['cargo'] ?? '';
if (($cargo == "Med_auditor") || ($cargo == "Enf_Auditor")) {
    $cargo;
} else {
    $cargo = null;
};
$condicoesvisita = [
    // strlen($cargo) ? ' se.cargo_user = " ' . $cargo . ' " '  : null,
    strlen($id_internacao) ? 'ac.id_internacao = :id_internacao' : null
];

$condicoesvisita = array_filter($condicoesvisita);
// REMOVE POSICOES VAZIAS DO FILTRO
$wherevisita = implode(' AND ', $condicoesvisita);
$wherevisitaParams = strlen($id_internacao) ? [':id_internacao' => (int)$id_internacao] : [];

$ultimoReg = (int)($visitaMax[0]['id_visita'] ?? 0);

$contarVis = 0; //contar numero de visitas por internacao 
$queryVis = $internacaoDAO->selectAllInternacaoCountVis($wherevisita, null, null, $wherevisitaParams);
$contarVis = (int)($queryVis[0]['numero_de_id_visita'] ?? 0);

$internacoesContexto = array_values((array)$internacaoList);
$internacaoAtual = is_array($internacoesContexto[0] ?? null) ? $internacoesContexto[0] : [];
$internacaoId = $internacaoAtual['id_internacao'] ?? '';
$internacaoHospital = $internacaoAtual['nome_hosp'] ?? '';
$internacaoPaciente = $internacaoAtual['nome_pac'] ?? '';
$internacaoDataRaw = $internacaoAtual['data_intern_int'] ?? '';
$internacaoDataBR = $internacaoDataRaw ? date('d/m/Y', strtotime((string)$internacaoDataRaw)) : '';
$internacaoHospitalId = $internacaoAtual['id_hospital'] ?? '';
$internacaoPacienteId = $internacaoAtual['fk_paciente_int'] ?? '';
?>
<link rel="stylesheet" href="<?= $BASE_URL ?>css/form_cad_visita_ai.css?v=<?= filemtime(__DIR__ . '/../css/form_cad_visita_ai.css') ?>">

<div class="visita-page" style="visibility:hidden;">
    <div class="visita-hero">
        <div>
            <h1 id="visita-page-title">Cadastrar visita</h1>
        </div>
        <div class="visita-hero__actions">
            <?php if ($contarVis > 0): ?>
            <button type="button" class="btn btn-sm btn-visita-historico"
                onclick="var m=document.getElementById('myModal1');document.querySelectorAll('.modal-backdrop').forEach(function(b){b.remove();});m.style.display='block';m.classList.add('show');m.removeAttribute('aria-hidden');m.setAttribute('aria-modal','true');m.setAttribute('role','dialog');document.body.classList.add('modal-open');">
                <i class="bi bi-eye me-2"></i>
                Visitas Anteriores
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="visita-page__content">
    <form action="<?= $BASE_URL ?>process_visita.php" id="add-visita-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="type" value="create">
        <input type="hidden" name="timer_vis" id="timer_vis" value="">

        <div class="visita-card visita-card--general">
            <div class="visita-card__header">
                <div>
                    <h2 class="visita-card__title">Dados da visita</h2>
                </div>
                    <span class="visita-card__tag" id="visita-main-tag">Informações principais</span>
            </div>
            <div class="visita-card__body">
        <div class="form-group row visita-dados-row">
            <div class="visita-summary-grid">
                <div class="visita-summary-card visita-summary-card--small">
                    <span class="visita-summary-card__label">Reg Int</span>
                    <strong class="visita-summary-card__value"><?= htmlspecialchars((string)$internacaoId, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="visita-summary-card visita-summary-card--wide">
                    <span class="visita-summary-card__label">Hospital</span>
                    <strong class="visita-summary-card__value"><?= htmlspecialchars((string)$internacaoHospital, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="visita-summary-card visita-summary-card--wide">
                    <span class="visita-summary-card__label">Paciente</span>
                    <strong class="visita-summary-card__value"><?= htmlspecialchars((string)$internacaoPaciente, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="visita-summary-card visita-summary-card--medium">
                    <span class="visita-summary-card__label">Data internação</span>
                    <strong class="visita-summary-card__value"><?= htmlspecialchars((string)$internacaoDataBR, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="visita-summary-card visita-summary-card--small">
                    <span class="visita-summary-card__label">Visita No.</span>
                    <input type="text" readonly class="visita-summary-card__input"
                        value="<?= $contarVis + 1 ?>" id="visita_no_vis" name="visita_no_vis">
                </div>
            </div>
            <div class="visita-head-grid">
            <div class="form-group visita-head-field visita-head-field--small">
                <?php
                // Alterado de 'd-m-Y' para 'Y-m-d' para funcionar nos inputs type="date"
                $agora = date('Y-m-d');
                $agoraLanc = $agora;
                ?>
                <label for="data_visita_vis">Data da Visita</label>

                <input type="date" value="<?= $agora; ?>" class="form-control" id="data_visita_vis"
                    name="data_visita_vis">

                <p id="data-visita-error" class="fc-inline-error" style="display:none;">Data Inválida</p>
            </div>

            <div class="form-group visita-head-field visita-head-field--small visita-field">
                <label for="data_lancamento_vis">Data do lançamento</label>
                <input type="date" value="<?= $agoraLanc; ?>" class="form-control"
                    id="data_lancamento_vis" name="data_lancamento_vis">
            </div>

            <div class="form-group visita-head-field visita-head-field--wide">
                <label for="retificou">Retificar Visita</label>
                <div class="visita-inline-clear">
                    <select class="form-control" id="retificou" name="retificou">
                        <option value="">Selecione a visita</option>
                        <?php foreach ((array) $visitasAntigas as $visita): ?>
                        <?php if (is_array($visita) && isset($visita['visita_no_vis'])): ?>
                        <?php
                            $visitaNoOption = (int)($visita['visita_no_vis'] ?? 0);
                            $visitaIdOption = (int)($visita['id_visita'] ?? 0);
                            $retificarSelected = (
                                isset($editVisitaIdParam, $editVisitaIdReal)
                                && $editVisitaIdParam
                                && ($visitaNoOption === (int)$editVisitaIdParam || $visitaIdOption === (int)$editVisitaIdReal)
                            );
                            $dataVisitaOption = 'Data não informada';
                            if (!empty($visita['data_visita_vis'])) {
                                $dataVisitaObj = DateTime::createFromFormat('Y-m-d', (string)$visita['data_visita_vis']);
                                if (!$dataVisitaObj) {
                                    $dataVisitaTs = strtotime((string)$visita['data_visita_vis']);
                                    $dataVisitaObj = $dataVisitaTs ? (new DateTime())->setTimestamp($dataVisitaTs) : null;
                                }
                                if ($dataVisitaObj instanceof DateTime) {
                                    $dataVisitaOption = $dataVisitaObj->format('d/m/Y');
                                }
                            }
                        ?>
                        <option value="<?= $visita['visita_no_vis'] ?>" <?= $retificarSelected ? 'selected' : '' ?>>
                            Visita ID <?= $visita['visita_no_vis'] ?> -
                            <?= htmlspecialchars($dataVisitaOption, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="visita-inline-clear__btn" data-clear-select="retificou" aria-label="Limpar visita retificada">&times;</button>
                </div>
            </div>
            </div>

            <!-- Campo de antecedentes removido conforme solicitação -->
            <input type="hidden" value="" id="id_visita_edit" name="id_visita_edit">
            <input type="hidden" class="form-control" id="usuario_create" value="<?= $_SESSION['email_user'] ?>"
                name="usuario_create">
            <input type="hidden" class="form-control" id="fk_usuario_vis" value="<?= $idSessao ?>"
                name="fk_usuario_vis">
            <input type="hidden" class="form-control" id="visita_med_vis" name="visita_med_vis"
                value="<?= $defaultVisitaMed ?>">
            <input type="hidden" class="form-control" id="visita_enf_vis" name="visita_enf_vis"
                value="<?= $defaultVisitaEnf ?>">
            <input type="hidden" class="form-control" id="visita_auditor_prof_med" name="visita_auditor_prof_med"
                value="<?= $defaultAuditorMed ?>">
            <input type="hidden" class="form-control" id="visita_auditor_prof_enf" name="visita_auditor_prof_enf"
                value="<?= $defaultAuditorEnf ?>">
            <input type="hidden" class="form-control" value="<?= $id_internacao ?>" id="fk_internacao_vis"
                name="fk_internacao_vis" placeholder="">
            <input type="hidden" id="id_hospital" name="id_hospital" value="<?= htmlspecialchars((string)$internacaoHospitalId, ENT_QUOTES, 'UTF-8') ?>">

            <input type="hidden" class="form-control" id="fk_int_visita" name="fk_int_visita"
                value="<?= $ultimoReg + 1 ?>">

            <input type="hidden" class="form-control" id="fk_paciente_int" name="fk_paciente_int"
                value="<?= htmlspecialchars((string)$internacaoPacienteId, ENT_QUOTES, 'UTF-8') ?>">

            <input type="hidden" class="form-control" id="data_internacao" name="data_internacao"
                value="<?= htmlspecialchars((string)$internacaoDataBR, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" class="form-control" id="data_intern_int" name="data_intern_int"
                value="<?= htmlspecialchars((string)$internacaoDataBR, ENT_QUOTES, 'UTF-8') ?>">
        </div>
            </div>
        </div>
            <?php if ($mostrarCadastroCentral): ?>
            <div class="visita-card visita-card--central" id="cadastro-central-visita">
                <div class="visita-card__header">
                    <div>
                        <p class="visita-card__eyebrow">Cadastro central</p>
                        <h3 class="visita-card__title">Responsável pela visita</h3>
                    </div>
                    <span class="visita-card__tag">Obrigatório selecionar tipo e responsável</span>
                </div>
                <div class="visita-card__body">
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-3">
                            <label class="form-label" for="visita_resp_tipo">Tipo de responsável</label>
                            <select id="visita_resp_tipo" class="form-select form-select-sm">
                                <option value="">(sem seleção)</option>
                                <option value="med">Médico auditor</option>
                                <option value="enf">Enfermeiro auditor</option>
                            </select>
                        </div>
                        <div class="col-sm-4 d-none" id="box_visita_resp_med">
                            <label class="form-label" for="visita_resp_med_id">Selecionar médico</label>
                            <select id="visita_resp_med_id" class="form-select form-select-sm">
                                <option value="">Selecione</option>
                                <?php foreach ($medicosAud as $med): ?>
                                <option value="<?= (int)$med['id_usuario'] ?>">
                                    <?= htmlspecialchars($med['usuario_user'] ?? ('#' . $med['id_usuario'])) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4 d-none" id="box_visita_resp_enf">
                            <label class="form-label" for="visita_resp_enf_id">Selecionar enfermeiro</label>
                            <select id="visita_resp_enf_id" class="form-select form-select-sm">
                                <option value="">Selecione</option>
                                <?php foreach ($enfsAud as $enf): ?>
                                <option value="<?= (int)$enf['id_usuario'] ?>">
                                    <?= htmlspecialchars($enf['usuario_user'] ?? ('#' . $enf['id_usuario'])) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="visita-card visita-card--auditoria">
                <div class="visita-card__header">
                    <div>
                        <p class="visita-card__eyebrow">Auditoria</p>
                        <h3 class="visita-card__title">Relatórios e observações</h3>
                    </div>
                </div>
                <div class="visita-card__body">
                    <div class="clinical-text-field">
                        <div class="clinical-text-field__head">
                            <label for="rel_visita_vis">Relatório de Auditoria</label>
                            <div class="clinical-text-field__actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-clean-text="rel_visita_vis">Limpar formatação</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-ai-improve="rel_visita_vis">Organizar com IA</button>
                            </div>
                        </div>
                        <div id="cronicos-relatorio-alert" class="fc-chronic-alert"
                            style="display:none"
                            hidden>
                            <div class="fc-chronic-alert__title">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                Alerta de condição crônica
                            </div>
                            <p class="fc-chronic-alert__text">
                                Foram identificados termos compatíveis com doenças crônicas no relatório:
                                <strong data-role="matched-list"></strong>.
                            </p>
                            <p class="fc-chronic-alert__note" data-role="auto-note"></p>
                        </div>
                        <textarea type="textarea" rows="2" onclick="aumentarTextAudit()" class="form-control fc-no-resize"
                            id="rel_visita_vis" name="rel_visita_vis" autocomplete="off"
                            autocorrect="off" autocapitalize="none" spellcheck="false"></textarea>
                        <div class="d-flex justify-content-end mt-1">
                            <small class="text-muted" data-counter-for="rel_visita_vis">0/5000</small>
                        </div>
                    </div>
                    <div class="clinical-text-field">
                        <div class="clinical-text-field__head">
                            <label for="acoes_int_vis">Ações da Auditoria</label>
                            <div class="clinical-text-field__actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-clean-text="acoes_int_vis">Limpar formatação</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-ai-improve="acoes_int_vis">Organizar com IA</button>
                            </div>
                        </div>
                        <textarea type="textarea" rows="2" onclick="aumentarTextAcoes()" class="form-control fc-no-resize"
                            id="acoes_int_vis" name="acoes_int_vis" autocomplete="off"
                            autocorrect="off" autocapitalize="none" spellcheck="false"></textarea>
                        <div class="d-flex justify-content-end mt-1">
                            <small class="text-muted" data-counter-for="acoes_int_vis">0/5000</small>
                        </div>
                    </div>
                    <div class="clinical-text-field">
                        <div class="clinical-text-field__head">
                            <label for="programacao_enf">Programação Terapêutica</label>
                            <div class="clinical-text-field__actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-clean-text="programacao_enf">Limpar formatação</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-ai-improve="programacao_enf">Organizar com IA</button>
                            </div>
                        </div>
                        <textarea type="textarea" rows="2" class="form-control fc-no-resize"
                            onclick="aumentarTextProgVis()" id="programacao_enf"
                            name="programacao_enf" autocomplete="off" autocorrect="off" autocapitalize="none"
                            spellcheck="false"></textarea>
                        <div class="d-flex justify-content-end mt-1">
                            <small class="text-muted" data-counter-for="programacao_enf">0/5000</small>
                        </div>
                    </div>
                    <div class="ia-highlight-box">
                        <div class="ia-highlight-box__header">
                            <div class="ia-highlight-box__title-wrap">
                                <div>
                                    <p class="ia-highlight-box__eyebrow">Inteligência Artificial</p>
                                    <h3 class="ia-highlight-box__title">Assistente de parecer clínico</h3>
                                </div>
                                <span class="parecer-ia-powered">
                                    <i class="bi bi-stars"></i>
                                    IA conectada
                                </span>
                            </div>
                            <div class="auditoria-actions auditoria-actions--ia">
                                <input type="file" id="pdf-visita-input" accept="application/pdf,.pdf,image/png,image/jpeg,image/jpg,.png,.jpg,.jpeg" hidden>
                                <button type="button" class="btn btn-sm btn-outline-secondary auditoria-action-btn" id="btn-ler-pdf-visita">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                    LER PDF/IMAGEM
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary auditoria-action-btn auditoria-action-btn--subtle-ia" id="btn-executar-prompt-uti-visita">
                                    <i class="bi bi-cpu"></i>
                                    Executar Prompt UTI
                                </button>
                            </div>
                        </div>
                        <div class="parecer-ia-card">
                            <div class="parecer-ia-card__header">
                                <div class="parecer-ia-title-wrap">
                                    <h4>Parecer IA</h4>
                                </div>
                                <button type="button" class="parecer-ia-toggle" id="btn-toggle-parecer-visita-ia" aria-expanded="false" aria-controls="parecer-visita-ia-body">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                            <div id="parecer-visita-ia-status" class="parecer-ia-status" hidden></div>
                            <div class="parecer-ia-card__body" id="parecer-visita-ia-body" hidden>
                                <div id="parecer-visita-ia-content" class="parecer-ia-content">
                                    <p class="parecer-ia-empty">Nenhum parecer gerado.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ENTRADA DE DADOS AUTOMATICOS NO INPUT-->
            <div style="display:none;">
                <input type="hidden" class="form-control" value="n" id="internado_uti_int" name="internado_uti_int">
            </div>
            <div style="display:none;">
                <input type="hidden" class="form-control" value="n" id="internacao_uti_int" name="internacao_uti_int">
            </div>
            <div style="display:none;">
                <input type="hidden" class="form-control" value="s" id="internacao_ativa_int"
                    name="internacao_ativa_int">
            </div>
            <div class="visita-card visita-card--tabelas">
                <div class="visita-card__header tabelas-adicionais-card__header">
                    <div>
                        <p class="visita-card__eyebrow tabelas-adicionais-card__eyebrow">Tabelas adicionais</p>
                        <h3 class="visita-card__title tabelas-adicionais-card__title">Complementos da visita</h3>
                    </div>
                </div>
                <div class="visita-card__body">
                    <div class="form-group row d-flex justify-content-center align-items-end tabelas-selects">
                        <div class="form-group tabelas-col">
                            <label class="control-label" for="relatorio-detalhado">Relatório detalhado</label>
                            <select class="input-lg-fullcare form-control detail-select" id="relatorio-detalhado" name="relatorio-detalhado">
                                <option value="">Selecione</option>
                                <option value="s">Sim</option>
                                <option value="n">Não</option>
                            </select>
                        </div>
                        <?php if ($_SESSION['cargo'] === 'Med_auditor' || ($_SESSION['cargo'] === 'Diretoria')) { ?>

                        <div class="form-group tabelas-col">
                            <label class="control-label" for="select_tuss">Tuss</label>
                            <select class="input-lg-fullcare form-control select-purple" id="select_tuss" name="select_tuss">
                                <option value="">Selecione</option>
                                <option value="s" <?= (($editAdditionalSelects['tuss'] ?? '') === 's') ? 'selected' : '' ?>>Sim</option>
                                <option value="n">Não</option>
                            </select>
                        </div>
                        <div class="form-group tabelas-col">
                            <label class="control-label" for="select_prorrog">Prorrogação</label>
                            <select class="input-lg-fullcare form-control select-purple" id="select_prorrog" name="select_prorrog">
                                <option value="">Selecione</option>
                                <option value="s" <?= (($editAdditionalSelects['prorrog'] ?? '') === 's') ? 'selected' : '' ?>>Sim</option>
                                <option value="n">Não</option>
                            </select>
                        </div>
                        <?php }; ?>
                        <div class="form-group tabelas-col">
                            <label class="control-label" for="select_gestao">Gestão Assistencial</label>

                            <select class="input-lg-fullcare form-control select-purple" id="select_gestao" name="select_gestao">
                                <option value="">Selecione</option>
                                <option value="s" <?= (($editAdditionalSelects['gestao'] ?? '') === 's') ? 'selected' : '' ?>>Sim</option>
                                <option value="n">Não</option>
                            </select>
                        </div>
                        <div class="form-group tabelas-col">
                            <label class="control-label" for="select_uti">UTI</label>
                            <select class="input-lg-fullcare form-control select-purple" id="select_uti" name="select_uti">
                                <option value="">Selecione</option>
                                <option value="s" <?= (($editAdditionalSelects['uti'] ?? '') === 's') ? 'selected' : '' ?>>Sim</option>
                                <option value="n">Não</option>
                            </select>
                        </div>
                        <?php if ($_SESSION['cargo'] === 'Med_auditor' || ($_SESSION['cargo'] === 'Diretoria')) { ?>

                        <div class="form-group tabelas-col">
                            <label class="control-label" for="select_negoc">Negociações</label>
                            <select class="input-lg-fullcare form-control select-purple" id="select_negoc" name="select_negoc">
                                <option value="">Selecione</option>
                                <option value="s" <?= (($editAdditionalSelects['negoc'] ?? '') === 's') ? 'selected' : '' ?>>Sim</option>
                                <option value="n">Não</option>
                            </select>
                        </div>
                        <?php }; ?>

                        <br>
                    </div>
                    <!-- FORMULARIO DE GESTÃO -->
                    <?php include_once('formularios/form_cad_internacao_tuss.php'); ?>
                    <!-- FORMULARIO DE GESTÃO -->

                    <?php include_once('formularios/form_cad_internacao_gestao.php'); ?>

                    <!-- FORMULARIO DE UTI -->
                    <?php include_once('formularios/form_cad_internacao_uti.php'); ?>

                    <!-- FORMULARIO DE PRORROGACOES -->
                    <?php include_once('formularios/form_cad_internacao_prorrog.php'); ?>

                    <!-- <FORMULARO DE NEGOCIACOES -->
                    <?php include_once('formularios/form_cad_internacao_negoc.php'); ?>
                    <?php include_once('formularios/form_cad_visita_detalhes.php'); ?>
                </div>
            </div>
            <script>
            function toggleDetalhesVisita() {
                var select = document.getElementById('relatorio-detalhado');
                var hidden = document.getElementById('select_detalhes');
                var wrapper = document.getElementById('detalhes-card-wrapper');
                var detalhes = document.getElementById('div-detalhado');
                if (!select || !wrapper || !detalhes) return;
                var show = select.value === 's';
                if (hidden) hidden.value = select.value || '';
                wrapper.style.display = show ? 'block' : 'none';
                detalhes.style.display = show ? 'block' : 'none';
            }

            document.addEventListener('DOMContentLoaded', function() {
                var select = document.getElementById('relatorio-detalhado');
                if (select) {
                    select.addEventListener('change', toggleDetalhesVisita);
                }
                toggleDetalhesVisita();
            });
            </script>

            <div class="visita-actions">
                <button type="submit" class="btn btn-success btn-submit-standard" id="visita-submit-btn">
                    <i class="fas fa-check"></i> <span id="visita-submit-label">Cadastrar</span>
                </button>
                <button type="button" class="btn btn-sm btn-clear-draft fc-clear-draft" data-clear-clinical-draft="fields">Limpar rascunho</button>
                <div class="alert" id="alert" role="alert"></div>
            </div>
    </form>
    </div>
</div>

<div class="modal fade fc-form-modal fc-form-modal--confirmation" id="modalNovoLancamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visita cadastrada</h5>
            </div>
            <div class="modal-body">
                Haverá novo lançamento?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-action="encerrar-lancamento">Não</button>
                <button type="button" class="btn btn-success" data-action="continuar-lancamento">Sim</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para abrir tela de cadastro -->
<div class="modal fade fc-history-modal fc-form-modal fc-form-modal--history" id="myModal1">
    <div class="modal-dialog  modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="page-title">Visitas</h4>
                <p class="page-description">Informações
                    sobre visitas anteriores</p>
                <button type="button" class="btn-close btn-close-white"
                    aria-label="Fechar" onclick="var m=document.getElementById('myModal1');m.classList.remove('show');m.style.display='none';m.setAttribute('aria-hidden','true');document.querySelectorAll('.modal-backdrop').forEach(function(b){b.remove();});document.body.classList.remove('modal-open');document.body.style.removeProperty('overflow');document.body.style.removeProperty('padding-right');"></button>
            </div>
            <div class="modal-body">
                <?php

                if (!$visitas) {
                    echo ("<br>");
                    echo ("<p class='fc-empty-message'> <b>-- Esta internação ainda não possui visita -- </b></p>");
                    echo ("<br>");
                } else { ?>
                <h6 class="page-title">Relatórios anteriores</h6>
                <div class="visit-history-table-wrap fc-history-table-wrap">
                <table class="table table-sm table-striped table-hover table-condensed visit-history-table fc-history-table">
                    <thead>
                        <tr>
                            <th scope="col" class="th-w-2">Visita</th>
                            <th scope="col" class="th-w-2">Data visita</th>
                            <th scope="col" class="th-w-2">Med</th>
                            <th scope="col" class="th-w-2">Enf</th>
                            <th scope="col" class="th-w-15">Relatório</th>
                            <th scope="col" class="th-w-2">Visualizar</th>
                            <th scope="col" class="th-w-2">Editar</th>
                            <th scope="col" class="th-w-2">Remover</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $hoje = date('Y-m-d');
                            $atual = new DateTime($hoje);
                            foreach ($visitas as $intern):
                            ?>
                        <tr>
                            <td scope="row"><?= $intern["id_visita"] ?></td>
                            <td scope="row"><?= !empty($intern['data_visita_vis'])
                                                        ? date("d/m/Y", strtotime($intern['data_visita_vis']))
                                                        : date("d/m/Y", strtotime($intern['data_visita_int']));; ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?php if ($intern["visita_med_vis"] == "s") { ?><span id="boot-icon" class="bi bi-check fc-status-check"></span>
                                <?php }; ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?php if ($intern["visita_enf_vis"] == "s") { ?><span id="boot-icon" class="bi bi-check fc-status-check"></span>
                                <?php }; ?>
                            </td>
                            <td scope="row"><?= $intern['rel_visita_vis'] = !empty($intern['rel_visita_vis']) ? $intern['rel_visita_vis'] : $intern['rel_int'];
                                                    ?></td>
                            <td><a href="<?= $BASE_URL ?>show_visita.php?id_visita=<?= $intern["id_visita"] ?>"><i
                                        class="aparecer-acoes bi bi-eye text-success"></i></a>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-link p-0 text-primary"
                                    onclick="selecionarVisitaParaEditar(<?= (int) $intern['id_visita'] ?>)"
                                    title="Editar esta visita">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <?php if (empty($intern['retificado'])): ?>
                                <button type="button" class="btn btn-link p-0 text-danger"
                                    data-bs-toggle="modal" data-bs-target="#modalDeleteVisitaList"
                                    data-visita-id="<?= (int) $intern['id_visita'] ?>" title="Remover esta visita">
                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                </button>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php }; ?>
            </div>

        </div>
    </div>
</div>

<script>
(function() {
    var historyModal = document.getElementById('myModal1');
    if (!historyModal) return;

    function cleanupHistoryModal() {
        historyModal.classList.remove('show');
        historyModal.style.display = 'none';
        historyModal.setAttribute('aria-hidden', 'true');
        historyModal.removeAttribute('aria-modal');
        historyModal.removeAttribute('role');
        document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
            backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    window.abrirHistoricoVisitas = function() {
        cleanupHistoryModal();
        historyModal.style.display = 'block';
        historyModal.classList.add('show');
        historyModal.removeAttribute('aria-hidden');
        historyModal.setAttribute('aria-modal', 'true');
        historyModal.setAttribute('role', 'dialog');
        document.body.classList.add('modal-open');
    };

    window.fecharHistoricoVisitas = cleanupHistoryModal;

    historyModal.addEventListener('click', function(event) {
        if (event.target === historyModal) cleanupHistoryModal();
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && historyModal.classList.contains('show')) {
            cleanupHistoryModal();
        }
    });
})();
</script>
<div class="modal fade fc-form-modal fc-form-modal--danger" id="modalDeleteVisitaList" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Remover visita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Deseja realmente deletar esta visita?</p>
                <div class="alert alert-danger d-none js-delete-feedback" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm-delete-row">Remover</button>
            </div>
        </div>
    </div>
</div>
<textarea id="visita-client-config-json" hidden><?= htmlspecialchars(json_encode([
    'baseUrl' => (string) $BASE_URL,
    'draftKey' => 'fullcare:visita:' . (string)($id_internacao ?? ($_GET['id_internacao'] ?? 'local')),
    'sessionId' => (string) $idSessao,
    'isMedSessao' => (bool) $isMedSessao,
    'isEnfSessao' => (bool) $isEnfSessao,
    'listaInternacoesUrl' => rtrim($BASE_URL, '/') . '/internacoes/lista',
    'csrf' => csrf_token(),
    'acomodacoes' => json_decode($jsonAcomodacoes ?? '[]', true) ?: [],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_NOQUOTES, 'UTF-8') ?></textarea>
<script src="<?= $BASE_URL ?>js/select_visita.js?v=<?= filemtime(__DIR__ . '/../js/select_visita.js') ?>"></script>
<script src="<?= $BASE_URL ?>js/text_cad_visita.js?v=<?= filemtime(__DIR__ . '/../js/text_cad_visita.js') ?>"></script>
<script>
const __VISITA_CLIENT_CONFIG_NODE = document.getElementById('visita-client-config-json');
const __VISITA_CLIENT_CONFIG = JSON.parse(__VISITA_CLIENT_CONFIG_NODE ? __VISITA_CLIENT_CONFIG_NODE.value : '{}');
window.clinicalTextToolsConfig = {
    baseUrl: __VISITA_CLIENT_CONFIG.baseUrl || '',
    draftKey: __VISITA_CLIENT_CONFIG.draftKey || 'fullcare:visita:local',
    fields: ['rel_visita_vis', 'acoes_int_vis', 'programacao_enf'],
    autosaveStatusId: 'clinical-autosave-status'
};
</script>
<script src="<?= $BASE_URL ?>js/clinical_text_tools.js?v=<?= filemtime(__DIR__ . '/../js/clinical_text_tools.js') ?>"></script>
<script>
(function() {
    const fkInput = document.getElementById('fk_usuario_vis');
    if (!fkInput) return;

    const respTipo = document.getElementById('visita_resp_tipo');
    const boxMed = document.getElementById('box_visita_resp_med');
    const boxEnf = document.getElementById('box_visita_resp_enf');
    const selectMed = document.getElementById('visita_resp_med_id');
    const selectEnf = document.getElementById('visita_resp_enf_id');
    const flagMed = document.getElementById('visita_med_vis');
    const flagEnf = document.getElementById('visita_enf_vis');
    const auditorMed = document.getElementById('visita_auditor_prof_med');
    const auditorEnf = document.getElementById('visita_auditor_prof_enf');

    const sessionId = __VISITA_CLIENT_CONFIG.sessionId || '';
    const isMedSessao = !!__VISITA_CLIENT_CONFIG.isMedSessao;
    const isEnfSessao = !!__VISITA_CLIENT_CONFIG.isEnfSessao;

    function applySelection(userId, tipo) {
        const effectiveTipo = tipo || (isMedSessao ? 'med' : (isEnfSessao ? 'enf' : ''));
        const effectiveUserId = userId || sessionId || '';
        if (fkInput) fkInput.value = userId || '';
        if (flagMed) flagMed.value = (effectiveTipo === 'med') ? 's' : 'n';
        if (flagEnf) flagEnf.value = (effectiveTipo === 'enf') ? 's' : 'n';
        if (auditorMed) auditorMed.value = (effectiveTipo === 'med') ? effectiveUserId : '';
        if (auditorEnf) auditorEnf.value = (effectiveTipo === 'enf') ? effectiveUserId : '';
    }

    function resetToSession() {
        applySelection(sessionId, isMedSessao ? 'med' : (isEnfSessao ? 'enf' : ''));
    }

    function syncCadastroCentral() {
        const tipo = respTipo ? respTipo.value : '';
        if (tipo === 'med' && selectMed && selectMed.value) {
            applySelection(selectMed.value, 'med');
            if (auditorEnf) auditorEnf.value = '';
        } else if (tipo === 'enf' && selectEnf && selectEnf.value) {
            applySelection(selectEnf.value, 'enf');
            if (auditorMed) auditorMed.value = '';
        } else {
            resetToSession();
        }
    }

    function hide(el) {
        if (!el) return;
        el.classList.add('d-none');
        el.hidden = true;
    }

    function show(el) {
        if (!el) return;
        el.classList.remove('d-none');
        el.hidden = false;
    }

    resetToSession();

    if (!respTipo) return;

    hide(boxMed);
    hide(boxEnf);

    respTipo.addEventListener('change', function() {
        const value = this.value;
        if (selectMed) selectMed.value = '';
        if (selectEnf) selectEnf.value = '';

        hide(boxMed);
        hide(boxEnf);
        resetToSession();

        if (value === 'med') {
            show(boxMed);
        } else if (value === 'enf') {
            show(boxEnf);
        } else {
            resetToSession();
        }
    });

    if (selectMed) selectMed.addEventListener('change', function() {
        syncCadastroCentral();
    });

    if (selectEnf) selectEnf.addEventListener('change', function() {
        syncCadastroCentral();
    });

    const form = document.getElementById('add-visita-form');
    if (form) {
        form.addEventListener('submit', syncCadastroCentral);
    }
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('novo_lancamento_prompt') !== '1') {
        return;
    }

    var cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete('novo_lancamento_prompt');
    window.history.replaceState({}, document.title, cleanUrl.toString());

    var modalEl = document.getElementById('modalNovoLancamento');
    if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    var modal = new bootstrap.Modal(modalEl, {
        backdrop: 'static',
        keyboard: false
    });

    var btnEncerrar = modalEl.querySelector('[data-action="encerrar-lancamento"]');
    var btnContinuar = modalEl.querySelector('[data-action="continuar-lancamento"]');

    if (btnEncerrar) {
        btnEncerrar.addEventListener('click', function() {
            window.location.href = __VISITA_CLIENT_CONFIG.listaInternacoesUrl || 'list_internacao.php';
        }, { once: true });
    }

    if (btnContinuar) {
        btnContinuar.addEventListener('click', function() {
            modal.hide();
        }, { once: true });
    }

    window.setTimeout(function() {
        modal.show();
    }, 150);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalDeleteVisitaList');
    if (!modal) return;
    var confirmBtn = modal.querySelector('[data-action=\"confirm-delete-row\"]');
    var feedback = modal.querySelector('.js-delete-feedback');
    var currentId = null;

    modal.addEventListener('show.bs.modal', function(event) {
        var trigger = event.relatedTarget;
        currentId = trigger ? parseInt(trigger.getAttribute('data-visita-id'), 10) : null;
        if (feedback) {
            feedback.classList.add('d-none');
            feedback.textContent = '';
        }
        if (confirmBtn) confirmBtn.disabled = false;
    });

    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', function() {
        if (!currentId) return;
        confirmBtn.disabled = true;

        var formData = new FormData();
        formData.append('type', 'delete');
        formData.append('id_visita', currentId);
        formData.append('redirect', window.location.href);
        formData.append('ajax', '1');
        formData.append('csrf', __VISITA_CLIENT_CONFIG.csrf || '');

        fetch('process_visita.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function(resp) { return resp.json(); })
            .then(function(res) {
                if (res && res.success) {
                    window.location.reload();
                    return;
                }
                var msg = (res && res.message) ? res.message : 'Não foi possível remover a visita.';
                if (feedback) {
                    feedback.textContent = msg;
                    feedback.classList.remove('d-none');
                } else {
                    alert(msg);
                }
            })
            .catch(function() {
                if (feedback) {
                    feedback.textContent = 'Falha inesperada ao remover a visita.';
                    feedback.classList.remove('d-none');
                } else {
                    alert('Falha inesperada ao remover a visita.');
                }
            })
            .finally(function() {
                confirmBtn.disabled = false;
            });
    });
});
</script>
<script>
// Função para popular os selects "troca_de" e "troca_para" com as acomodações recebidas
function populateSelects(acomodacoes) {
    let options = '<option value="">Selecione a Acomodação</option>';
    window.__NEG_ACOMOD_VALOR_MAP = {};
    acomodacoes.forEach(ac => {
        const nome = String(ac.acomodacao_aco || '').trim();
        if (nome) {
            window.__NEG_ACOMOD_VALOR_MAP[nome.toUpperCase()] = parseFloat(ac.valor_aco || 0);
        }
        options +=
            `<option value="${ac.acomodacao_aco}" data-valor="${ac.valor_aco}">${ac.acomodacao_aco}</option>`;
    });

    // Atualiza os selects com as novas opções
    $('select[name="troca_de"]').html(options);
    $('select[name="troca_para"]').html(options);

    // Limpa os campos relacionados
    $('input[name="saving"]').val('');
    $('input[name="qtd"]').val('');
    $('input[name="saving_show"]').val('').css('color', '');
}

const acomodacoes = __VISITA_CLIENT_CONFIG.acomodacoes || [];

populateSelects(acomodacoes)
</script>

<script>
(function () {
    var form = document.getElementById('add-visita-form');
    var timerField = document.getElementById('timer_vis');
    var pacienteField = document.getElementById('fk_paciente_int');
    var timerStart = null;

    function startTimer() {
        if (timerStart === null) {
            timerStart = Date.now();
        }
    }

    if (pacienteField) {
        if (pacienteField.value) {
            startTimer();
        } else {
            pacienteField.addEventListener('change', function () {
                if (this.value) {
                    startTimer();
                }
            });
        }
    } else {
        startTimer();
    }

    ['pacienteSelecionado', 'paciente-selecionado', 'visitaPacienteSelecionado'].forEach(function (evtName) {
        document.addEventListener(evtName, startTimer);
    });

    if (form && timerField) {
        form.addEventListener('submit', function () {
            if (timerStart !== null) {
                var elapsed = Math.max(0, Math.round((Date.now() - timerStart) / 1000));
                timerField.value = elapsed;
            }
        });
    }
})();
</script>


<script>
var text_exames = document.querySelector("#exames_enf");

function aumentarTextExames() {
    if (text_exames.rows == "2") {
        text_exames.rows = "30"
    } else {
        text_exames.rows = "2"
    }
}

// mudar linhas da oportunidades 
var text_oport = document.querySelector("#oportunidades_enf");

function aumentarTextOport() {
    if (text_oport.rows == "2") {
        text_oport.rows = "30"
    } else {
        text_oport.rows = "2"
    }
}

// mudar linhas da programacao 
var text_programacao = document.querySelector("#programacao_enf");

function aumentarTextProgramacao() {
    if (text_programacao.rows == "2") {
        text_programacao.rows = "30"
    } else {
        text_programacao.rows = "2"
    }
}
</script>
<textarea id="visita-bootstrap-json" hidden><?= htmlspecialchars(json_encode([
    'dataInternacaoVis' => !empty($ultimaVis['data_intern_int']) ? date('Y-m-d', strtotime($ultimaVis['data_intern_int'])) : '',
    'tussPorVisita' => $tussPorVisita,
    'tussPorInternacao' => $tussPorInternacao,
    'negPorVisita' => $negPorVisita,
    'negPorInternacao' => $negPorInternacao,
    'gestaoPorVisita' => $gestaoPorVisita,
    'gestaoPorInternacao' => $gestaoPorInternacao,
    'utiPorVisita' => $utiPorVisita,
    'utiPorInternacao' => $utiPorInternacao,
    'prorrogPorVisita' => $prorrogPorVisita,
    'prorrogPorInternacao' => $prorrogPorInternacao,
    'editVisitaIdReal' => (int)($editVisitaIdReal ?? 0),
    'prorrogEditRows' => $prorrogEditRows ?? [],
    'editAdditionalSelects' => $editAdditionalSelects ?? [],
    'visitaInterMap' => $visitaInterMap,
    'visitasAntigas' => $visitasAntigas ?? [],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_NOQUOTES, 'UTF-8') ?></textarea>
<script>
const __VISITA_BOOTSTRAP_NODE = document.getElementById('visita-bootstrap-json');
const __VISITA_BOOTSTRAP = JSON.parse(__VISITA_BOOTSTRAP_NODE ? __VISITA_BOOTSTRAP_NODE.value : '{}');
const dataVisitaInput = document.getElementById('data_visita_vis');
const dataVisitaError = document.getElementById('data-visita-error');
const dataInternacaoVis = new Date(__VISITA_BOOTSTRAP.dataInternacaoVis || ''); // Data da internação
const hoje = new Date(); // Data atual

dataVisitaInput.addEventListener('change', () => {
    const dataVisita = new Date(dataVisitaInput.value);

    if (dataVisita < dataInternacaoVis || dataVisita > hoje) {
        dataVisitaError.style.display = 'block'; // Exibe o alerta
    } else {
        dataVisitaError.style.display = 'none'; // Oculta o alerta
    }
});

// Oculta o alerta ao clicar no campo
dataVisitaInput.addEventListener('click', () => {
    dataVisitaError.style.display = 'none';
});
</script>

<script>
var currentEditVisitaId = null;
var isHydratingAdditionalSection = false;

window.VISITA_TUSS_DATA = __VISITA_BOOTSTRAP.tussPorVisita || {};
window.VISITA_TUSS_FALLBACK = __VISITA_BOOTSTRAP.tussPorInternacao || {};
window.VISITA_NEG_DATA = __VISITA_BOOTSTRAP.negPorVisita || {};
window.VISITA_NEG_FALLBACK = __VISITA_BOOTSTRAP.negPorInternacao || {};
window.VISITA_GESTAO_DATA = __VISITA_BOOTSTRAP.gestaoPorVisita || {};
window.VISITA_GESTAO_FALLBACK = __VISITA_BOOTSTRAP.gestaoPorInternacao || {};
window.VISITA_UTI_DATA = __VISITA_BOOTSTRAP.utiPorVisita || {};
window.VISITA_UTI_FALLBACK = __VISITA_BOOTSTRAP.utiPorInternacao || {};
window.VISITA_PRORR_DATA = __VISITA_BOOTSTRAP.prorrogPorVisita || {};
window.VISITA_PRORR_FALLBACK = __VISITA_BOOTSTRAP.prorrogPorInternacao || {};
window.VISITA_EDIT_ID_REAL = __VISITA_BOOTSTRAP.editVisitaIdReal || 0;
window.VISITA_PRORR_EDIT_ROWS = __VISITA_BOOTSTRAP.prorrogEditRows || [];
window.VISITA_EDIT_ADDITIONAL_SELECTS = __VISITA_BOOTSTRAP.editAdditionalSelects || {};
window.VISITA_INTER_MAP = __VISITA_BOOTSTRAP.visitaInterMap || {};
</script>
<script>
const __VISITA_INTER_MAP = window.VISITA_INTER_MAP || {};
const __TUSS_FALLBACK = window.VISITA_TUSS_FALLBACK || {};
const __NEG_FALLBACK = window.VISITA_NEG_FALLBACK || {};
const __GESTAO_FALLBACK = window.VISITA_GESTAO_FALLBACK || {};
const __UTI_FALLBACK = window.VISITA_UTI_FALLBACK || {};
const __PRORR_FALLBACK = window.VISITA_PRORR_FALLBACK || {};
const __VISITA_EDIT_ID_REAL = window.VISITA_EDIT_ID_REAL || 0;
const __VISITA_PRORR_EDIT_ROWS = Array.isArray(window.VISITA_PRORR_EDIT_ROWS) ? window.VISITA_PRORR_EDIT_ROWS : [];
const __VISITA_EDIT_ADDITIONAL_SELECTS = window.VISITA_EDIT_ADDITIONAL_SELECTS || {};
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const select = document.getElementById("retificou");

    if (!select.value) {
        const hoje = new Date();
        const dia = String(hoje.getDate()).padStart(2, '0');
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        const ano = hoje.getFullYear();
        const dataExibicao = `${dia}/${mes}/${ano}`;
        const dataValor = `${ano}-${mes}-${dia}`;
        const novaOption = document.createElement("option");
        novaOption.value = dataValor;
        novaOption.text = `Data Atual - ${dataExibicao}`;
        select.add(novaOption);
        select.value = dataValor;
    }
});
</script>

<script>
function updateVisitaSelectPlaceholders() {
    const selects = document.querySelectorAll('#add-visita-form select');
    selects.forEach((selectEl) => {
        const empty = !selectEl.value;
        selectEl.classList.toggle('select-placeholder', empty);
    });
}
document.addEventListener('DOMContentLoaded', function() {
    updateVisitaSelectPlaceholders();
    document.querySelectorAll('#add-visita-form select').forEach((selectEl) => {
        selectEl.addEventListener('change', updateVisitaSelectPlaceholders);
    });
});
</script>

<script>
(function() {
    const visitasOriginais = __VISITA_BOOTSTRAP.visitasAntigas || [];
    const visitaMap = {};
    const visitaMapById = {};
    (visitasOriginais || []).forEach((row) => {
        if (!row || typeof row !== 'object') return;
        const noKey = row.visita_no_vis != null ? String(row.visita_no_vis) : null;
        const idKey = row.id_visita != null ? String(row.id_visita) : null;
        if (noKey) visitaMap[noKey] = row;
        if (idKey) visitaMapById[idKey] = row;
    });

    const selectRet = document.getElementById('retificou');
    const dataVisitaInput = document.getElementById('data_visita_vis');
    const visitaNoInput = document.getElementById('visita_no_vis');
    const relInput = document.getElementById('rel_visita_vis');
    const acoesInput = document.getElementById('acoes_int_vis');
    const examesInput = document.getElementById('exames_enf');
    const oportunidadesInput = document.getElementById('oportunidades_enf');
    const programacaoInput = document.getElementById('programacao_enf');
    const auditorMedInput = document.getElementById('visita_auditor_prof_med');
    const auditorEnfInput = document.getElementById('visita_auditor_prof_enf');
    const flagMedInput = document.getElementById('visita_med_vis');
    const flagEnfInput = document.getElementById('visita_enf_vis');
    const dataLancInput = document.getElementById('data_lancamento_vis');
    const editIdInput = document.getElementById('id_visita_edit');
    const fkVisitaInput = document.getElementById('fk_int_visita');
    const modalEl = document.getElementById('myModal1');
    const pageTitleEl = document.getElementById('visita-page-title');
    const mainTagEl = document.getElementById('visita-main-tag');
    const submitLabelEl = document.getElementById('visita-submit-label');

    function cleanupModalState() {
        if (modalEl) {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.removeAttribute('aria-modal');
            modalEl.removeAttribute('role');
        }
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        if (!document.querySelector('.modal.show')) {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        }
    }

    window.fecharHistoricoVisitas = function() {
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getInstance(modalEl);
            if (instance) instance.hide();
        } else if (modalEl && window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modalEl).modal('hide');
        }
        window.setTimeout(cleanupModalState, 80);
    };

    window.abrirHistoricoVisitas = function() {
        cleanupModalState();
        if (!modalEl) return;
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        modalEl.removeAttribute('aria-hidden');
        modalEl.setAttribute('aria-modal', 'true');
        modalEl.setAttribute('role', 'dialog');
        document.body.classList.add('modal-open');
    };

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', cleanupModalState);
    }

    if (!selectRet) return;

    function formatLancamentoDateValue(value) {
        if (!value) return '';
        const normalized = String(value).trim();
        const match = normalized.match(/^(\d{4}-\d{2}-\d{2})/);
        if (match) {
            return match[1];
        }
        const parsed = new Date(normalized.replace('T', ' '));
        if (!Number.isNaN(parsed.getTime())) {
            const pad = (n) => String(n).padStart(2, '0');
            return `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())}`;
        }
        return '';
    }

    const defaults = {
        dataVisita: dataVisitaInput ? dataVisitaInput.value : '',
        visitaNo: visitaNoInput ? visitaNoInput.value : '',
        rel: relInput ? relInput.value : '',
        acoes: acoesInput ? acoesInput.value : '',
        exames: examesInput ? examesInput.value : '',
        oportunidades: oportunidadesInput ? oportunidadesInput.value : '',
        programacao: programacaoInput ? programacaoInput.value : '',
        fkVisita: fkVisitaInput ? fkVisitaInput.value : '',
        dataLanc: dataLancInput ? dataLancInput.value : ''
    };

    let lastSyncedVisitaDate = defaults.dataVisita;

    function syncLancamentoWithVisita(force) {
        if (!dataVisitaInput || !dataLancInput) return;
        const dataVisitaValue = dataVisitaInput.value || '';
        if (force || !dataLancInput.value || dataLancInput.value === lastSyncedVisitaDate) {
            dataLancInput.value = dataVisitaValue;
        }
        lastSyncedVisitaDate = dataVisitaValue;
    }

    function syncVisitaFormMode(isEditMode) {
        if (pageTitleEl) {
            pageTitleEl.textContent = isEditMode ? 'Editar visita' : 'Cadastrar visita';
        }
        if (mainTagEl) {
            mainTagEl.textContent = isEditMode ? 'Edição da visita selecionada' : 'Informações principais';
        }
        if (submitLabelEl) {
            submitLabelEl.textContent = isEditMode ? 'Atualizar' : 'Cadastrar';
        }
    }

    function fillCampos(vis) {
        if (visitaNoInput && vis.visita_no_vis != null) {
            visitaNoInput.value = vis.visita_no_vis;
        }
        if (fkVisitaInput && vis.id_visita != null) {
            fkVisitaInput.value = vis.id_visita;
        }
        if (editIdInput) editIdInput.value = vis.id_visita ?? '';
        if (dataVisitaInput && vis.data_visita_vis) {
            dataVisitaInput.value = vis.data_visita_vis;
        }
        if (dataLancInput) {
            dataLancInput.value = vis.data_visita_vis || defaults.dataVisita || defaults.dataLanc || '';
        }
        lastSyncedVisitaDate = dataVisitaInput ? (dataVisitaInput.value || '') : '';
        if (relInput) relInput.value = vis.rel_visita_vis || '';
        if (acoesInput) acoesInput.value = vis.acoes_int_vis || '';
        if (examesInput) examesInput.value = vis.exames_enf || '';
        if (oportunidadesInput) oportunidadesInput.value = vis.oportunidades_enf || '';
        if (programacaoInput) programacaoInput.value = vis.programacao_enf || '';
        if (auditorMedInput) auditorMedInput.value = vis.visita_auditor_prof_med || '';
        if (auditorEnfInput) auditorEnfInput.value = vis.visita_auditor_prof_enf || '';
        if (flagMedInput) flagMedInput.value = vis.visita_med_vis || flagMedInput.value;
        if (flagEnfInput) flagEnfInput.value = vis.visita_enf_vis || flagEnfInput.value;
        currentEditVisitaId = vis.id_visita != null ? String(vis.id_visita) : null;
        syncVisitaFormMode(true);
        resetAdditionalTables();
        const hydrateCurrentVisit = function() {
            if (typeof hydrateAdditionalTablesForVisita === 'function') {
                hydrateAdditionalTablesForVisita(currentEditVisitaId);
            }
            if (typeof signalAdditionalSelectsForVisita === 'function') {
                signalAdditionalSelectsForVisita(currentEditVisitaId);
            }
        };
        hydrateCurrentVisit();
        window.setTimeout(hydrateCurrentVisit, 0);
        window.setTimeout(hydrateCurrentVisit, 100);
    }

    function resetCampos() {
        if (visitaNoInput) visitaNoInput.value = defaults.visitaNo;
        if (dataVisitaInput) dataVisitaInput.value = defaults.dataVisita;
        if (relInput) relInput.value = defaults.rel;
        if (acoesInput) acoesInput.value = defaults.acoes;
        if (examesInput) examesInput.value = defaults.exames;
        if (oportunidadesInput) oportunidadesInput.value = defaults.oportunidades;
        if (programacaoInput) programacaoInput.value = defaults.programacao;
        if (fkVisitaInput) fkVisitaInput.value = defaults.fkVisita;
        if (editIdInput) editIdInput.value = '';
        if (dataLancInput) dataLancInput.value = defaults.dataVisita || defaults.dataLanc;
        lastSyncedVisitaDate = dataVisitaInput ? (dataVisitaInput.value || '') : '';
        currentEditVisitaId = null;
        syncVisitaFormMode(false);
        resetAdditionalTables();
    }

    if (dataVisitaInput && dataLancInput) {
        syncLancamentoWithVisita(true);
        dataVisitaInput.addEventListener('change', function() {
            syncLancamentoWithVisita(false);
        });
    }

    selectRet.addEventListener('change', function() {
        const key = this.value && /^\d+$/.test(this.value) ? this.value : null;
        if (key && visitaMap[key]) {
            fillCampos(visitaMap[key]);
        } else {
            resetCampos();
        }
    });

    window.selecionarVisitaParaEditar = function(idVisita) {
        const mapKey = idVisita != null ? String(idVisita) : null;
        const visita = mapKey ? (visitaMapById[mapKey] || visitaMap[mapKey]) : null;
        if (!visita) return;
        if (selectRet && visita.visita_no_vis != null) {
            selectRet.value = String(visita.visita_no_vis);
        }
        fillCampos(visita);
        if (modalEl && (modalEl.classList.contains('show') || modalEl.style.display === 'block')) {
            if (typeof window.fecharHistoricoVisitas === 'function') {
                window.fecharHistoricoVisitas();
            } else {
                cleanupModalState();
            }
        }
    };

    const params = new URLSearchParams(window.location.search);
    const editVisitaId = params.get('edit_visita');
    if (editVisitaId && /^\d+$/.test(editVisitaId)) {
        window.setTimeout(function() {
            window.selecionarVisitaParaEditar(editVisitaId);
        }, 0);
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-clear-select]').forEach(function(button) {
        button.addEventListener('click', function() {
            var targetId = button.getAttribute('data-clear-select');
            var select = document.getElementById(targetId);
            if (!select) return;
            select.value = '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
});

const GESTAO_FIELD_DEFAULTS = {
    alto_custo_ges: 'n',
    rel_alto_custo_ges: '',
    opme_ges: 'n',
    rel_opme_ges: '',
    home_care_ges: 'n',
    rel_home_care_ges: '',
    desospitalizacao_ges: 'n',
    rel_desospitalizacao_ges: '',
    evento_adverso_ges: 'n',
    rel_evento_adverso_ges: '',
    tipo_evento_adverso_gest: '',
    evento_sinalizado_ges: 'n',
    evento_discutido_ges: 'n',
    evento_negociado_ges: 'n',
    evento_valor_negoc_ges: '',
    evento_retorno_qual_hosp_ges: 'n',
    evento_classificado_hospital_ges: 'n',
    evento_data_ges: '',
    evento_encerrar_ges: 'n',
    evento_impacto_financ_ges: 'n',
    evento_prolongou_internacao_ges: 'n',
    evento_concluido_ges: 'n',
    evento_classificacao_ges: '',
    evento_prorrogar_ges: 'n',
    evento_fech_ges: 'n'
};

const UTI_FIELD_MAP = [
    { key: 'internado_uti', id: 'internado_uti', defaultValue: 's' },
    { key: 'motivo_uti', id: 'motivo_uti', defaultValue: '' },
    { key: 'just_uti', id: 'just_uti', defaultValue: 'Pertinente' },
    { key: 'criterios_uti', id: 'criterio_uti', defaultValue: '' },
    { key: 'data_internacao_uti', id: 'data_internacao_uti', defaultValue: '', formatter: normalizeDateValue },
    { key: 'hora_internacao_uti', id: 'hora_internacao_uti', defaultValue: '', formatter: normalizeTimeValue },
    { key: 'data_alta_uti', id: 'data_alta_uti', defaultValue: '', formatter: normalizeDateValue },
    { key: 'vm_uti', id: 'vm_uti', defaultValue: 'n' },
    { key: 'dva_uti', id: 'dva_uti', defaultValue: 'n' },
    { key: 'suporte_vent_uti', id: 'suporte_vent_uti', defaultValue: 'n' },
    { key: 'glasgow_uti', id: 'glasgow_uti', defaultValue: '' },
    { key: 'dist_met_uti', id: 'dist_met_uti', defaultValue: 'n' },
    { key: 'score_uti', id: 'score_uti', defaultValue: '' },
    { key: 'saps_uti', id: 'saps_uti', defaultValue: '' },
    { key: 'rel_uti', id: 'rel_uti', defaultValue: '' }
];

function resetAdditionalTables() {
    const selectTuss = document.getElementById('select_tuss');
    const selectNeg = document.getElementById('select_negoc');
    const selectGestao = document.getElementById('select_gestao');
    const selectUti = document.getElementById('select_uti');
    const selectProrrog = document.getElementById('select_prorrog');
    if (selectTuss) {
        selectTuss.value = '';
        selectTuss.dispatchEvent(new Event('change'));
    }
    if (selectNeg) {
        selectNeg.value = '';
        selectNeg.dispatchEvent(new Event('change'));
    }
    if (selectGestao) {
        selectGestao.value = '';
        selectGestao.dispatchEvent(new Event('change'));
    }
    if (selectUti) {
        selectUti.value = '';
        selectUti.dispatchEvent(new Event('change'));
    }
    if (selectProrrog) {
        selectProrrog.value = '';
        selectProrrog.dispatchEvent(new Event('change'));
    }
    resetTussFields();
    resetNegotiationFields();
    resetGestaoFields();
    resetUtiFields();
    resetProrrogFields();
}

function hydrateTussForVisita(visitaId, openPanel) {
    const map = window.VISITA_TUSS_DATA || {};
    const key = visitaId != null ? String(visitaId) : null;
    let entries = key && map[key] ? map[key] : [];
    if ((!entries || !entries.length) && visitaId != null && openPanel) {
        const interId = __VISITA_INTER_MAP[String(visitaId)];
        if (interId && __TUSS_FALLBACK[String(interId)]) {
            entries = __TUSS_FALLBACK[String(interId)];
        }
    }
    const selectTuss = document.getElementById('select_tuss');
    if (!selectTuss) return;
    if (!entries.length) {
        resetTussFields();
        selectTuss.value = '';
        selectTuss.dispatchEvent(new Event('change'));
        return;
    }
    markAdditionalSelect('select_tuss', openPanel);
    applyTussEntries(entries);
}

function hasEntriesForVisita(visitaId, visitMap, fallbackMap) {
    const key = visitaId != null ? String(visitaId) : null;
    if (!key) return false;
    const directData = visitMap && visitMap[key];
    if (Array.isArray(directData)) {
        return directData.length > 0;
    }
    if (directData) {
        return true;
    }
    const interId = __VISITA_INTER_MAP[key];
    const fallbackData = interId && fallbackMap ? fallbackMap[String(interId)] : null;
    if (Array.isArray(fallbackData)) {
        return fallbackData.length > 0;
    }
    return !!fallbackData;
}

function hasDirectEntriesForVisita(visitaId, visitMap) {
    const key = visitaId != null ? String(visitaId) : null;
    if (!key || !visitMap) return false;
    const directData = visitMap[key];
    if (Array.isArray(directData)) {
        return directData.length > 0;
    }
    return !!directData;
}

function bindLazyHydration(selectId, shouldHydrate, hydrator) {
    const select = document.getElementById(selectId);
    if (!select) return;
    select.addEventListener('change', function() {
        if (isHydratingAdditionalSection || this.value !== 's' || !currentEditVisitaId) {
            return;
        }
        if (!shouldHydrate(currentEditVisitaId)) {
            return;
        }
        isHydratingAdditionalSection = true;
        try {
            hydrator(currentEditVisitaId, true);
        } finally {
            isHydratingAdditionalSection = false;
        }
    });
}

function markAdditionalSelect(selectId, openPanel) {
    const select = document.getElementById(selectId);
    if (!select) return;
    if (openPanel && typeof window.fullcareShowAdditionalSection === 'function') {
        select.value = 's';
        window.fullcareShowAdditionalSection(selectId);
        if (typeof window.fullcareRestyleAdditionalLaunchers === 'function') {
            window.fullcareRestyleAdditionalLaunchers();
        }
        return;
    }
    if (typeof window.fullcareSignalAdditionalSection === 'function') {
        window.fullcareSignalAdditionalSection(selectId, 's');
    } else {
        select.value = 's';
    }
    if (typeof window.fullcareRestyleAdditionalLaunchers === 'function') {
        window.fullcareRestyleAdditionalLaunchers();
    }
}

function resetTussFields() {
    if (typeof clearTussInputs === 'function') {
        clearTussInputs();
    }
    const tussJsonField = document.getElementById('tuss-json');
    if (tussJsonField) tussJsonField.value = '';
}

function applyTussEntries(entries) {
    if (!Array.isArray(entries) || !entries.length) return;
    if (typeof clearTussInputs === 'function') clearTussInputs();
    const initial = document.querySelector('.tuss-field-container[data-initial="true"]');
    if (!initial) return;
    entries.forEach((entry, idx) => {
        let target = initial;
        if (idx > 0) {
            if (typeof addTussField === 'function') addTussField();
            const containers = document.querySelectorAll('.tuss-field-container');
            target = containers[containers.length - 1];
        }
        if (!target) return;
        const selectDesc = target.querySelector('[name="tuss_solicitado"]');
        if (selectDesc) {
            selectDesc.value = entry.tuss_solicitado || '';
        }
        const dataInput = target.querySelector('[name="data_realizacao_tuss"]');
        if (dataInput) dataInput.value = (entry.data_realizacao_tuss || '').substring(0, 10);
        const qtdSol = target.querySelector('[name="qtd_tuss_solicitado"]');
        if (qtdSol) qtdSol.value = entry.qtd_tuss_solicitado || '';
        const qtdLib = target.querySelector('[name="qtd_tuss_liberado"]');
        if (qtdLib) qtdLib.value = entry.qtd_tuss_liberado || '';
        const liberado = target.querySelector('[name="tuss_liberado_sn"]');
        if (liberado) liberado.value = entry.tuss_liberado_sn || '';
    });
    if (typeof generateTussJSON === 'function') generateTussJSON();
}

function hydrateNegForVisita(visitaId, openPanel) {
    const map = window.VISITA_NEG_DATA || {};
    const key = visitaId != null ? String(visitaId) : null;
    let entries = key && map[key] ? map[key] : [];
    if ((!entries || !entries.length) && visitaId != null) {
        const interId = __VISITA_INTER_MAP[String(visitaId)];
        if (interId && __NEG_FALLBACK[String(interId)]) {
            entries = __NEG_FALLBACK[String(interId)];
        }
    }
    const selectNeg = document.getElementById('select_negoc');
    if (!selectNeg) return;
    if (!entries.length) {
        resetNegotiationFields();
        selectNeg.value = '';
        selectNeg.dispatchEvent(new Event('change'));
        return;
    }
    markAdditionalSelect('select_negoc', openPanel);
    applyNegotiationEntries(entries);
}

function resetNegotiationFields() {
    const containers = document.querySelectorAll('.negotiation-field-container');
    containers.forEach((container) => {
        if (container.hasAttribute('data-initial')) {
            container.querySelectorAll('input:not([type="hidden"]), select').forEach((el) => {
                el.value = '';
            });
        } else {
            container.remove();
        }
    });
    const jsonField = document.getElementById('negociacoes_json');
    if (jsonField) jsonField.value = '';
}

function applyNegotiationEntries(entries) {
    if (!Array.isArray(entries) || !entries.length) return;
    resetNegotiationFields();
    const base = document.querySelector('.negotiation-field-container[data-initial="true"]');
    if (!base) return;
    entries.forEach((entry, idx) => {
        let target = base;
        if (idx > 0) {
            if (typeof addNegotiationField === 'function') addNegotiationField();
            const containers = document.querySelectorAll('.negotiation-field-container');
            target = containers[containers.length - 1];
        }
        if (!target) return;
        const tipo = target.querySelector('[name="tipo_negociacao"]');
        if (tipo) tipo.value = entry.tipo_negociacao || '';
        const dataIni = target.querySelector('[name="data_inicio_negoc"]');
        if (dataIni) dataIni.value = (entry.data_inicio_negoc || '').substring(0, 10);
        const dataFim = target.querySelector('[name="data_fim_negoc"]');
        if (dataFim) dataFim.value = (entry.data_fim_negoc || '').substring(0, 10);
        if (tipo && typeof setTrocaFromTipo === 'function') {
            setTrocaFromTipo($(target));
        }
        const trocaDe = target.querySelector('[name="troca_de"]');
        if (trocaDe) trocaDe.value = entry.troca_de || '';
        const trocaPara = target.querySelector('[name="troca_para"]');
        if (trocaPara) trocaPara.value = entry.troca_para || '';
        const qtd = target.querySelector('[name="qtd"]');
        if (qtd) qtd.value = entry.qtd || '';
        if (typeof calculateSaving === 'function') calculateSaving($(target));
        const savedSaving = parseFloat(entry.saving || 'NaN');
        const saving = target.querySelector('[name="saving"]');
        const savingShow = target.querySelector('[name="saving_show"]');
        if (!Number.isNaN(savedSaving)) {
            if (saving) saving.value = savedSaving.toFixed(2);
            if (savingShow) {
                savingShow.value = savedSaving >= 0
                    ? `R$ ${savedSaving.toFixed(2)}`
                    : `-R$ ${Math.abs(savedSaving).toFixed(2)}`;
                savingShow.style.color = savedSaving >= 0 ? 'green' : 'red';
            }
        }
    });
    if (typeof generateNegotiationsJSON === 'function') generateNegotiationsJSON();
    if (typeof validarTodasDatas === 'function') validarTodasDatas();
}

function resetGestaoFields() {
    Object.keys(GESTAO_FIELD_DEFAULTS).forEach((fieldId) => {
        const field = document.getElementById(fieldId);
        if (!field) return;
        const defaultValue = GESTAO_FIELD_DEFAULTS[fieldId];
        field.value = defaultValue != null ? defaultValue : '';
        if (field.tagName === 'SELECT') {
            field.dispatchEvent(new Event('change'));
        }
    });
}

function applyGestaoEntry(entry) {
    if (!entry) {
        resetGestaoFields();
        return;
    }
    Object.keys(GESTAO_FIELD_DEFAULTS).forEach((fieldId) => {
        const field = document.getElementById(fieldId);
        if (!field) return;
        let value = entry[fieldId];
        if (value === undefined || value === null || value === '') {
            value = GESTAO_FIELD_DEFAULTS[fieldId] ?? '';
        }
        field.value = value;
        if (field.tagName === 'SELECT') {
            field.dispatchEvent(new Event('change'));
        }
    });
}

function hydrateGestaoForVisita(visitaId, openPanel) {
    const map = window.VISITA_GESTAO_DATA || {};
    const key = visitaId != null ? String(visitaId) : null;
    let entry = key && map[key] ? map[key] : null;
    if (!entry && visitaId != null && openPanel) {
        const interId = __VISITA_INTER_MAP[String(visitaId)];
        if (interId && __GESTAO_FALLBACK[String(interId)]) {
            entry = __GESTAO_FALLBACK[String(interId)];
        }
    }
    const selectGestao = document.getElementById('select_gestao');
    if (!entry) {
        resetGestaoFields();
        if (selectGestao) {
            selectGestao.value = '';
            selectGestao.dispatchEvent(new Event('change'));
        }
        return;
    }
    if (selectGestao) {
        markAdditionalSelect('select_gestao', openPanel);
    }
    applyGestaoEntry(entry);
}

function resetUtiFields() {
    UTI_FIELD_MAP.forEach((fieldInfo) => {
        const field = document.getElementById(fieldInfo.id);
        if (!field) return;
        const defaultValue = fieldInfo.defaultValue != null ? fieldInfo.defaultValue : '';
        field.value = defaultValue;
        if (field.tagName === 'SELECT') {
            field.dispatchEvent(new Event('change'));
        }
    });
    const justifyEl = document.querySelector('textarea[name="justifique_uti"]');
    if (justifyEl) justifyEl.value = '';
}

function applyUtiEntry(entry) {
    if (!entry) {
        resetUtiFields();
        return;
    }
    UTI_FIELD_MAP.forEach((fieldInfo) => {
        const field = document.getElementById(fieldInfo.id);
        if (!field) return;
        let value = entry[fieldInfo.key];
        if (fieldInfo.formatter && value) {
            value = fieldInfo.formatter(value);
        }
        if (value === undefined || value === null || value === '') {
            value = fieldInfo.defaultValue != null ? fieldInfo.defaultValue : '';
        }
        field.value = value;
        if (field.tagName === 'SELECT') {
            field.dispatchEvent(new Event('change'));
        }
    });
    const justifyEl = document.querySelector('textarea[name="justifique_uti"]');
    if (justifyEl && entry.justifique_uti) {
        justifyEl.value = entry.justifique_uti;
    }
}

function hydrateUtiForVisita(visitaId, openPanel) {
    const map = window.VISITA_UTI_DATA || {};
    const key = visitaId != null ? String(visitaId) : null;
    let entry = key && map[key] ? map[key] : null;
    if (!entry && visitaId != null && openPanel) {
        const interId = __VISITA_INTER_MAP[String(visitaId)];
        if (interId && __UTI_FALLBACK[String(interId)]) {
            entry = __UTI_FALLBACK[String(interId)];
        }
    }
    const selectUti = document.getElementById('select_uti');
    if (!entry) {
        resetUtiFields();
        if (selectUti) {
            selectUti.value = '';
            selectUti.dispatchEvent(new Event('change'));
        }
        return;
    }
    if (selectUti) {
        markAdditionalSelect('select_uti', openPanel);
    }
    applyUtiEntry(entry);
}

function normalizeDateValue(value) {
    return value ? String(value).substring(0, 10) : '';
}

function normalizeTimeValue(value) {
    return value ? String(value).substring(0, 5) : '';
}

function resetProrrogFields() {
    if (typeof clearProrrogInputs === 'function') {
        clearProrrogInputs();
    }
    const jsonField = document.getElementById('prorrogacoes-json');
    if (jsonField) jsonField.value = '';
}

function applyProrrogEntries(entries) {
    if (!Array.isArray(entries) || !entries.length) {
        resetProrrogFields();
        return;
    }
    entries = entries.slice().sort((a, b) => {
        const aDate = Date.parse(a.prorrog1_ini_pror || '') || 0;
        const bDate = Date.parse(b.prorrog1_ini_pror || '') || 0;
        if (aDate === bDate) {
            return (parseInt(a.id_prorrogacao || 0, 10) || 0) - (parseInt(b.id_prorrogacao || 0, 10) || 0);
        }
        return aDate - bDate;
    });
    if (typeof clearProrrogInputs === 'function') {
        clearProrrogInputs();
    }
    let base = document.querySelector('#fieldsContainer .field-container');
    if (!base && typeof addField === 'function') {
        addField();
        base = document.querySelector('#fieldsContainer .field-container');
    }
    if (!base) return;
    entries.forEach((entry, idx) => {
        let target = base;
        if (idx > 0 && typeof addField === 'function') {
            addField();
            const containers = document.querySelectorAll('#fieldsContainer .field-container');
            target = containers[containers.length - 1];
        }
        if (!target) return;
        const acomod = target.querySelector('[name="acomod1_pror"]');
        if (acomod) acomod.value = entry.acomod1_pror || '';
        const ini = target.querySelector('[name="prorrog1_ini_pror"]');
        if (ini) ini.value = normalizeDateValue(entry.prorrog1_ini_pror);
        const fim = target.querySelector('[name="prorrog1_fim_pror"]');
        if (fim) fim.value = normalizeDateValue(entry.prorrog1_fim_pror);
        const isol = target.querySelector('[name="isol_1_pror"]');
        if (isol) isol.value = entry.isol_1_pror || 'n';
        const diarias = target.querySelector('[name="diarias_1"]');
        if (diarias) diarias.value = entry.diarias_1 || '';
        if (typeof calculateDiarias === 'function') {
            calculateDiarias(target, { allowHistoricalFutureDates: true });
        }
    });
    if (typeof generateProrJSON === 'function') {
        generateProrJSON();
    }
}

function hydrateProrrogForVisita(visitaId, openPanel) {
    const map = window.VISITA_PRORR_DATA || {};
    const key = visitaId != null ? String(visitaId) : null;
    let entries = key && map[key] ? map[key] : [];
    if ((!entries || !entries.length) && __VISITA_EDIT_ID_REAL && String(visitaId) === String(__VISITA_EDIT_ID_REAL)) {
        entries = __VISITA_PRORR_EDIT_ROWS;
    }
    if ((!entries || !entries.length) && visitaId != null && openPanel) {
        const interId = __VISITA_INTER_MAP[String(visitaId)];
        if (interId && __PRORR_FALLBACK[String(interId)]) {
            entries = __PRORR_FALLBACK[String(interId)];
        }
    }
    const selectProrr = document.getElementById('select_prorrog');
    if (!entries || !entries.length) {
        resetProrrogFields();
        if (selectProrr) {
            selectProrr.value = '';
            selectProrr.dispatchEvent(new Event('change'));
        }
        return;
    }
    if (selectProrr) {
        markAdditionalSelect('select_prorrog', openPanel);
    }
    applyProrrogEntries(entries);
}

function hydrateAdditionalTablesForVisita(visitaId) {
    if (!visitaId) return;
    isHydratingAdditionalSection = true;
    try {
        if (hasDirectEntriesForVisita(visitaId, window.VISITA_TUSS_DATA || {})) {
            hydrateTussForVisita(visitaId);
        }
        if (hasEntriesForVisita(visitaId, window.VISITA_NEG_DATA || {}, __NEG_FALLBACK)) {
            hydrateNegForVisita(visitaId);
        }
        if (hasDirectEntriesForVisita(visitaId, window.VISITA_GESTAO_DATA || {})) {
            hydrateGestaoForVisita(visitaId);
        }
        if (hasDirectEntriesForVisita(visitaId, window.VISITA_UTI_DATA || {})) {
            hydrateUtiForVisita(visitaId);
        }
        if (
            hasDirectEntriesForVisita(visitaId, window.VISITA_PRORR_DATA || {})
            || (__VISITA_EDIT_ID_REAL && String(visitaId) === String(__VISITA_EDIT_ID_REAL) && __VISITA_PRORR_EDIT_ROWS.length > 0)
        ) {
            hydrateProrrogForVisita(visitaId);
        }
    } finally {
        isHydratingAdditionalSection = false;
    }
}

function signalAdditionalSelectsForVisita(visitaId) {
    if (!visitaId) return;
    const realVisitaId = String(visitaId);
    const checks = [
        ['select_tuss', hasDirectEntriesForVisita(realVisitaId, window.VISITA_TUSS_DATA || {})],
        ['select_negoc', hasEntriesForVisita(realVisitaId, window.VISITA_NEG_DATA || {}, __NEG_FALLBACK)],
        ['select_gestao', hasDirectEntriesForVisita(realVisitaId, window.VISITA_GESTAO_DATA || {})],
        ['select_uti', hasDirectEntriesForVisita(realVisitaId, window.VISITA_UTI_DATA || {})],
        [
            'select_prorrog',
            hasDirectEntriesForVisita(realVisitaId, window.VISITA_PRORR_DATA || {})
            || (__VISITA_EDIT_ID_REAL && realVisitaId === String(__VISITA_EDIT_ID_REAL) && __VISITA_PRORR_EDIT_ROWS.length > 0)
        ]
    ];
    checks.forEach(([selectId, hasData]) => {
        if (hasData) {
            markAdditionalSelect(selectId, false);
        }
    });
    if (typeof window.fullcareHideAdditionalSections === 'function') {
        window.fullcareHideAdditionalSections();
    }
}

bindLazyHydration(
    'select_tuss',
    function(visitaId) {
        return hasEntriesForVisita(visitaId, window.VISITA_TUSS_DATA || {}, __TUSS_FALLBACK);
    },
    hydrateTussForVisita
);

bindLazyHydration(
    'select_negoc',
    function(visitaId) {
        return hasEntriesForVisita(visitaId, window.VISITA_NEG_DATA || {}, __NEG_FALLBACK);
    },
    hydrateNegForVisita
);

bindLazyHydration(
    'select_gestao',
    function(visitaId) {
        return hasEntriesForVisita(visitaId, window.VISITA_GESTAO_DATA || {}, __GESTAO_FALLBACK);
    },
    hydrateGestaoForVisita
);

bindLazyHydration(
    'select_uti',
    function(visitaId) {
        return hasEntriesForVisita(visitaId, window.VISITA_UTI_DATA || {}, __UTI_FALLBACK);
    },
    hydrateUtiForVisita
);

bindLazyHydration(
    'select_prorrog',
    function(visitaId) {
        return hasEntriesForVisita(visitaId, window.VISITA_PRORR_DATA || {}, __PRORR_FALLBACK);
    },
    hydrateProrrogForVisita
);

function hydrateCurrentEditVisitAfterSetup() {
    if (!currentEditVisitaId) return;
    hydrateAdditionalTablesForVisita(currentEditVisitaId);
    signalAdditionalSelectsForVisita(currentEditVisitaId);
}

function signalEditAdditionalSelectsFromServer() {
    if (!__VISITA_EDIT_ID_REAL) return;
    const map = {
        tuss: 'select_tuss',
        prorrog: 'select_prorrog',
        gestao: 'select_gestao',
        uti: 'select_uti',
        negoc: 'select_negoc'
    };
    Object.keys(map).forEach((key) => {
        if (__VISITA_EDIT_ADDITIONAL_SELECTS[key] === 's') {
            markAdditionalSelect(map[key], false);
        }
    });
    if (typeof window.fullcareHideAdditionalSections === 'function') {
        window.fullcareHideAdditionalSections();
    }
}

hydrateCurrentEditVisitAfterSetup();
document.addEventListener('DOMContentLoaded', hydrateCurrentEditVisitAfterSetup);
window.addEventListener('load', hydrateCurrentEditVisitAfterSetup);
signalEditAdditionalSelectsFromServer();
document.addEventListener('DOMContentLoaded', signalEditAdditionalSelectsFromServer);
window.addEventListener('load', signalEditAdditionalSelectsFromServer);
window.setTimeout(signalEditAdditionalSelectsFromServer, 0);
</script>

<script src="<?= $BASE_URL ?>js/internacao_cronicos_alert.js"></script>
<script>
window.visitaAiConfig = Object.assign({}, window.visitaAiConfig || {}, {
    baseUrl: __VISITA_CLIENT_CONFIG.baseUrl || ''
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="<?= $BASE_URL ?>js/uti_audit_ai_visita.js?v=<?= filemtime(__DIR__ . '/../js/uti_audit_ai_visita.js') ?>"></script>

<!-- <script src="js/text_cad_internacao.js"></script>
<script src="js/select_internacao.js"></script> -->
