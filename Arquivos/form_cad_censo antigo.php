<div class="row">

    <h2 class="page-title">Cadastrar Censo</h2>
    <p class="page-description">Adicione informações sobre a internação</p>

    <form class="formulario visible" action="<?= $BASE_URL ?>process_censo.php" id="add-internacao-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="type" value="create-censo">

        <div class="form-group row">
            <div class="form-group col-sm-3">
                <label class="control-label col-sm-3 " for="fk_hospital_int">Hospital</label>
                <select class="form-control" id="fk_hospital_int" name="fk_hospital_int" required>
                    <option autofocus value="<?= $hospital["nome_hosp"] ?>">Selecione o Hospital</option>
                    <?php foreach ($hospitals as $hospital) : ?>
                        <option value="<?= $hospital["id_hospital"] ?>"><?= $hospital["nome_hosp"] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label class="control-label" for="fk_paciente_int">Paciente</label>
                <select class="form-control" id="fk_paciente_int" name="fk_paciente_int" required>
                    <option value="">Selecione o paciente</option>
                    <?php foreach ($pacientes as $paciente) : ?>
                        <option value="<?= $paciente["id_paciente"] ?>"><?= $paciente["nome_pac"] ?></option>
                    <?php endforeach; ?>
                </select>
                <div>
                    <a style="font-size:0.6em; margin-left:7px;color:darkgray" href="<?= $BASE_URL ?>cad_paciente.php?id_estipulante=<?= $id_estipulante ?>"><i style="color:darkgray" name="type" value="edite" class="far fa-edit edit-icon"></i> Novo Paciente</a>
                </div>
            </div>
            <?php $dataAtual = date('Y-m-d');
            ?>

            <div class="form-group col-sm-2">
                <label class="control-label" for="data_intern_int">Data Internação</label>
                <input type="date" class="form-control" id="data_intern_int" value="<?php echo date('Y-m-d') ?>" name="data_intern_int">
                <div class="notif-input oculto" id="notif-input">
                    Data inválida !
                </div>
            </div>

            <div class="form-group col-sm-1">
                <label class="control-label" for="internado_int">Internado</label>
                <select class="form-control" id="internado_int" name="internado_int">
                    <option value="s">Sim</option>
                    <option value="n">Não</option>
                </select>
            </div>
            <!-- ENTRADA DE DADOS AUTOMATICOS NO INPUT-->
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="n" id="internado_uti_int" name="internado_uti_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="n" id="internacao_uti_int" name="internacao_uti_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="s" id="primeira_vis_int" name="primeira_vis_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="0" id="visita_no_int" name="visita_no_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="n" id="conta_finalizada_int" name="conta_finalizada_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="n" id="conta_paga_int" name="conta_paga_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="s" id="internacao_ativa_int" name="internacao_ativa_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" id="visita_enf_int" name="visita_enf_int" placeholder="<?php if (($_SESSION['cargo']) === 'Enf_auditor') {
                                                                                                                        echo 's';
                                                                                                                    } else {
                                                                                                                        echo 'n';
                                                                                                                    }; ?>" value="<?php if (($_SESSION['cargo']) === 'Enf_auditor') {
                                                                                                                                        's';
                                                                                                                                    } else {
                                                                                                                                        'n';
                                                                                                                                    }; ?>">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" id="visita_med_int" name="visita_med_int" placeholder="<?php if (($_SESSION['cargo']) === 'Med_auditor') {
                                                                                                                        echo 's';
                                                                                                                    } else {
                                                                                                                        echo 'n';
                                                                                                                    }; ?>" value="<?php if (($_SESSION['cargo']) == 'Med_auditor') {
                                                                                                                                        's';
                                                                                                                                    } else {
                                                                                                                                        'n';
                                                                                                                                    }; ?>">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" id="visita_auditor_prof_enf" name="visita_auditor_prof_enf" placeholder="<?php if (($_SESSION['cargo']) === 'Enf_auditor') {
                                                                                                                                        echo ($_SESSION['login_user']);
                                                                                                                                    }; ?>" value="<?php if (($_SESSION['cargo']) === 'Enf_auditor') {
                                                                                                                                                        ($_SESSION['login_user']);
                                                                                                                                                    }; ?>">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" id="visita_auditor_prof_med" name="visita_auditor_prof_med" placeholder="<?php if (($_SESSION['cargo']) === 'Med_auditor') {
                                                                                                                                        echo ($_SESSION['login_user']);
                                                                                                                                    }; ?>" value="<?php if (($_SESSION['cargo']) == 'Med_auditor') {
                                                                                                                                                        ($_SESSION['login_user']);
                                                                                                                                                    }; ?>">
            </div>

        </div>
        <div class="row">
            <div class="form-group col-sm-2">
                <label class="control-label" for="acomodacao_int">Acomodação</label>
                <select class="form-control" id="acomodacao_int" name="acomodacao_int">
                    <option value="">Selecione acomodação</option>
                    <?php
                    sort($dados_acomodacao, SORT_ASC);
                    foreach ($dados_acomodacao as $acomd) { ?>
                        <option value="<?= $acomd; ?>"><?= $acomd; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="especialidade_int">Especialidade</label>
                <select class="form-control" id="especialidade_int" name="especialidade_int">
                    <option value="">Selecione Especialidade</option>
                    <?php
                    sort($dados_especialidade, SORT_ASC);
                    foreach ($dados_especialidade as $especial) { ?>
                        <option value="<?= $especial; ?>"><?= $especial; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label for="titular_int">Médico</label>
                <input type="text" class="form-control" id="titular_int" name="titular_int" placeholder="Digite o nome do médico">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="modo_internacao_int">Modo Admissão</label>
                <select class="form-control" id="modo_internacao_int" name="modo_internacao_int">
                    <option value="">Selecione o modo internação</option>
                    <option value="Clínica">Clínica</option>
                    <option value="Pediatria">Pediatria</option>
                    <option value="Ortopedia">Ortopedia</option>
                    <option value="Obstetrícia">Obstetrícia</option>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="modo_internacao_int">Tipo Internação</label>
                <select class="form-control" id="modo_internacao_int" name="modo_internacao_int">
                    <option value="">Selecione o tipo de admissão</option>
                    <option value="Eletiva">Eletiva</option>
                    <option value="Urgência">Urgência</option>
                </select>
            </div>




            <div class="form-group col-sm-2">
                <label for="senha_int">Senha</label>
                <input type="text" class="form-control" id="senha_int" name="senha_int" placeholder="Digite a senha">
            </div>
            <div class="form-group col-sm-3">
                <label for="usuario_create_int">Usuário</label>
                <input type="text" class="form-control" id="usuario_create_int" value="<?= $_SESSION['email_user'] ?>" name="usuario_create_int" readonly>
            </div>
            <div class="form-group row">

                <div class="form-group col-sm-3">
                    <?php $agora = date('d/m/Y'); ?>
                    <input type="hidden" class="form-control" id="data_create_int" value='<?= $agora; ?>' name="data_create_int" placeholder="Digite o nome do médico">
                </div>
            </div>
            <br>
            <div> <button style="margin:10px" type="submit" class="btn-sm btn-success btn-int-niveis">Cadastrar</button>
            </div>
            <br>
        </div>
    </form>
</div>
<script src="js/scriptDataInt.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>