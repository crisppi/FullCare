<?php
// Verifica se os respectivos campos TUSS estão preenchidos e define as divs para serem exibidas.
// $showDivTuss2 = isset($int_tuss[0]['tuss_solicitado2']) && !empty($int_tuss[0]['tuss_solicitado2']);
// $showDivTuss3 = isset($int_tuss[0]['tuss_solicitado3']) && !empty($int_tuss[0]['tuss_solicitado3']);
// $showDivTuss4 = isset($int_tuss[0]['tuss_solicitado4']) && !empty($int_tuss[0]['tuss_solicitado4']);
// $showDivTuss5 = isset($int_tuss[0]['tuss_solicitado5']) && !empty($int_tuss[0]['tuss_solicitado5']);
// $showDivTuss6 = isset($int_tuss[0]['tuss_solicitado6']) && !empty($int_tuss[0]['tuss_solicitado6']);
?>
<div id="container-tuss">
    <input type="hidden" name="type" style="display:none;" value="create">
    <div class="form-group row" style="display:none">
        <input type="hidden" class="form-control" value="<?= $_SESSION["id_usuario"] ?>" id="fk_id_usuario"
            name="fk_id_usuario">
        <input type="hidden" class="form-control" id="data_create_int" value='<?= $agora; ?>' name="data_create_int">
        <div class="form-group col-sm-1">
            <?php
            $a = ($gestaoIdMax[0]);
            $ultimoReg = ($a["ultimoReg"]) + 1;
            extract($findMaxProInt);
            ?>
            <input type="hidden" class="form-control" readonly id="fk_int_tuss" name="fk_int_tuss"
                value="<?= $ultimoReg ?>">
        </div>

    </div>

    <!-- TUSS -->
    <div class="form-group row">
        <?php
        

        // Pegando o primeiro array para exemplo
        // $dados = $int_tuss[0];
        // print_r($dados);
        ?>

        <!-- Bloco 1 -->
        <div class="form-group row">
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss_solicitado">Tuss</label>
                <input type="text" class="form-control-sm form-control" id="tuss_solicitado" name="tuss_solicitado"
                    value="<?= $dados['tuss_solicitado']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss">Descrição Tuss</label>
                <select class="form-control-sm form-control selectpicker show-tick" data-size="5"
                    data-live-search="true" id="tuss" name="tuss">
                    <option value="">...</option>
                    
                </select>
            </div>

            <input type="hidden" class="form-control" id="bloco1" name="bloco1" value="bloco1">
            <input type="hidden" class="form-control" id="fk_int_tuss" name="fk_int_tuss"
                value="<?= $dados['id_tuss']; ?>">

            <div class="form-group col-sm-1">
                <label class="control-label" for="data_realizacao_tuss">Data</label>
                <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss"
                    value="<?= $dados['data_realizacao_tuss']; ?>" name="data_realizacao_tuss">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_solicitado">Qtd Solicitada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_solicitado"
                    value="<?= $dados['qtd_tuss_solicitado']; ?>" name="qtd_tuss_solicitado">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_liberado">Qtd liberada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado"
                    value="<?= $dados['qtd_tuss_liberado']; ?>" name="qtd_tuss_liberado">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="tuss_liberado_sn">Liberado</label>
                <select class="form-control-sm form-control" id="tuss_liberado_sn" name="tuss_liberado_sn">
                    <option value="">Selec.</option>
                    <option value="s" <?= $dados['tuss_liberado_sn'] == 's' ? 'selected' : '' ?>>Sim</option>
                    <option value="n" <?= $dados['tuss_liberado_sn'] == 'n' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label for="adic1">Adicionar</label><br>
                <input style="margin-left:30px" type="checkbox" id="adic1" name="adic1" value="adic1">
            </div>
        </div>

        <!-- Bloco 2 -->
        <div id="div-TUSS2" style="<?= !$showDivTuss2 ? 'display: none' : '' ?>;" class="form-group row">
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss_solicitado2">Tuss (2)</label>
                <input type="text" class="form-control form-control-sm" id="tuss_solicitado2" name="tuss_solicitado2"
                    value="<?= $dados['tuss_solicitado2']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss2">Descrição Tuss</label>
                <select class="form-control-sm form-control" id="tuss2" name="tuss2">
                    <option value="">...</option>
                    <?php foreach ($tussGeral as $tuss): ?>
                        <option value="<?= $tuss["cod_tuss"] ?>" <?= $tuss["cod_tuss"] == $dados['tuss_solicitado2'] ? 'selected' : '' ?>>
                            <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-sm-1">
                <label class="control-label" for="data_realizacao_tuss2">Data</label>
                <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss2"
                    name="data_realizacao_tuss2" value="<?= $dados['data_realizacao_tuss2']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_solicitado2">Qtd Solicitada</label>
                <input type="text" class="form-control form-control-sm" id="qtd_tuss_solicitado2"
                    name="qtd_tuss_solicitado2" value="<?= $dados['qtd_tuss_solicitado2']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_liberado2">Qtd liberada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado2"
                    name="qtd_tuss_liberado2" value="<?= $dados['qtd_tuss_liberado2']; ?>">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="tuss_liberado_sn2">Liberado</label>
                <select class="form-control-sm form-control" id="tuss_liberado_sn2" name="tuss_liberado_sn2">
                    <option value="">Selec.</option>
                    <option value="s" <?= $dados['tuss_liberado_sn2'] == 's' ? 'selected' : '' ?>>Sim</option>
                    <option value="n" <?= $dados['tuss_liberado_sn2'] == 'n' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label for="adic2">Adicionar</label><br>
                <input style="margin-left:30px" type="checkbox" id="adic2" name="adic2" value="adic2">
            </div>
        </div>

        <!-- Bloco 3 -->
        <div id="div-TUSS3" style="<?= !$showDivTuss3 ? 'display: none' : '' ?>;" class="form-group row">
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss_solicitado3">Tuss (3)</label>
                <input type="text" class="form-control-sm form-control" id="tuss_solicitado3" name="tuss_solicitado3"
                    value="<?= $dados['tuss_solicitado3']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss3">Descrição Tuss</label>
                <select class="form-control-sm form-control" id="tuss3" name="tuss3">
                    <option value="">...</option>
                    <?php foreach ($tussGeral as $tuss): ?>
                        <option value="<?= $tuss["cod_tuss"] ?>" <?= $tuss["cod_tuss"] == $dados['tuss_solicitado3'] ? 'selected' : '' ?>>
                            <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-sm-1">
                <label class="control-label" for="data_realizacao_tuss3">Data</label>
                <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss3"
                    name="data_realizacao_tuss3" value="<?= $dados['data_realizacao_tuss3']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_solicitado3">Qtd Solicitada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_solicitado3"
                    name="qtd_tuss_solicitado3" value="<?= $dados['qtd_tuss_solicitado3']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_liberado3">Qtd liberada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado3"
                    name="qtd_tuss_liberado3" value="<?= $dados['qtd_tuss_liberado3']; ?>">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="tuss_liberado_sn3">Liberado</label>
                <select class="form-control-sm form-control" id="tuss_liberado_sn3" name="tuss_liberado_sn3">
                    <option value="">Selec.</option>
                    <option value="s" <?= $dados['tuss_liberado_sn3'] == 's' ? 'selected' : '' ?>>Sim</option>
                    <option value="n" <?= $dados['tuss_liberado_sn3'] == 'n' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label for="adic3">Adicionar</label><br>
                <input style="margin-left:30px" type="checkbox" id="adic3" name="adic3" value="adic3">
            </div>
        </div>

        <!-- Bloco 4 -->
        <div id="div-TUSS4" style="<?= !$showDivTuss4 ? 'display: none' : '' ?>;" class="form-group row">
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss_solicitado4">Tuss (4)</label>
                <input type="text" class="form-control-sm form-control" id="tuss_solicitado4" name="tuss_solicitado4"
                    value="<?= $dados['tuss_solicitado4']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss4">Descrição Tuss</label>
                <select class="form-control-sm form-control" id="tuss4" name="tuss4">
                    <option value="">...</option>
                    <?php foreach ($tussGeral as $tuss): ?>
                        <option value="<?= $tuss["cod_tuss"] ?>" <?= $tuss["cod_tuss"] == $dados['tuss_solicitado4'] ? 'selected' : '' ?>>
                            <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-sm-1">
                <label class="control-label" for="data_realizacao_tuss4">Data</label>
                <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss4"
                    name="data_realizacao_tuss4" value="<?= $dados['data_realizacao_tuss4']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_solicitado4">Qtd Solicitada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_solicitado4"
                    name="qtd_tuss_solicitado4" value="<?= $dados['qtd_tuss_solicitado4']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_liberado4">Qtd liberada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado4"
                    name="qtd_tuss_liberado4" value="<?= $dados['qtd_tuss_liberado4']; ?>">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="tuss_liberado_sn4">Liberado</label>
                <select class="form-control-sm form-control" id="tuss_liberado_sn4" name="tuss_liberado_sn4">
                    <option value="">Selec.</option>
                    <option value="s" <?= $dados['tuss_liberado_sn4'] == 's' ? 'selected' : '' ?>>Sim</option>
                    <option value="n" <?= $dados['tuss_liberado_sn4'] == 'n' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label for="adic4">Adicionar</label><br>
                <input style="margin-left:30px" type="checkbox" id="adic4" name="adic4" value="adic4">
            </div>
        </div>

        <!-- Bloco 5 -->
        <div id="div-TUSS5" style="<?= !$showDivTuss5 ? 'display: none' : '' ?>;" class="form-group row">
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss_solicitado5">Tuss (5)</label>
                <input type="text" class="form-control-sm form-control" id="tuss_solicitado5" name="tuss_solicitado5"
                    value="<?= $dados['tuss_solicitado5']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss5">Descrição Tuss</label>
                <select class="form-control-sm form-control" id="tuss5" name="tuss5">
                    <option value="">...</option>
                    <?php foreach ($tussGeral as $tuss): ?>
                        <option value="<?= $tuss["cod_tuss"] ?>" <?= $tuss["cod_tuss"] == $dados['tuss_solicitado5'] ? 'selected' : '' ?>>
                            <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-sm-1">
                <label class="control-label" for="data_realizacao_tuss5">Data</label>
                <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss5"
                    name="data_realizacao_tuss5" value="<?= $dados['data_realizacao_tuss5']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_solicitado5">Qtd Solicitada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_solicitado5"
                    name="qtd_tuss_solicitado5" value="<?= $dados['qtd_tuss_solicitado5']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_liberado5">Qtd liberada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado5"
                    name="qtd_tuss_liberado5" value="<?= $dados['qtd_tuss_liberado5']; ?>">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="tuss_liberado_sn5">Liberado</label>
                <select class="form-control-sm form-control" id="tuss_liberado_sn5" name="tuss_liberado_sn5">
                    <option value="">Selec.</option>
                    <option value="s" <?= $dados['tuss_liberado_sn5'] == 's' ? 'selected' : '' ?>>Sim</option>
                    <option value="n" <?= $dados['tuss_liberado_sn5'] == 'n' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label for="adic5">Adicionar</label><br>
                <input style="margin-left:30px" type="checkbox" id="adic5" name="adic5" value="adic5">
            </div>
        </div>

        <!-- Bloco 6 -->
        <div id="div-TUSS6" style="<?= !$showDivTuss6 ? 'display: none' : '' ?>;" class="form-group row">
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss_solicitado6">Tuss (6)</label>
                <input type="text" class="form-control-sm form-control" id="tuss_solicitado6" name="tuss_solicitado6"
                    value="<?= $dados['tuss_solicitado6']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="tuss6">Descrição Tuss</label>
                <select class="form-control-sm form-control" id="tuss6" name="tuss6">
                    <option value="">...</option>
                    <?php foreach ($tussGeral as $tuss): ?>
                        <option value="<?= $tuss["cod_tuss"] ?>" <?= $tuss["cod_tuss"] == $dados['tuss_solicitado6'] ? 'selected' : '' ?>>
                            <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-sm-1">
                <label class="control-label" for="data_realizacao_tuss6">Data</label>
                <input type="date" class="form-control-sm form-control" id="data_realizacao_tuss6"
                    name="data_realizacao_tuss6" value="<?= $dados['data_realizacao_tuss6']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_solicitado6">Qtd Solicitada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_solicitado6"
                    name="qtd_tuss_solicitado6" value="<?= $dados['qtd_tuss_solicitado6']; ?>">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_liberado6">Qtd liberada</label>
                <input type="text" class="form-control-sm form-control" id="qtd_tuss_liberado6"
                    name="qtd_tuss_liberado6" value="<?= $dados['qtd_tuss_liberado6']; ?>">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="tuss_liberado_sn6">Liberado</label>
                <select class="form-control-sm form-control" id="tuss_liberado_sn6" name="tuss_liberado_sn6">
                    <option value="">Selec.</option>
                    <option value="s" <?= $dados['tuss_liberado_sn6'] == 's' ? 'selected' : '' ?>>Sim</option>
                    <option value="n" <?= $dados['tuss_liberado_sn6'] == 'n' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label for="adic6">Adicionar</label><br>
                <input style="margin-left:30px" type="checkbox" id="adic6" name="adic6" value="adic6">
            </div>
        </div>

    </div>

</div>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<style>
    /* Increase the size of the checkbox */
    input[type=checkbox] {
        width: 18px;
        /* Set your desired width */
        height: 18px;
        /* Set your desired height */
    }
</style>
<script>
    $(document).ready(function () {
        $('.selectpicker').selectpicker();
        $('.selectpicker').selectpicker('refresh');
        $('.selectpicker').on('loaded.bs.select', function () {
            $('.bs-searchbox input').attr('placeholder', 'Digite para pesquisar...');
        });
    });

    // ao liberar adicionar 1
    $(document).ready(function () {
        // Adicione um ouvinte de mudança ao checkbox button
        $('#adic1').change(function () {
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

    function syncInput() {
        var input = document.getElementById('tuss_solicitado').value;
        var select = document.getElementById('tuss');

        // Atualize o valor selecionado no select
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            if (option.text.toLowerCase().includes(input.toLowerCase())) {
                option.selected = true;
                break;
            }
        }
    }
    // ao liberar adicionar 2
    $(document).ready(function () {
        // Adicione um ouvinte de mudança ao checkbox button
        $('#adic2').change(function () {
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

    function syncInput2() {
        var input = document.getElementById('tuss_solicitado2').value;
        var select = document.getElementById('tuss2');

        // Atualize o valor selecionado no select
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            if (option.text.toLowerCase().includes(input.toLowerCase())) {
                option.selected = true;
                break;
            }
        }
    }
    // ao liberar adicionar 3
    $(document).ready(function () {
        // Adicione um ouvinte de mudança ao checkbox button
        $('#adic3').change(function () {
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

    function syncInput3() {
        var input = document.getElementById('tuss_solicitado3').value;
        var select = document.getElementById('tuss3');

        // Atualize o valor selecionado no select
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            if (option.text.toLowerCase().includes(input.toLowerCase())) {
                option.selected = true;
                break;
            }
        }
    }
    // ao liberar adicionar 4
    $(document).ready(function () {
        // Adicione um ouvinte de mudança ao checkbox button
        $('#adic4').change(function () {
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

    function syncInput4() {
        var input = document.getElementById('tuss_solicitado4').value;
        var select = document.getElementById('tuss4');

        // Atualize o valor selecionado no select
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            if (option.text.toLowerCase().includes(input.toLowerCase())) {
                option.selected = true;
                break;
            }
        }
    }
    // ao liberar adicionar 5
    $(document).ready(function () {
        // Adicione um ouvinte de mudança ao checkbox button
        $('#adic5').change(function () {
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

    function syncInput5() {
        var input = document.getElementById('tuss_solicitado5').value;
        var select = document.getElementById('tuss5');

        // Atualize o valor selecionado no select
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            if (option.text.toLowerCase().includes(input.toLowerCase())) {
                option.selected = true;
                break;
            }
        }
    }
    // ao liberar adicionar 6
    $(document).ready(function () {
        // Adicione um ouvinte de mudança ao checkbox button
        $('#adic6').change(function () {
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

    function syncInput6() {
        var input = document.getElementById('tuss_solicitado6').value;
        var select = document.getElementById('tuss6');

        // Atualize o valor selecionado no select
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            if (option.text.toLowerCase().includes(input.toLowerCase())) {
                option.selected = true;
                break;
            }
        }
    }

    // Anexe a função ao evento change do select
    // $(document).ready(function() {
    //     $('#tuss_liberado_sn').change(funcaoAoMudarStatus);
    // });

    // $(document).ready(function() {
    //     $('#tuss_liberado_sn2').change(funcaoAoMudarStatus2);
    // });

    // $(document).ready(function() {
    //     $('#tuss_liberado_sn3').change(funcaoAoMudarStatus3);
    // });
</script>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- <script>

<-- JavaScript para sincronizar os valores do input com o select -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Inclui o CSS do Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Inclui o JavaScript do Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>