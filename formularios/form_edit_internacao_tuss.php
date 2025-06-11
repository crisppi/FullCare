<?php $agora = date('Y-m-d');
?>
<form class="visible" action="<?= $BASE_URL ?>process_tuss.php" id="myForm" method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="form-group row">
            <h4 class="page-title">Cadastrar internação</h4>
            <hr>
        </div>

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
                    <?php
                    $nomePaciente = ''; // Inicializa com valor vazio
                    if (!empty($intern["fk_paciente_int"])) {
                        foreach ($pacientes as $paciente) {
                            if ($paciente["id_paciente"] == $intern["fk_paciente_int"]) {
                                $nomePaciente = $paciente["nome_pac"]; // Define o nome do paciente correspondente
                                break;
                            }
                        }
                    }
                    ?>
                    <input type="text" class="form-control" id="nome_pac" value="<?= htmlspecialchars($nomePaciente) ?>"
                        name="nome_pac" readonly>

                    <input type="hidden" class="form-control " id="fk_paciente_int"
                        value="<?= $intern["fk_paciente_int"] ?>" name="fk_paciente_int" readonly>



                    <div id="alert_intern" style="font-size: 0.6em; margin-left: 7px; color: red; display:none">Paciente
                        já
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
                    <input type="date" class="form-control " id="data_visita_int"
                        value="<?= $intern["data_visita_int"] ?>" name="data_visita_int" readonly>
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
            <h3>Passo 2: Cadastro TUSS</h3>
            <hr>
            <div class="form-group row">
                <!-- bloco 1 -->
                <div class="form-group row">

                    <!-- <?php print_r($intern); ?> -->
                    <div class="form-group col-sm-3">
                        <label class="control-label" for="tuss">Descrição Tuss</label>
                        <select class="form-control-sm form-control selectpicker show-tick" data-size="5"
                            data-live-search="true" id="tuss_solicitado" name="tuss_solicitado">
                            <option value="">...</option>
                            <?php foreach ($tussGeral as $tuss): ?>
                            <option value="<?= $tuss["cod_tuss"] ?>">
                                <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" class="form-control" id="bloco1" name="bloco1" value="bloco1">
                    <input type="hidden" class="form-control" id="type" name="type" value="create">
                    <input type="hidden" class="form-control" id="fk_int_tuss" name="fk_int_tuss"
                        value="<?= $intern['id_internacao'] ?>">

                    <div class="form-group col-sm-1">
                        <label class="control-label" for="data_realizacao_tuss">Data</label>
                        <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss"
                            value="<?php echo date('Y-m-d') ?>" name="data_realizacao_tuss">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_solicitado">Qtd Solicitada</label>
                        <input type="text" class="form-control-sm form-control" id="qtd_tuss_solicitado"
                            name="qtd_tuss_solicitado">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_liberado">Qtd liberada</label>
                        <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado"
                            name="qtd_tuss_liberado">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="tuss_liberado_sn">Liberado</label>
                        <select class="form-control-sm form-control" id="tuss_liberado_sn" name="tuss_liberado_sn">
                            <option value="">Selec.</option>
                            <option value="s">Sim</option>
                            <option value="n">Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label for="adic1">Adicionar</label><br>
                        <input style="margin-left:30px" type="checkbox" id="adic1" name="adic1" value="adic1">
                    </div>
                </div>
                <!-- bloco 2-->
                <div id="div-TUSS2" style="display:none" class="form-group row">

                    <div class="form-group col-sm-3">
                        <label class="control-label" for="tuss_solicitado2">Descrição Tuss</label>
                        <select class="form-control-sm form-control" id="tuss_solicitado2" name="tuss_solicitado2">
                            <option value="">...</option>
                            <?php foreach ($tussGeral as $tuss): ?>
                            <option value="<?= $tuss["cod_tuss"] ?>">
                                <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" class="form-control" id="bloco2" name="bloco2" value="bloco2">

                    <div class="form-group col-sm-1">
                        <label class="control-label" for="data_realizacao_tuss2">Data</label>
                        <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss2"
                            value="<?php echo date('Y-m-d') ?>" name="data_realizacao_tuss2">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_solicitado2">Qtd Solicitada</label>
                        <input type="text" class="form-control" id="qtd_tuss_solicitado2" name="qtd_tuss_solicitado2">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_liberado2">Qtd liberada</label>
                        <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado2"
                            name="qtd_tuss_liberado2">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="tuss_liberado_sn2">Liberado</label>
                        <select class="form-control-sm form-control" id="tuss_liberado_sn2" name="tuss_liberado_sn2">
                            <option value="">Selec.</option>
                            <option value="s">Sim</option>
                            <option value="n">Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label for="adic2">Adicionar</label><br>
                        <input style="margin-left:30px" type="checkbox" id="adic2" name="adic2" value="adic2">
                    </div>
                </div>

                <!-- bloco 3-->
                <div id="div-TUSS3" style="display:none" class="form-group row">

                    <input type="hidden" class="form-control" id="bloco3" name="bloco3" value="bloco3">

                    <div class="form-group col-sm-3">
                        <label class="control-label" for="tuss_solicitado3">Descrição Tuss</label>
                        <select class="form-control-sm form-control" id="tuss_solicitado3" name="tuss_solicitado3">
                            <option value="">...</option>
                            <?php foreach ($tussGeral as $tuss): ?>
                            <option value="<?= $tuss["cod_tuss"] ?>">
                                <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label class="control-label" for="data_realizacao_tuss3">Data</label>
                        <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss3"
                            value="<?php echo date('Y-m-d') ?>" name="data_realizacao_tuss3">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_solicitado3">Qtd Solicitada</label>
                        <input type="text" class="form-control-sm form-control" id="qtd_tuss_solicitado3"
                            name="qtd_tuss_solicitado3">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_liberado3">Qtd liberada</label>
                        <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado3"
                            name="qtd_tuss_liberado3">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="tuss_liberado_sn3">Liberado</label>
                        <select class="form-control-sm form-control" id="tuss_liberado_sn3" name="tuss_liberado_sn3">
                            <option value="">Selec.</option>
                            <option value="s">Sim</option>
                            <option value="n">Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label for="adic3">Adicionar</label><br>
                        <input style="margin-left:30px" type="checkbox" id="adic3" name="adic3" value="adic3">
                    </div>
                </div>

                <!-- bloco 4-->
                <div id="div-TUSS4" style="display:none" class="form-group row">

                    <input type="hidden" class="form-control" id="bloco4" name="bloco4" value="bloco4">

                    <div class="form-group col-sm-3">
                        <label class="control-label" for="tuss_solicitado4">Descrição Tuss</label>
                        <select class="form-control" id="tuss_solicitado4" name="tuss_solicitado4">
                            <option value="">...</option>
                            <?php foreach ($tussGeral as $tuss): ?>
                            <option value="<?= $tuss["cod_tuss"] ?>">
                                <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label class="control-label" for="data_realizacao_tuss4">Data</label>
                        <input type="date" class="form-control" id="data_realizacao_tuss4"
                            value="<?php echo date('Y-m-d') ?>" name="data_realizacao_tuss4">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_solicitado4">Qtd Solicitada</label>
                        <input type="text" class="form-control" id="qtd_tuss_solicitado4" name="qtd_tuss_solicitado4">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_liberado4">Qtd liberada</label>
                        <input type="text" class="form-control" id="qtd_tuss_liberado4" name="qtd_tuss_liberado4">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="tuss_liberado_sn4">Liberado</label>
                        <select class="form-control" id="tuss_liberado_sn4" name="tuss_liberado_sn4">
                            <option value="">Selec.</option>
                            <option value="s">Sim</option>
                            <option value="n">Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label for="adic4">Adicionar</label><br>
                        <input style="margin-left:30px" type="checkbox" id="adic4" name="adic4" value="adic4">
                    </div>
                </div>
                <!-- bloco 5-->
                <div id="div-TUSS5" style="display:none" class="form-group row">

                    <input type="hidden" class="form-control" id="bloco5" name="bloco5" value="bloco5">

                    <div class="form-group col-sm-3">
                        <label class="control-label" for="tuss_solicitado5">Descrição Tuss</label>
                        <select class="form-control" id="tuss_solicitado5" name="tuss_solicitado5">
                            <option value="">...</option>
                            <?php foreach ($tussGeral as $tuss): ?>
                            <option value="<?= $tuss["cod_tuss"] ?>">
                                <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label class="control-label" for="data_realizacao_tuss5">Data </label>
                        <input type="date" class="form-control" id="data_realizacao_tuss5"
                            value="<?php echo date('Y-m-d') ?>" name="data_realizacao_tuss5">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_solicitado5">Qtd Solicitada</label>
                        <input type="text" class="form-control" id="qtd_tuss_solicitado5" name="qtd_tuss_solicitado5">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_liberado5">Qtd liberada</label>
                        <input type="text" class="form-control" id="qtd_tuss_liberado5" name="qtd_tuss_liberado5">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="tuss_liberado_sn5">Liberado</label>
                        <select class="form-control" id="tuss_liberado_sn5" name="tuss_liberado_sn5">
                            <option value="">Selec.</option>
                            <option value="s">Sim</option>
                            <option value="n">Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label for="adic5">Adicionar</label><br>
                        <input style="margin-left:30px" type="checkbox" id="adic5" name="adic5" value="adic5">
                    </div>
                </div>

                <!-- bloco 6-->
                <div id="div-TUSS6" style="display:none" class="form-group row">

                    <input type="hidden" class="form-control" id="bloco6" name="bloco6" value="bloco6">

                    <div class="form-group col-sm-3">
                        <label class="control-label" for="tuss_solicitado6">Descrição Tuss</label>
                        <select class="form-control" id="tuss_solicitado6" name="tuss_solicitado6">
                            <option value="">...</option>
                            <?php foreach ($tussGeral as $tuss): ?>
                            <option value="<?= $tuss["cod_tuss"] ?>">
                                <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <label class="control-label" for="data_realizacao_tuss6">Data</label>
                        <input type="date" class="form-control" id="data_realizacao_tuss6"
                            value="<?php echo date('Y-m-d') ?>" name="data_realizacao_tuss6">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_solicitado6">Qtd Solicitada</label>
                        <input type="text" class="form-control" id="qtd_tuss_solicitado6" name="qtd_tuss_solicitado6">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="qtd_tuss_liberado6">Qtd liberada</label>
                        <input type="text" class="form-control" id="qtd_tuss_liberado6" name="qtd_tuss_liberado6">
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="tuss_liberado_sn6">Liberado</label>
                        <select class="form-control" id="tuss_liberado_sn6" name="tuss_liberado_sn6">
                            <option value="">Selec.</option>
                            <option value="s">Sim</option>
                            <option value="n">Não</option>
                        </select>
                    </div>
                </div>
            </div>


            <!-- Botões de navegação -->
            <div class="form-group">
                <button type="button" class="btn btn-secondary" onclick="prevStep()">Voltar</button>
                <button type="submit" class="btn btn-success">Finalizar Cadastro</button>
            </div>
        </div>
</form>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

</div>


<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<!-- Bootstrap Select CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/css/bootstrap-select.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Select JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/js/bootstrap-select.min.js"></script>


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
// ao liberar adicionar 1
$(document).ready(function() {
    // Adicione um ouvinte de mudança ao checkbox button
    $('#adic1').change(function() {
        // Verifique se o checkbox button está marcado
        if ($(this).is(':checked')) {
            // Se estiver marcado, mostre a div
            $('#div-TUSS2').show();

        } else {
            // Se não estiver marcado, oculte a div
            $('#div-TUSS2').hide();
        }
    });
});


// ao liberar adicionar 2
$(document).ready(function() {
    // Adicione um ouvinte de mudança ao checkbox button
    $('#adic2').change(function() {
        // Verifique se o checkbox button está marcado
        if ($(this).is(':checked')) {
            // Se estiver marcado, mostre a div
            $('#div-TUSS3').show();

        } else {
            // Se não estiver marcado, oculte a div
            $('#div-TUSS3').hide();
        }
    });
});


// ao liberar adicionar 3
$(document).ready(function() {
    // Adicione um ouvinte de mudança ao checkbox button
    $('#adic3').change(function() {
        // Verifique se o checkbox button está marcado
        if ($(this).is(':checked')) {
            // Se estiver marcado, mostre a div
            $('#div-TUSS4').show();

        } else {
            // Se não estiver marcado, oculte a div
            $('#div-TUSS4').hide();
        }
    });
});


// ao liberar adicionar 4
$(document).ready(function() {
    // Adicione um ouvinte de mudança ao checkbox button
    $('#adic4').change(function() {
        // Verifique se o checkbox button está marcado
        if ($(this).is(':checked')) {
            // Se estiver marcado, mostre a div
            $('#div-TUSS5').show();

        } else {
            // Se não estiver marcado, oculte a div
            $('#div-TUSS5').hide();
        }
    });
});


// ao liberar adicionar 5
$(document).ready(function() {
    // Adicione um ouvinte de mudança ao checkbox button
    $('#adic5').change(function() {
        // Verifique se o checkbox button está marcado
        if ($(this).is(':checked')) {
            // Se estiver marcado, mostre a div
            $('#div-TUSS6').show();

        } else {
            // Se não estiver marcado, oculte a div
            $('#div-TUSS6').hide();
        }
    });
});


// ao liberar adicionar 6
$(document).ready(function() {
    // Adicione um ouvinte de mudança ao checkbox button
    $('#adic6').change(function() {
        // Verifique se o checkbox button está marcado
        if ($(this).is(':checked')) {
            // Se estiver marcado, mostre a div
            $('#div-TUSS7').show();

        } else {
            // Se não estiver marcado, oculte a div
            $('#div-TUSS7').hide();
        }
    });
});
</script>

<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<!-- Bootstrap Select CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/css/bootstrap-select.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Select JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/js/bootstrap-select.min.js"></script>