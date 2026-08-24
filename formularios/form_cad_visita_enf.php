<div class="row">
<link rel="stylesheet" href="<?= $BASE_URL ?>css/form_cad_visita_ai.css?v=<?= filemtime(__DIR__ . '/../css/form_cad_visita_ai.css') ?>">
    <?php
    // calculo de dias da internacao e dias da ultima visita
    $hoje = date('Y-m-d');
    $visitaAnt = date("Y-m-d", strtotime($ultimaVis['data_visita_vis']));
    $intern = date("Y-m-d", strtotime($ultimaVis['data_intern_int']));
    $atual = new DateTime($hoje);
    $visAnt = new DateTime($visitaAnt);
    $dataIntern = new DateTime($intern);

    $intervaloUltimaVis = $visAnt->diff($atual);
    $diasIntern = $dataIntern->diff($atual);

    // print_r($id_internacao);
    $visitasDAO = new visitaDAO($conn, $BASE_URL);
    $internacaoDAO = new internacaoDAO($conn, $BASE_URL);
    // $queryVis = new internacaoDAO($conn, $BASE_URL);
    $query2DAO = new visitaDAO($conn, $BASE_URL);
    $id_internacao = filter_input(INPUT_GET, "id_internacao", FILTER_SANITIZE_NUMBER_INT);
    $visitas = $visitasDAO->joinVisitaInternacao($id_internacao);

    ?><h4 class="page-title">Cadastrar visita</h4>
    <p class="page-description">Adicione informações sobre a visita</p>

    <div id="view-contact-container" class="container-fluid fc-visit-summary">
        <hr>
        <span class="fc-visit-summary__label">Internação:</span>
        <span class="fc-visit-summary__value"><?= $internacaoList['0']['id_internacao'] ?></span>
        <span class="card-title bold fc-visit-summary__label">Hospital:</span>
        <span class="card-title bold fc-visit-summary__value"><?= $internacaoList['0']['nome_hosp'] ?></span>
        <span class="fc-visit-summary__label">Paciente:</span>
        <span class="fc-visit-summary__value"><?= $internacaoList['0']['nome_pac'] ?></span>
        <span class="fc-visit-summary__label">Data internação:</span>
        <span class="fc-visit-summary__value"><?= date("d/m/Y", strtotime($internacaoList['0']['data_intern_int'])); ?></span>
        <hr>
    </div>

    <form class="formulario" action="<?= $BASE_URL ?>process_visita.php" id="add-visita-form" method="POST"
        enctype="multipart/form-data">
        <input type="hidden" name="type" value="create">
        <?php
        $contarVis = 0; //contar numero de visitas por internacao 
        $queryVis = $internacaoDAO->selectInternVis($id_internacao);
        foreach ($queryVis as $item) {
            $contarVis++;
        };
        ?>
        <div class="form-group row">
            <div class="form-group col-sm-1">
                <label data-centered-field for="visita_no_vis"> Visita No.</label>
                <input type="text" readonly data-centered-field value="<?= $contarVis + 1 ?>"
                    class="form-control" id="visita_no_vis" name="visita_no_vis">
            </div>
            <div class="form-group col-sm-4">
                <input type="hidden" class="form-control" class="form-control" id="usuario_create"
                    value="<?= $_SESSION['email_user'] ?>" name="usuario_create">
            </div>
            <div class="form-group col-sm-4">
                <input type="hidden" class="form-control" value="<?= $id_internacao ?>" id="fk_internacao_vis"
                    name="fk_internacao_vis" placeholder="">
            </div>

            <div>
                <label for="rel_visita_vis">Relatório Auditoria</label>
                <div class="d-flex justify-content-end flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-clean-text="rel_visita_vis">Limpar formatação</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-ai-improve="rel_visita_vis">Organizar com IA</button>
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
                <textarea type="textarea" rows="2" onclick="aumentarTextAudit()" class="form-control"
                    id="rel_visita_vis" name="rel_visita_vis"></textarea>
                <div class="d-flex justify-content-end mt-1">
                    <small class="text-muted" data-counter-for="rel_visita_vis">0/5000</small>
                </div>
            </div>
            <div class="fc-field-block">
                <label for="acoes_int_vis">Ações Auditoria</label>
                <div class="d-flex justify-content-end flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-clean-text="acoes_int_vis">Limpar formatação</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-ai-improve="acoes_int_vis">Organizar com IA</button>
                </div>
                <textarea type="textarea" rows="2" onclick="aumentarTextAcoes()" class="form-control" id="acoes_int_vis"
                    name="acoes_int_vis" placeholder="Ações de auditoria"></textarea>
                <div class="d-flex justify-content-end mt-1">
                    <small class="text-muted" data-counter-for="acoes_int_vis">0/5000</small>
                </div>
            </div>
            <div class="fc-field-block">
                <label for="programacao_enf">Programação Terapêutica</label>
                <div class="d-flex justify-content-end flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-clean-text="programacao_enf">Limpar formatação</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-ai-improve="programacao_enf">Organizar com IA</button>
                </div>
                <textarea type="textarea" rows="2" class="form-control" id="programacao_enf"
                    name="programacao_enf" placeholder="Programação terapêutica"></textarea>
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
            <hr>
            <!-- ENTRADA DE DADOS AUTOMATICOS NO INPUT-->
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="n" id="internado_uti_int" name="internado_uti_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="n" id="internacao_uti_int" name="internacao_uti_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="n" id="conta_finalizada_int"
                    name="conta_finalizada_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="n" id="conta_paga_int" name="conta_paga_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="s" id="internacao_ativa_int"
                    name="internacao_ativa_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="<?= ($_SESSION['id_usuario']) ?>" id="fk_usuario_vis"
                    name="fk_usuario_vis">
            </div>
            <div class="form-group col-sm-1">

                <input type="hidden" class="form-control" value="<?= ($_SESSION['cargo']) ?>" id="fk_usuario_vis"
                    name="fk_usuario_vis">
            </div>
            <div class="form-group col-sm-2">
                <?php
                $agora = date('d-m-Y');
                $agoraLanc = date('Y-m-d');
                ?>
                <input type="hidden" value=' <?= $agora; ?>' class="form-control" id="data_visita_vis"
                    name="data_visita_vis">
            </div>
            <div class="form-group col-sm-3">
                <label for="data_lancamento_vis">Data do lançamento</label>
                <input type="date" class="form-control" id="data_lancamento_vis"
                    name="data_lancamento_vis" value="<?= $agoraLanc; ?>" readonly tabindex="-1"
                    onfocus="this.blur();" onkeydown="return false;" data-readonly-cursor>
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" id="visita_enf_vis" name="visita_enf_vis"
                    placeholder="<?php if (($_SESSION['cargo']) === 'Enf_auditor') {
                                                                                                                        echo 's';
                                                                                                                    } else {
                                                                                                                        echo 'n';
                                                                                                                    }; ?>"
                    value="<?php if (($_SESSION['cargo']) === 'Enf_auditor') {
                                                                                                                                        echo 's';
                                                                                                                                    } else {
                                                                                                                                        echo 'n';
                                                                                                                                    }; ?>">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" id="visita_med_vis" name="visita_med_vis"
                    placeholder="<?php if (($_SESSION['cargo']) === 'Med_auditor') {
                                                                                                                        echo 's';
                                                                                                                    } else {
                                                                                                                        echo 'n';
                                                                                                                    }; ?>"
                    value="<?php if (($_SESSION['cargo']) == 'Med_auditor') {
                                                                                                                                        echo 's';
                                                                                                                                    } else {
                                                                                                                                        echo 'n';
                                                                                                                                    }; ?>">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" id="visita_auditor_prof_enf" name="visita_auditor_prof_enf"
                    placeholder="<?php if (($_SESSION['cargo']) === 'Enf_auditor') {
                                                                                                                                        echo ($_SESSION['login_user']);
                                                                                                                                    }; ?>"
                    value="<?php if (($_SESSION['cargo']) === 'Enf_auditor') {
                                                                                                                                                        echo ($_SESSION['login_user']);
                                                                                                                                                    }; ?>">
            </div>
            <?php if (($_SESSION['cargo']) === 'Med_auditor') {
            }; ?>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" id="visita_auditor_prof_med" name="visita_auditor_prof_med"
                    placeholder="<?php if (($_SESSION['cargo']) == 'Med_auditor') {
                                                                                                                                        echo ($_SESSION['login_user']);
                                                                                                                                    }; ?>"
                    value="<?php if (($_SESSION['cargo']) === 'Med_auditor') {
                                                                                                                                                        echo ($_SESSION['email_user']);
                                                                                                                                                    }; ?>">
            </div>
            <div class="form-group row">
                <div class="form-group col-sm-2">
                    <label class="control-label" for="select_prorrog">Tuss</label>
                    <select class="form-control" id="select_tuss" name="select_tuss">
                        <option value="">Selecione</option>
                        <option value="s">Sim</option>
                        <option value="n">Não</option>
                    </select>
                </div>
                <div class="form-group col-sm-2">
                    <label class="control-label" for="select_prorrog">Prorrogação</label>
                    <select class="form-control" id="select_prorrog" name="select_prorrog">
                        <option value="">Selecione</option>
                        <option value="s">Sim</option>
                        <option value="n">Não</option>
                    </select>
                </div>
                <div class="form-group col-sm-2">
                    <label class="control-label" for="select_gestao">Gestão Assistencial</label>

                    <select class="form-control" id="select_gestao" name="select_gestao">
                        <option value="">Selecione</option>
                        <option value="s">Sim</option>
                        <option value="n">Não</option>
                    </select>
                </div>
                <div class="form-group col-sm-2">
                    <label class="control-label" for="select_uti">UTI</label>
                    <select class="form-control" id="select_uti" name="select_uti">
                        <option value="">Selecione</option>
                        <option value="s">Sim</option>
                        <option value="n">Não</option>
                    </select>
                </div>
                <div class="form-group col-sm-2">
                    <label class="control-label" for="select_negoc">Negociações</label>
                    <select class="form-control" id="select_negoc" name="select_negoc">
                        <option value="">Selecione</option>
                        <option value="s">Sim</option>
                        <option value="n">Não</option>
                    </select>
                </div>
                <br>
            </div>
            <!-- FORMULARIO DE GESTÃO -->
            <?php include_once('formularios/form_cad_visita_tuss.php'); ?>
            <!-- FORMULARIO DE GESTÃO -->

            <?php include_once('formularios/form_cad_visita_gestao.php'); ?>

            <!-- FORMULARIO DE UTI -->
            <?php include_once('formularios/form_cad_visita_uti.php'); ?>

            <!-- FORMULARIO DE PRORROGACOES -->
            <?php include_once('formularios/form_cad_visita_prorrog.php'); ?>

            <!-- <FORMULARO DE NEGOCIACOES -->
            <?php include_once('formularios/form_cad_visita_negoc.php'); ?>
            <br>

            <div>
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <small id="clinical-autosave-status" class="text-muted">Rascunho automático: ativo</small>
                    <button type="button" class="btn btn-sm btn-outline-secondary fc-clear-draft" data-clear-clinical-draft="fields">Limpar rascunho</button>
                </div>
                <button type="submit" class="btn-sm btn-success btn-int-niveis fc-legacy-submit">Cadastrar</button>
            </div>
    </form>
</div>
</div>
<hr>
<?php if ($contarVis > 0) { ?>
<div class="form-group col-sm-3 fc-history-toggle">
    <label id="textVisita" for="exibirVisita"><i class="bi bi-eye text-success"></i> Visualizar visitas anteriores</label>
    <input type="checkbox" id="exibirVisita" name="exibirVisita" value="exibirVisita">
    <br>
</div>
<hr>
<?php } ?>
<div id="div-visitas" style="display:none">
    <?php

    if (!$visitas) {
        echo ("<br>");
        echo ("<p class='fc-empty-message'> <b>-- Esta internação ainda não possui visita -- </b></p>");
        echo ("<br>");
    } else { ?>
    <h6 class="page-title">Relatórios anteriores</h6>
    <table class="table table-sm table-striped  table-hover table-condensed">
        <thead>
            <tr>
                <th scope="col" class="th-w-2">Visita</th>
                <th scope="col" class="th-w-2">Data visita</th>
                <th scope="col" class="th-w-2">Med</th>
                <th scope="col" class="th-w-2">Enf</th>
                <th scope="col" class="th-w-15">Relatório</th>
                <th scope="col" class="th-w-2">Visualizar</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $hoje = date('Y-m-d');
                $atual = new DateTime($hoje);
                foreach ($visitas as $intern) :
                ?>
            <tr>
                <td scope="row"><?= $intern["id_visita"] ?></td>
                <td scope="row"><?= $intern["data_visita_vis"] ?></td>
                <td scope="row" class="nome-coluna-table"><?php if ($intern["visita_med_vis"] == "s") { ?><span
                        id="boot-icon" class="bi bi-check fc-status-check"></span>
                    <?php }; ?></td>
                <td scope="row" class="nome-coluna-table"><?php if ($internacaoList["visita_enf_vis"] == "s") { ?><span
                        id="boot-icon" class="bi bi-check fc-status-check"></span>
                    <?php }; ?>
                </td>
                <td scope="row"><?= $intern["rel_visita_vis"] ?></td>
                <td><a href="<?= $BASE_URL ?>show_visita.php?id_visita=<?= $intern["id_visita"] ?>"><i
                            class="aparecer-acoes bi bi-eye text-success"></i></a>
                </td>

            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php }; ?>
    <br>
    <hr>
</div>

<script src="<?= $BASE_URL ?>js/text_cad_visita.js?v=<?= filemtime(__DIR__ . '/../js/text_cad_visita.js') ?>"></script>
<script src="<?= $BASE_URL ?>js/internacao_cronicos_alert.js"></script>
<script>
window.clinicalTextToolsConfig = {
    baseUrl: <?= json_encode((string) $BASE_URL, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    draftKey: <?= json_encode('fullcare:visita-enf:' . (string)($id_internacao ?? ($_GET['id_internacao'] ?? 'local')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    fields: ['rel_visita_vis', 'acoes_int_vis', 'programacao_enf'],
    autosaveStatusId: 'clinical-autosave-status'
};
</script>
<script src="<?= $BASE_URL ?>js/clinical_text_tools.js?v=<?= filemtime(__DIR__ . '/../js/clinical_text_tools.js') ?>"></script>
<script>
window.visitaAiConfig = Object.assign({}, window.visitaAiConfig || {}, {
    baseUrl: <?= json_encode((string) $BASE_URL, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="<?= $BASE_URL ?>js/uti_audit_ai_visita.js?v=<?= filemtime(__DIR__ . '/../js/uti_audit_ai_visita.js') ?>"></script>
<script src="<?= $BASE_URL ?>js/select_visita.js?v=<?= filemtime(__DIR__ . '/../js/select_visita.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous">
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
