<div class="row">
    <h4 class="page-title">Cadastrar internação</h4>
    <p class="page-description">Adicione informações sobre a internação</p>
    <form class="formulario visible" action="<?= $BASE_URL ?>process_internacao.php" id="add-internacao-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="type" value="create">

        <div class="form-group row">
            <div class="form-group col-sm-3">
                <label class="control-label col-sm-3 " for="fk_hospital_int">Hospital</label>
                <select class="form-control" id="fk_hospital_int" name="fk_hospital_int" required>
                    <option value="<?= $hospital["nome_hosp"] ?>">Selecione o Hospital</option>
                    <?php foreach ($listHopitaisPerfil as $hospital) : ?>
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
            <div class="form-group col-sm-2">
                <label for="data_visita_int">Data Visita</label>
                <input type="date" value='<?= $dataAtual; ?>' class="form-control" id="data_visita_int" name="data_visita_int" readonly>
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
                <input type="hidden" class="form-control" value="s" id="primeira_vis_int" name="primeira_vis_int">
            </div>
            <div class="form-group col-sm-1">
                <input type="hidden" class="form-control" value="0" id="visita_no_int" name="visita_no_int">
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
                    <option value="">Selecione a especialidade</option>
                    <option value="Ginecologia">Ginecologia</option>
                    <option value="Cardiologia">Cardiologia</option>
                    <option value="Ortopedia">Ortopedia</option>
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
                <label class="control-label" for="tipo_admissao_int">Tipo Internação</label>
                <select class="form-control" id="tipo_admissao_int" name="tipo_admissao_int">
                    <option value="">Selecione o tipo de admissão</option>
                    <option value="Eletiva">Eletiva</option>
                    <option value="Urgência">Urgência</option>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="fk_patologia_int">Patologia</label>
                <select class="form-control" id="fk_patologia_int" name="fk_patologia_int">
                    <option value="">Selecione a patologia</option>
                    <?php foreach ($patologias as $patologia) : ?>
                        <option value="<?= $patologia["id_patologia"] ?>"><?= $patologia["patologia_pat"] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="fk_patologia2">Antecedente</label>
                <select class="form-control" id="fk_patologia2" name="fk_patologia2">
                    <option value="">Selecione a Patologia</option>
                    <?php foreach ($patologias as $patologia) : ?>
                        <option value="<?= $patologia["id_patologia"] ?>"><?= $patologia["patologia_pat"] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="grupo_patologia_int">Grupo Patologia</label>
                <select class="form-control" id="grupo_patologia_int" name="grupo_patologia_int">
                    <option value="">Selecione o Grupo</option>
                    <?php foreach ($dados_grupo_pat as $grupo) : ?>
                        <option value="<?= $grupo ?>"><?= $grupo ?></option>
                    <?php endforeach; ?>
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
                <div>
                    <label for="rel_int">Relatório Auditoria</label>
                    <textarea type="textarea" rows="20" class="form-control" id="rel_int" name="rel_int" placeholder="Relatório da auditoria"></textarea>
                </div>
                <div>
                    <label for="acoes_int">Ações Auditoria</label>
                    <textarea rows="20" type="textarea" class="form-control" id="acoes_int" name="acoes_int" placeholder="Ações de auditoria"></textarea>
                </div>
                <div class="form-group col-sm-3">
                    <?php $agora = date('Y-m-d'); ?>
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