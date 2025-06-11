<?php $agora = date('Y-m-d'); ?>
<!-- Main CSS-->

<div class="row">
    <div class="form-group row">
        <h4 class="page-title">Atualização Evento Adverso</h4>
        <hr>
    </div>

    <?php $hospital = $hospital_geral->findById($intern["fk_hospital_int"]);
    $paciente = $pacienteDao->findByIdSeg($intern["fk_paciente_int"]);
    $int_gestao = $gestao->findByIdInt($intern['id_internacao']);
    $type = 'create';
    if ($int_gestao->id_gestao != null) {
        $type = 'update';
    }
    ?>

    <form id="myForm" name="myForm" action="process_evento_adverso.php" method="POST">

        <!-- Barra de progresso -->
        <div class="progress">
            <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 50%" aria-valuenow="50"
                aria-valuemin="0" aria-valuemax="100">Etapa 1 de 2</div>
        </div>
        <br>
        <!-- Etapa 1 -->
        <div id="step-1" class="step">
            <h3>Passo 1: Informações Básicas</h3>

            <hr>

            <div class="form-group row">
                <div class="form-group col-sm-6">
                    <label for="fk_hospital_int_hosp">Hospital</label>
                    <input type="text" readonly class="form-control" name="fk_hospital_int_hosp"
                        value="<?= $hospital->nome_hosp ?>">
                    <input type="hidden" name="fk_paciente_int" value="<?= $intern['fk_hospital_int'] ?>">
                    <input type="text" name="id_gestao" value="<?= $int_gestao->id_gestao ?>">
                    <input type="hidden" name="type" id="type" value="<?= $type ?>">
                    <input type="hidden" name="fk_user_ges" id="fk_user_ges" value="<?= $_SESSION['id_usuario'] ?>">
                    <input type="text" type="text" name="fk_internacao_ges"
                        value="<?= $int_gestao->fk_internacao_ges ?>">

                </div>

                <div class="form-group col-sm-6">
                    <label for="fk_paciente_int_nome">Paciente</label>
                    <input type="text" readonly class="form-control" name="fk_paciente_int_nome"
                        value="<?= $paciente->nome_pac ?>">
                    <!-- Campo oculto para enviar o valor fk_intern_pac -->
                    <input type="hidden" name="fk_paciente_int" value="<?= $intern['fk_paciente_int'] ?>">
                </div>
            </div>
            <div class="form-group row">
                <div class="form-group col-sm-6">
                    <label for="data_intern_int">Data Internação</label>
                    <input type="date" readonly class="form-control " id="data_intern_int" required
                        name="data_intern_int" value="<?= $intern["data_intern_int"] ?>">
                </div>
                <div class="form-group col-sm-6">
                    <label for="data_visita_int">Data Visita</label>
                    <input type="date" class="form-control " id="data_visita_int"
                        value="<?= $intern["data_visita_int"] ?>" name="data_visita_int" readonly>
                </div>
            </div>

            <div class="form-group row">
                <input type="hidden" class="form-control " value="<?= $intern["internado_int"] ?>" name="internado_int"
                    readonly>

                <div class="form-group col-sm-12">
                    <label for="rel_int">Relatório de Auditoria</label>
                    <textarea type="textarea" style="resize:none" rows="5" class="form-control" id="rel_int"
                        name="rel_int"><?php echo $intern['rel_int'] ?></textarea>
                </div>
            </div>

            <!-- Botões de navegação -->
            <div class="form-group">
                <button type="button" class="btn btn-secondary" onclick="prevStep()" disabled>Voltar</button>
                <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
            </div>
        </div>

        <!-- Etapa 2 -->
        <div id="step-2" class="step" style="display: none;">
            <h3>Passo 2: Evento Adverso</h3>
            <hr>
            <div id="container-gestao">
                <div class="form-group row" style="display:none">

                    <div class="form-group col-sm-1">
                        <input type="text" readonly class="form-control" id="fk_internacao_ges" name="fk_internacao_ges"
                            value="<?= $intern['id_internacao'] ?>">
                    </div>
                </div>
                <div class="form-group row">

                    <div class="form-group col-sm-2">
                        <label for="evento_adverso_ges">Evento Adverso</label>
                        <select class="form-control-sm form-control" id="evento_adverso_ges" name="evento_adverso_ges">
                            <option value="n" <?= ($int_gestao->evento_adverso_ges === 'n') ? 'selected' : '' ?>>Não
                            </option>
                            <option value="s" <?= ($int_gestao->evento_adverso_ges === 's') ? 'selected' : '' ?>>Sim
                            </option>
                        </select>
                    </div>
                    <!-- DIV evento adverso -->
                    <div id="div_evento" class="form-group col-sm-10"
                        style="display: <?= ($int_gestao->evento_adverso_ges === 's') ? 'block' : 'none' ?>">
                        <div>
                            <label for="tipo_evento_adverso_gest">Tipo Evento Adverso</label>
                            <select class="form-control-sm form-control" id="tipo_evento_adverso_gest"
                                name="tipo_evento_adverso_gest">
                                <?php
                                sort($dados_tipo_evento, SORT_ASC);
                                foreach ($dados_tipo_evento as $evento) {
                                    // Check if the current option matches the value of $int_gestao->tipo_evento_adverso_gest
                                    $selected = ($evento == $int_gestao->tipo_evento_adverso_gest) ? 'selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars($evento); ?>" <?= $selected; ?>>
                                    <?= htmlspecialchars($evento); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div id="div_rel_evento">
                            <label for="rel_evento_adverso_ges">Relatório Evento Adverso</label>
                            <textarea type="textarea" style="resize:none" rows="5" class="form-control"
                                id="rel_evento_adverso_ges"
                                name="rel_evento_adverso_ges"><?php echo $int_gestao->rel_evento_adverso_ges ?></textarea>
                        </div>
                        <div class="form-group row">
                            <div class="form-group col-sm-2">
                                <label for="evento_sinalizado_ges">Evento sinalizado</label>
                                <select class="form-control-sm form-control" id="evento_sinalizado_ges"
                                    name="evento_sinalizado_ges">
                                    <option value="n"
                                        <?= ($int_gestao->evento_sinalizado_ges === 'n') ? 'selected' : '' ?>>Não
                                    </option>
                                    <option value="s"
                                        <?= ($int_gestao->evento_sinalizado_ges === 's') ? 'selected' : '' ?>>Sim
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-sm-2">
                                <label for="evento_discutido_ges">Evento discutido</label>
                                <select class="form-control-sm form-control" id="evento_discutido_ges"
                                    name="evento_discutido_ges">
                                    <option value="n"
                                        <?= ($int_gestao->evento_discutido_ges === 'n') ? 'selected' : '' ?>>Não
                                    </option>
                                    <option value="s"
                                        <?= ($int_gestao->evento_discutido_ges === 's') ? 'selected' : '' ?>>Sim
                                    </option>
                                </select>
                            </div>

                            <div class="form-group col-sm-2">
                                <label for="evento_negociado_ges">Evento negociado</label>
                                <select class="form-control-sm form-control" id="evento_negociado_ges"
                                    name="evento_negociado_ges">
                                    <option value="n"
                                        <?= ($int_gestao->evento_negociado_ges === 'n') ? 'selected' : '' ?>>Não
                                    </option>
                                    <option value="s"
                                        <?= ($int_gestao->evento_negociado_ges === 's') ? 'selected' : '' ?>>Sim
                                    </option>
                                </select>
                            </div>

                            <div class="form-group col-sm-2">
                                <label for="evento_valor_negoc_ges">Valor negociado</label>
                                <input type="text" class="form-control form-control-sm" id="evento_valor_negoc_ges"
                                    value='' name="evento_valor_negoc_ges">
                            </div>
                            <div class="form-group col-sm-2">
                                <label for="evento_prorrogar_ges">Seguir Prorrogação</label>
                                <select class="form-control-sm form-control" id="evento_prorrogar_ges"
                                    name="evento_prorrogar_ges">
                                    <option value="n"
                                        <?= ($int_gestao->evento_prorrogar_ges === 'n') ? 'selected' : '' ?>>Não
                                    </option>
                                    <option value="s"
                                        <?= ($int_gestao->evento_prorrogar_ges === 's') ? 'selected' : '' ?>>Sim
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-sm-2">
                                <label for="evento_fech_ges">Fechar conta</label>
                                <select class="form-control-sm form-control" id="evento_fech_ges"
                                    name="evento_fech_ges">
                                    <option value="n" <?= ($int_gestao->evento_fech_ges === 'n') ? 'selected' : '' ?>>
                                        Não
                                    </option>
                                    <option value="s" <?= ($int_gestao->evento_fech_ges === 's') ? 'selected' : '' ?>>
                                        Sim
                                    </option>
                                </select>
                            </div>

                        </div>
                        <div class="form-group row">
                            <div class="form-group col-sm-2">
                                <label for="evento_retorno_qual_hosp_ges">Retorno Qualidade Hospital?</label>
                                <select class="form-control-sm form-control" id="evento_retorno_qual_hosp_ges"
                                    name="evento_retorno_qual_hosp_ges">
                                    <option value="n"
                                        <?= ($int_gestao->evento_retorno_qual_hosp_ges === 'n') ? 'selected' : '' ?>>
                                        Não
                                    </option>
                                    <option value="s"
                                        <?= ($int_gestao->evento_retorno_qual_hosp_ges === 's') ? 'selected' : '' ?>>
                                        Sim
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-sm-2">
                                <label for="evento_encerrar_ges">Encerrar Evento?</label>
                                <select class="form-control-sm form-control" id="evento_encerrar_ges"
                                    name="evento_encerrar_ges">
                                    <option value="n"
                                        <?= ($int_gestao->evento_encerrar_ges === 'n') ? 'selected' : '' ?>>
                                        Não
                                    </option>
                                    <option value="s"
                                        <?= ($int_gestao->evento_encerrar_ges === 's') ? 'selected' : '' ?>>
                                        Sim
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-sm-2">
                                <label for="evento_impacto_financ_ges">Causou impacto financeiro?</label>
                                <select class="form-control-sm form-control" id="evento_impacto_financ_ges"
                                    name="evento_impacto_financ_ges">
                                    <option value="n"
                                        <?= ($int_gestao->evento_impacto_financ_ges === 'n') ? 'selected' : '' ?>>
                                        Não
                                    </option>
                                    <option value="s"
                                        <?= ($int_gestao->evento_impacto_financ_ges === 's') ? 'selected' : '' ?>>
                                        Sim
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-sm-2">
                                <label for="evento_prolongou_internacao_ges">Prolongou internação?</label>
                                <select class="form-control-sm form-control" id="evento_prolongou_internacao_ges"
                                    name="evento_prolongou_internacao_ges">
                                    <option value="n"
                                        <?= ($int_gestao->evento_prolongou_internacao_ges === 'n') ? 'selected' : '' ?>>
                                        Não
                                    </option>
                                    <option value="s"
                                        <?= ($int_gestao->evento_prolongou_internacao_ges === 's') ? 'selected' : '' ?>>
                                        Sim
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-sm-2">
                                <label for="evento_classificacao_ges">Como você classifica?</label>
                                <select class="form-control-sm form-control" id="evento_classificacao_ges"
                                    name="evento_classificacao_ges">
                                    <option value="leve"
                                        <?= ($int_gestao->evento_classificacao_ges === 'leve') ? 'selected' : '' ?>>
                                        Leve
                                    </option>
                                    <option value="moderada"
                                        <?= ($int_gestao->evento_classificacao_ges === 'moderada') ? 'selected' : '' ?>>
                                        Moderada
                                    </option>
                                    <option value="grave"
                                        <?= ($int_gestao->evento_classificacao_ges === 'grave') ? 'selected' : '' ?>>
                                        Grave
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </form>
    <script type="text/javascript">
    // JS PARA APARECER REL EVENTO ADVERSO
    var select_evento = document.querySelector('#evento_adverso_ges');
    var input_type_ges = document.querySelector('#typeGes');
    select_evento.addEventListener('change', setEvento);

    function setEvento() {
        var choice_evento = select_evento.value;
        var div_evento = document.getElementById("div_evento")
        var input_type_ges = document.querySelector('#typeGes');

        if (choice_evento === 's') {
            if (div_evento.style.display === "none") {
                div_evento.style.display = "block";
                input_type_ges.value = "update"; // Alterar para 'update' quando for 's'

            }

        }
        if (choice_evento === 'n') {

            if (div_evento.style.display === "block") {
                div_evento.style.display = "none";
                input_type_ges.value = "create"; // Alterar para 'create' quando for 'n'

            }
        }
    }
    </script>


    <div class="form-group">
        <button type="button" class="btn btn-secondary" onclick="prevStep()">Voltar</button>
        <button type="submit" class="btn btn-success">Finalizar Cadastro</button>
    </div>
</div>

</div>

<script>
$(document).ready(function() {
    $('.selectpicker').selectpicker();
    $('.selectpicker').selectpicker('refresh');
    $('.selectpicker').on('loaded.bs.select', function() {
        $('.bs-searchbox input').attr('placeholder', 'Digite para pesquisar...');
    });
});
let currentStep = 1;

function nextStep() {
    document.getElementById('step-' + currentStep).style.display = 'none';
    currentStep++;
    document.getElementById('step-' + currentStep).style.display = 'block';
    updateProgressBar();
}

function prevStep() {
    if (currentStep > 1) {
        document.getElementById('step-' + currentStep).style.display = 'none';
        currentStep--;
        document.getElementById('step-' + currentStep).style.display = 'block';
        updateProgressBar();
    }
}

function updateProgressBar() {
    const totalSteps = 2;
    const progressPercentage = (currentStep / totalSteps) * 100;
    document.getElementById('progress-bar').style.width = progressPercentage + '%';
    document.getElementById('progress-bar').innerText = 'Etapa ' + currentStep + ' de ' + totalSteps;
}
</script>

<script>
// $(document).ready(function() {
//     $("#myForm").submit(function(event) {
//         let typeGes = $("#typeGes").val();
//         let post_url = $(this).attr("action");
//         let request_method = $(this).attr("method");
//         let form_data = $(this).serialize();

//         console.log("Enviando formulário diretamente para:", post_url);
//         console.log("Dados do formulário:", form_data);

//         // Não usar AJAX e deixar o formulário ser enviado normalmente
//         if (typeGes === "create") {
//             alert("Enviando criação de nova gestão.");
//         } else if (typeGes === "update") {
//             alert("Enviando atualização de gestão.");
//         }

//         // Permitir o envio normal do formulário
//         return true;
//     });
// });
</script>