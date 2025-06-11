<?php $agora = date('Y-m-d'); ?>

<div class="row">
    <div class="form-group row">
        <h4 class="page-title">Cadastrar internação</h4>
        <hr>
    </div>

    <!-- Barra de progresso -->
    <div class="progress">
        <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 20%" aria-valuenow="20"
            aria-valuemin="0" aria-valuemax="100">Etapa 1 de 5</div>
    </div>
    <br>

    <!-- Etapa 1 -->
    <div id="step-1" class="step">
        <h3>Passo 1: Informações Básicas</h3>
        <hr>
        <div class="form-group row">
            <div class="form-group col-sm-6">
                <label for="hospital_selected">Hospital</label>
                <select onchange="myFunctionSelected()" class="form-control" id="hospital_selected"
                    name="hospital_selected" required>
                    <option value="<?= $int_hospital->id_hospital ?>">
                        <?= $int_hospital->nome_hosp ?? "Selecione o Hospital" ?>
                        <?php foreach ($listHopitaisPerfil as $hospital): ?>
                        <option value="<?= $hospital["id_hospital"] ?>"
                            <?= $int_hospital->nome_hosp == $hospital["id_hospital"] ? 'selected' : '' ?>>
                            <?= $hospital["nome_hosp"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label for="fk_paciente_int">Paciente</label>
                <select class="form-control selectpicker show-tick" data-size="5" data-live-search="true"
                    id="fk_paciente_int" name="fk_paciente_int" required>
                    <option value=""><?= $int_paciente['nome_pac'] ?? "Selecione o Hospital" ?></option>
                    <?php
                    usort($pacientes, function ($a, $b) {
                        return strcmp($a["nome_pac"], $b["nome_pac"]);
                    });
                    foreach ($pacientes as $paciente): ?>
                        <option value="<?= $paciente["id_paciente"] ?>"
                            <?= $intern["fk_paciente_int"] == $paciente["id_paciente"] ? 'selected' : '' ?>>
                            <?= $paciente["nome_pac"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div id="alert_intern" style="font-size: 0.6em; margin-left: 7px; color: red; display:none">Paciente já
                    internado</div>
            </div>
        </div>
        <div class="form-group row">
            <div class="form-group col-sm-6">
                <label for="data_intern_int">Data Internação</label>
                <input type="date" class="form-control " id="data_intern_int" required name="data_intern_int"
                    value="<?= $intern["data_intern_int"] ?>">
            </div>
            <div class="form-group col-sm-6">
                <label for="data_visita_int">Data Visita</label>
                <input type="date" class="form-control " id="data_visita_int" value="<?= $intern["data_visita_int"] ?>"
                    name="data_visita_int" readonly>
            </div>
        </div>

        <div class="form-group row">
            <div class="form-group col-sm-6">
                <label for="internado_int">Internado</label>
                <select class="form-control" id="internado_int" name="internado_int">
                    <option value="s" <?= $intern["internado_int"] == "s" ? 'selected' : '' ?>>Sim</option>
                    <option value="n" <?= $intern["internado_int"] == "n" ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label for="usuario_create_int">Usuário</label>
                <input type="text" class="form-control " id="usuario_create_int" value="<?= $_SESSION['email_user'] ?>"
                    name="usuario_create_int" readonly>
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
        <h3>Passo 2: Informações Básicas</h3>
        <hr>
        <div class="form-group row">
            <div class="form-group col-sm-6">
                <label for="acomodacao_int">Acomodação</label>
                <select class="form-control" id="acomodacao_int" name="acomodacao_int">
                    <option value="">Selecione</option>
                    <?php
                    sort($dados_acomodacao, SORT_ASC);
                    foreach ($dados_acomodacao as $acomd) { ?>
                        <option value="<?= $acomd; ?>" <?= $intern["acomodacao_int"] == $acomd ? 'selected' : '' ?>>
                            <?= $acomd; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label for="especialidade_int">Especialidade</label>
                <select class="form-control" id="especialidade_int" name="especialidade_int">
                    <option value="">Selecione</option>
                    <?php
                    sort($dados_especialidade, SORT_ASC);
                    foreach ($dados_especialidade as $especial) { ?>
                        <option value="<?= $especial; ?>" <?= $intern["especialidade_int"] == $especial ? 'selected' : '' ?>>
                            <?= $especial; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="form-group col-sm-6">
                <label for="titular_int">Médico</label>
                <input type="text" class="form-control " id="titular_int" name="titular_int"
                    value="<?= $intern["titular_int"] ?>">
            </div>
            <div class="form-group col-sm-6">
                <label for="crm_int">CRM</label>
                <input type="text" class="form-control " id="crm_int" name="crm_int" value="<?= $intern["crm_int"] ?>">
            </div>
        </div>

        <div class="form-group row">
            <div class="form-group col-sm-6">
                <label for="modo_internacao_int">Modo Admissão</label>
                <select class=" form-control" id="modo_internacao_int" name="modo_internacao_int">
                    <option value="">Selecione</option>
                    <option value="Clínica" <?= $intern["modo_internacao_int"] == "Clínica" ? 'selected' : '' ?>>Clínica
                    </option>
                    <option value="Pediatria" <?= $intern["modo_internacao_int"] == "Pediatria" ? 'selected' : '' ?>>
                        Pediatria</option>
                    <option value="Ortopedia" <?= $intern["modo_internacao_int"] == "Ortopedia" ? 'selected' : '' ?>>
                        Ortopedia</option>
                    <option value="Obstetrícia" <?= $intern["modo_internacao_int"] == "Obstetrícia" ? 'selected' : '' ?>>
                        Obstetrícia</option>
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label for="tipo_admissao_int">Tipo Internação</label>
                <select class="form-control" id="tipo_admissao_int" name="tipo_admissao_int">
                    <option value="">Selecione</option>
                    <option value="Eletiva" <?= $intern["tipo_admissao_int"] == "Eletiva" ? 'selected' : '' ?>>Eletiva
                    </option>
                    <option value="Urgência" <?= $intern["tipo_admissao_int"] == "Urgência" ? 'selected' : '' ?>>Urgência
                    </option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="form-group col-sm-6">
                <label for="fk_patologia_int">Patologia</label>
                <select class="form-control selectpicker show-tick" data-size="5" data-live-search="true"
                    id="fk_patologia_int" name="fk_patologia_int">
                    <option value="">Selecione</option>
                    <?php
                    usort($patologias, function ($a, $b) {
                        return strcmp($a["patologia_pat"], $b["patologia_pat"]);
                    });
                    foreach ($patologias as $patologia): ?>
                        <option value="<?= $patologia["id_patologia"] ?>"
                            <?= $intern["fk_patologia_int"] == $patologia["id_patologia"] ? 'selected' : '' ?>>
                            <?= $patologia["patologia_pat"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label for="grupo_patologia_int">Grupo Patologia</label>
                <select class="form-control" id="grupo_patologia_int" name="grupo_patologia_int">
                    <option value="">Selecione</option>
                    <?php foreach ($dados_grupo_pat as $grupo): ?>
                        <option value="<?= $grupo ?>" <?= $intern["grupo_patologia_int"] == $grupo ? 'selected' : '' ?>>
                            <?= $grupo ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="form-group col-sm-6">
                <label for="origem_int">Origem</label>
                <select class="form-control" id="origem_int" name="origem_int">
                    <option value="">Selecione</option>
                    <?php foreach ($origem as $origens): ?>
                        <option value="<?= $origens ?>" <?= $intern["origem_int"] == $origens ? 'selected' : '' ?>>
                            <?= $origens ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label for="senha_int">Senha</label>
                <input type="text" class="form-control " id="senha_int" name="senha_int"
                    value="<?= $intern["senha_int"] ?>">
            </div>
        </div>

        <!-- Botões de navegação -->
        <div class="form-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep()">Voltar</button>
            <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
        </div>
    </div>

    <!-- Etapa 3 -->
    <div id="step-3" class="step" style="display: none;">
        <h3>Passo 3: Relatorios</h3>
        <hr>
        <div class="form-group row">
            <div class="form-group col-sm-12">
                <label for="rel_int">Relatório de Auditoria</label>
                <textarea type="textarea" style="resize:none" rows="2" onclick="aumentarTextAudit()"
                    class="form-control" id="rel_int" name="rel_int"><?php echo $intern['rel_int'] ?></textarea>
            </div>
        </div>
        <div class="form-group row">
            <div class="form-group col-sm-12">
                <label for="acoes_int">Ações da Auditoria</label>
                <textarea rows="2" style="resize:none" onclick="aumentarTextAcoes()" type="textarea"
                    class="form-control" id="acoes_int" name="acoes_int"><?php echo $intern['acoes_int'] ?></textarea>
            </div>
        </div>
        <div class="form-group row">
            <div class="form-group col-sm-12">
                <label for="programacao_int">Programação Terapêutica</label>
                <textarea type="textarea" style="resize:none" rows="2" onclick="aumentarTextProgInt()"
                    class="form-control" id="programacao_int"
                    name="programacao_int"><?php echo $intern['programacao_int'] ?></textarea>
            </div>
        </div>

        <!-- Botões de navegação -->
        <div class="form-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep()">Voltar</button>
            <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
        </div>
    </div>

    <!-- Etapa 4 -->
    <div id="step-4" class="step" style="display: none;">
        <h3>Passo 4: Cadastro TUSS</h3>
        <hr>
        <?php include_once('form_cad_internacao_tuss.php'); ?>

        <!-- Botões de navegação -->
        <div class="form-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep()">Voltar</button>
            <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
        </div>
    </div>

    <!-- Etapa 5 -->
    <div id="step-5" class="step" style="display: none;">
        <h3>Passo 5: Cadastro Gestao</h3>
        <hr>
        <?php include_once('form_edit_internacao_gestao.php'); ?>

        <!-- Botões de navegação -->
        <div class="form-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep()">Voltar</button>
            <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
        </div>
    </div>

    <!-- Etapa 6 -->
    <div id="step-6" class="step" style="display: none;">
        <h3>Passo 6: Cadastro UTI</h3>
        <hr>
        <?php include_once('form_edit_internacao_uti.php'); ?>

        <!-- Botões de navegação -->
        <div class="form-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep()">Voltar</button>
            <button type="button" class="btn btn-primary" onclick="nextStep()">Próxima Etapa</button>
        </div>
    </div>

    <!-- Etapa 7 -->
    <div id="step-7" class="step" style="display: none;">
        <h3>Passo 7: Cadastro Prorrogação</h3>
        <hr>
        <?php include_once('form_edit_internacao_prorrog.php'); ?>

        <!-- Botões de navegação -->
        <div class="form-group">
            <button type="button" class="btn btn-secondary" onclick="prevStep()">Voltar</button>
            <button type="submit" class="btn btn-success">Finalizar Cadastro</button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.selectpicker').selectpicker();
        $('.selectpicker').selectpicker('refresh');
        $('.selectpicker').on('loaded.bs.select', function () {
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
        const totalSteps = 7;
        const progressPercentage = (currentStep / totalSteps) * 100;
        document.getElementById('progress-bar').style.width = progressPercentage + '%';
        document.getElementById('progress-bar').innerText = 'Etapa ' + currentStep + ' de ' + totalSteps;
    }
</script>



<script src="js/scriptDataInt.js"></script>
<script src="js/text_cad_internacao.js"></script>
<script src="js/select_internacao.js"></script>

<script>

    // var btnSelected = document.querySelector("#hospital_selected");
    function myFunctionSelected() {
        var btnSelected = document.querySelector("#hospital_selected").value;
        var btnSelectedCx = document.querySelector("#hospital_selected");
        var textoSelecao = document.querySelector("#texto-selecao");
        const btnHospital = document.querySelector("#fk_hospital_int");
        btnHospital.value = btnSelected;
        $("#hospital_selected").css({
            "color": "black",
            "font-weight": "bold",
            "border": "2px",
            "border-color": "green",
            "border-style": "solid"

        });
        textoSelecao.textContent = "Você está lançando dados da Internação no Hospital";
    }
</script>
<script>

    // aparecer campo atb em uso
    $(document).ready(function () {
        $('#atb').hide(); // Oculta o campo de texto quando a página carrega

        $('#atb_det').change(function () {
            if ($(this).val() === 's') {
                $('#atb').show();
            } else {
                $('#atb').hide();
            }
        });
    });

    // aparecer campo litros de O2
    $(document).ready(function () {
        $('#div-oxig').hide(); // Oculta o campo de texto quando a página carrega

        $('#oxig_det').change(function () {
            console.log(document.querySelector('#titular_int'))
            if ($(this).val() === 'Cateter' || $(this).val() == 'Mascara') {
                $('#div-oxig').show();
            } else {
                $('#div-oxig').hide();
            }
        });
    });
</script>
<!-- formulario ajax para envio form sem refresh -->
<script>
    // let capeante_adicional = 0;
    novoValorInternacao = <?= $intern['id_internacao'] ?>;


    $("#myForm").submit(function (event) {

        event.preventDefault(); //prevent default action 
        let post_url = $(this).attr("action"); //get form action url
        let request_method = $(this).attr("method"); //get form GET/POST method
        let form_data = $(this).serialize(); //Encode form elements for submission	
        const fk_int_capeante_js = document.querySelector("#fk_int_capeante");
        $.ajax({
            url: post_url,
            type: request_method,
            data: form_data,
            success: function (result) {
                console.log(result)
                $('form').trigger("reset");
                var btnSelected = document.querySelector("#hospital_selected").value;
                var btnSelectedCx = document.querySelector("#hospital_selected");
                var textoSelecao = document.querySelector("#texto-selecao");
                const btnHospital = document.querySelector("#fk_hospital_int");

                var valorInternacao = parseInt($("#id_internacao").val());
                var valorInternacaoTuss = parseInt($("#fk_int_tuss").val());
                var valorInternacaoUTI = parseInt($("#fk_internacao_uti").val());
                var valorInternacaoNegoc = parseInt($("#fk_id_int").val());
                var valorInternacaoPror = parseInt($("#fk_internacao_pror").val());
                var valorInternacaoGes = parseInt($("#fk_internacao_ges").val());

                // Soma o novoValor ao valor do input
                var novoValorInternacao = valorInternacao;

                // Atualiza o valor do input com o novo valor
                $("#id_internacao").val(novoValorInternacao);
                $("#fk_int_tuss").val(novoValorInternacao);
                $("#fk_internacao_uti").val(novoValorInternacao);
                $("#fk_id_int").val(novoValorInternacao);
                $("#fk_internacao_pror").val(novoValorInternacao);
                $("#fk_internacao_ges").val(novoValorInternacao);

                btnHospital.value = btnSelected;
                $("#hospital_selected").css({
                    "color": "black",
                    "font-weight": "bold",
                    "border": "2px",
                    "border-color": "green",
                    "border-style": "solid"

                });
                textoSelecao.textContent = "Você está lançando dados da Internação no Hospital";
                var textoAtual = $("#proximoId_int").text();
                var novoValor = parseInt(textoAtual);

                $("#proximoId_int").text(novoValor);
                $("#RegInt").val(novoValorInternacao);

                var valorInternacao = parseInt($("#id_internacao").val());
                var valorInternacaoTuss = parseInt($("#fk_int_tuss").val());
                var valorInternacaoUTI = parseInt($("#fk_internacao_uti").val());
                var valorInternacaoNegoc = parseInt($("#fk_id_int").val());
                var valorInternacaoPror = parseInt($("#fk_internacao_pror").val());
                var valorInternacaoGes = parseInt($("#fk_internacao_ges").val());

                // Atualiza o valor do input com o novo valor
                $("#id_internacao").val(novoValorInternacao);
                $("#fk_int_tuss").val(novoValorInternacao);
                $("#fk_internacao_uti").val(novoValorInternacao);
                $("#fk_id_int").val(novoValorInternacao);
                $("#fk_internacao_pror").val(novoValorInternacao);
                $("#fk_internacao_ges").val(novoValorInternacao);
                var btnSelected = document.querySelector("#hospital_selected").value;


                var divGes = document.querySelector("#container-gestao");
                var divTuss = document.querySelector("#container-tuss");
                var divPro = document.querySelector("#container-prorrog");
                var divUti = document.querySelector("#container-uti");
                var divNeg = document.querySelector("#container-negoc");

                divUti.style.display = "none";
                divTuss.style.display = "none";
                divPro.style.display = "none";
                divGes.style.display = "none";
                divNeg.style.display = "none";

                $("#select_tuss").val("");
                $("#select_tuss").css({
                    "color": "gray",
                    "font-weight": "normal",
                    "border": "1px",
                    "border-color": "gray",

                });

                var select_gestao = document.getElementById("#select_gestao");
                $("#select_gestao").val("");
                $("#select_gestao").css({
                    "color": "gray",
                    "font-weight": "normal",
                    "border": "1px",
                    "border-color": "gray",

                });

                var select_detalhes = document.getElementById("#select_detalhes");
                $("#select_detalhes").val("");
                $("#select_detalhes").css({
                    "color": "gray",
                    "font-weight": "normal",
                    "border": "1px",
                    "border-color": "gray",

                });
                var select_prorrog = document.getElementById("#select_prorrog");
                $("#select_prorrog").val("");
                $("#select_prorrog").css({
                    "color": "gray",
                    "font-weight": "normal",
                    "border": "1px",
                    "border-color": "gray",

                });

                var select_uti = document.getElementById("#select_uti");
                $("#select_uti").val("");
                $("#select_uti").css({
                    "color": "gray",
                    "font-weight": "normal",
                    "border": "1px",
                    "border-color": "gray",

                });

                var select_negoc = document.getElementById("#select_negoc");
                $("#select_negoc").val("");
                $("#select_negoc").css({
                    "color": "gray",
                    "font-weight": "normal",
                    "border": "1px",
                    "border-color": "gray",

                });

                $('#alert').addClass("alert-success");
                $('#alert').fadeIn().html("Editado com sucesso");
                setTimeout(function () {
                    $('#alert').fadeOut('Slow');
                }, 2000);

            }
        });
    });
</script>