<div id="container-uti" style=" margin:5px">
    <input type="hidden" style="display:none" name="type" value="create">
    <input type="hidden" style="display:none" class="form-control" id="id_internacao" name="id_internacao" value="<?= ($ultimoReg) ?> ">

    <!-- DADOS PARA FORMULARIO UTI -->
    <div class=" form-group row">
        <?php
        $a = ($findMaxUtiInt[0]);
        $ultimoReg = ($a["ultimoReg"]) + 1;
        ?>
        <input type="hidden" class="form-control" style="display:none" readonly id="fk_internacao_uti" name="fk_internacao_uti"
            value="<?= ($ultimoReg) ?> ">
        <input type="hidden" style="display:none" class="form-control" id="internacao_uti" name="internacao_uti" value="s">
        <input type="hidden" style="display:none" class="form-control" id="internado_uti_int" name="internado_uti_int" value="s">
        <input type="hidden" style="display:none" class="form-control" id="fk_user_uti" value="<?= $_SESSION['id_usuario'] ?>"
            name="fk_user_uti">

        <div class="form-group col-sm-2">
            <label for="internado_uti">Internado UTI</label>
            <select class="form-control-sm form-control" id="internado_uti" name="internado_uti">
                <option value="s">Sim</option>
                <option value="n">Não</option>
            </select>
        </div>
        <div class="form-group col-sm-2">
            <label for="motivo_uti">Motivo</label>
            <select class="form-control-sm form-control" id="motivo_uti" name="motivo_uti">
                <option value=" ">Selecione</option>
                <?php
                sort($dados_UTI, SORT_ASC);
                foreach ($dados_UTI as $uti) { ?>
                <option value="<?= $uti; ?>">
                    <?= $uti; ?>
                </option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group col-sm-2">
            <label for="just_uti">Justificativa</label>
            <select class="form-control-sm form-control" id="just_uti" name="just_uti">
                <option value="Pertinente">Pertinente</option>
                <option value="Não pertinente">Não pertinente</option>
            </select>
        </div>
        <div class="form-group col-sm-2">
            <label for="criterio_uti">Critério</label>
            <select class="form-control-sm form-control" id="criterio_uti" name="criterio_uti">
                <option value=" ">Selecione</option>
                <?php
                sort($criterios_UTI, SORT_ASC);
                foreach ($criterios_UTI as $uti) { ?>
                <option value="<?= $uti; ?>">
                    <?= $uti; ?>
                </option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group col-sm-2">
            <label for="data_internacao_uti">Data internação UTI</label>
            <input type="date" class="form-control-sm form-control" id="data_internacao_uti"
                value="<?php echo date('Y-m-d') ?>" name="data_internacao_uti">
        </div>
        <input type="hidden" class="form-control-sm form-control" id="fk_user_uti"
            value="<?= $_SESSION['id_usuario'] ?>" name="fk_user_uti">

        <div class="form-group row">
            <div class="form-group col-sm-2">
                <label for="vm_uti">VM</label>
                <select class="form-control-sm form-control" id="vm_uti" name="vm_uti">
                    <option value="n">Não</option>
                    <option value="s">Sim</option>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label for="dva_uti">DVA</label>
                <select class="form-control-sm form-control" id="dva_uti" name="dva_uti">
                    <option value="n">Não</option>
                    <option value="s">Sim</option>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label for="score_uti">Score</label>
                <select class="form-control-sm form-control" id="score_uti" name="score_uti">
                    <option value="">Selecione</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label for="saps_uti">Saps</label>
                <select class="form-control-sm form-control" id="saps_uti" name="saps_uti">
                    <option value=" ">Selecione</option>
                    <?php
                    sort($dados_saps, SORT_ASC);
                    foreach ($dados_saps as $saps) { ?>
                    <option value="<?= $saps; ?>">
                        <?= $saps; ?>
                    </option>
                    <?php } ?>
                </select>
            </div>
            <div style="margin-top:30px " class="form-group col-sm-2">
                <a style="color:blue; font-size:0.8em" href="https://www.rccc.eu/ppc/indicadores/saps3.html"
                    target="_blank">Calcular SAPS</a>
            </div>
        </div>
        <div>
            <label for="rel_uti">Relatório UTI</label>
            <textarea type="textarea" style="resize:none" onclick="aumentarTextUTI()" rows="2" class="form-control"
                id="rel_uti" name="rel_uti"></textarea>
        </div>
    </div>

</div>

<script>
// mudar linhas do text relatorio UTI 
var text_relatorio_uti = document.querySelector("#rel_uti");

function aumentarTextUTI() {
    if (text_relatorio_uti.rows == "2") {
        text_relatorio_uti.rows = "30"
    } else {
        text_relatorio_uti.rows = "2"
    }
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>