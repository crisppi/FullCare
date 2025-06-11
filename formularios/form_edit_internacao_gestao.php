<div id="container-gestao">
    <input type="hidden" style="display:none" name="type" value="create">
    <div class="form-group row" style="display:none">
        <?php
        $a = ($findMaxGesInt[0]);
        $ultimoReg = ($a["ultimoReg"]) + 1;
        ?>
        <div class="form-group col-sm-1">
            <input type="hidden" readonly class="form-control" id="fk_internacao_ges" name="fk_internacao_ges"
                value="<?= $int_gestao->fk_internacao_ges ?>">
        </div>
    </div>
    <div class="form-group row">
        <div class="form-group row">
            <div class="form-group col-sm-2">
                <label for="alto_custo_ges">Alto Custo</label>
                <select class="form-control-sm form-control" id="alto_custo_ges" name="alto_custo_ges">
                    <option value="n" <?= ($int_gestao->alto_custo_ges === 'n') ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($int_gestao->alto_custo_ges === 's') ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>
            <div class="form-group col-sm-2" id="div_rel_alto_custo"
                style="display: <?= ($int_gestao->alto_custo_ges === 's') ? 'block' : 'none' ?>">
                <label for="rel_alto_custo_ges">Relatório alto custo</label>
                <textarea type="textarea" style="resize:none" rows="2" onclick="aumentarText('rel_alto_custo_ges')"
                    onblur="reduzirText('rel_alto_custo_ges', 3)" class="form-control" id="rel_alto_custo_ges"
                    name="rel_alto_custo_ges"><?= $int_gestao->rel_alto_custo_ges ?></textarea>
            </div>
        </div>
        <hr>
        <div class="form-group row">
            <div class="form-group col-sm-2">
                <label for="home_care_ges">Home care</label>
                <select class="form-control-sm form-control" id="home_care_ges" name="home_care_ges">
                    <option value="n" <?= ($int_gestao->home_care_ges === 'n') ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($int_gestao->home_care_ges === 's') ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>
            <div class="form-group col-sm-2" id="div_rel_home_care"
                style="display: <?= ($int_gestao->home_care_ges === 's') ? 'block' : 'none' ?>">
                <label for="rel_home_care_ges">Relatório Home care</label>
                <textarea type="textarea" style="resize:none" rows="2" onclick="aumentarText('rel_home_care_ges')"
                    onblur="reduzirText('rel_home_care_ges', 3)" class="form-control" id="rel_home_care_ges"
                    name="rel_home_care_ges"><?= $int_gestao->rel_home_care_ges ?></textarea>
            </div>
        </div>
        <hr>
        <div class="form-group row">
            <div class="form-group col-sm-2">
                <label for="opme_ges">OPME</label>
                <select class="form-control-sm form-control" id="opme_ges" name="opme_ges">
                    <option value="n" <?= ($int_gestao->opme_ges === 'n') ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($int_gestao->opme_ges === 's') ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>
            <div class="form-group col-sm-2" id="div_rel_opme"
                style="display: <?= ($int_gestao->opme_ges === 's') ? 'block' : 'none' ?>">
                <label for="rel_opme_ges">Relatório OPME</label>
                <textarea type="textarea" style="resize:none" rows="2" onclick="aumentarText('rel_opme_ges')"
                    onblur="reduzirText('rel_opme_ges', 3)" class="form-control" id="rel_opme_ges"
                    name="rel_opme_ges"><?= $int_gestao->rel_opme_ges ?></textarea>
            </div>
        </div>
        <hr>
        <div class="form-group row">
            <div class="form-group col-sm-2">
                <label for="desospitalizacao_ges">Desospitalização</label>
                <select class="form-control-sm form-control" id="desospitalizacao_ges" name="desospitalizacao_ges">
                    <option value="n" <?= ($int_gestao->desospitalizacao_ges === 'n') ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($int_gestao->desospitalizacao_ges === 's') ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>
            <div class="form-group col-sm-2" id="div_rel_desospitalizacao"
                style="display: <?= ($int_gestao->desospitalizacao_ges === 's') ? 'block' : 'none' ?>">
                <label for="rel_desospitalizacao_ges">Relatório Desospitalização</label>
                <textarea type="textarea" style="resize:none" rows="2"
                    onclick="aumentarText('rel_desospitalizacao_ges')"
                    onblur="reduzirText('rel_desospitalizacao_ges', 3)" class="form-control"
                    id="rel_desospitalizacao_ges"
                    name="rel_desospitalizacao_ges"><?= $int_gestao->rel_desospitalizacao_ges ?></textarea>
            </div>
        </div>
        <hr>
        <div class="form-group row">
            <div class="form-group col-sm-2">
                <label for="evento_adverso_ges">Evento Adverso</label>
                <select class="form-control-sm form-control" id="evento_adverso_ges" name="evento_adverso_ges">
                    <option value="n" <?= ($int_gestao->evento_adverso_ges === 'n') ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($int_gestao->evento_adverso_ges === 's') ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>
            <!-- DIV evento adverso -->
            <div id="div_evento" class="form-group col-sm-10" style="display: <?= ($int_gestao->evento_adverso_ges === 's') ? 'block' : 'none' ?>">
                <div>
                    <label for="tipo_evento_adverso_gest">Tipo Evento Adverso</label>
                    <select class="form-control-sm form-control" id="tipo_evento_adverso_gest"
                        name="tipo_evento_adverso_gest">
                        <?php
                        sort($dados_tipo_evento, SORT_ASC);
                        foreach ($dados_tipo_evento as $evento) { ?>
                            <option value="<?= $evento; ?>"><?= $evento; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div id="div_rel_evento">
                    <label for="rel_evento_adverso_ges">Relatório Evento Adverso</label>
                    <textarea type="textarea" style="resize:none" rows="2"
                        onclick=" aumentarText('rel_evento_adverso_ges')"
                        onblur="reduzirText('rel_evento_adverso_ges', 3)" class="form-control"
                        id="rel_evento_adverso_ges" name="rel_evento_adverso_ges"></textarea>
                </div>

                <!-- novos -->
                <div class="form-group row">
                    <div class="form-group col-sm-2">
                        <label for="evento_sinalizado_ges">Evento sinalizado</label>
                        <select class="form-control-sm form-control" id="evento_sinalizado_ges"
                            name="evento_sinalizado_ges">
                            <option value="n">Não</option>
                            <option value="s">Sim</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-2">
                        <label for="evento_discutido_ges">Evento discutido</label>
                        <select class="form-control-sm form-control" id="evento_discutido_ges"
                            name="evento_discutido_ges">
                            <option value="n">Não</option>
                            <option value="s">Sim</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-2">
                        <label for="evento_negociado_ges">Evento negociado</label>
                        <select class="form-control-sm form-control" id="evento_negociado_ges"
                            name="evento_negociado_ges">
                            <option value="n">Não</option>
                            <option value="s">Sim</option>
                        </select>
                    </div>

                    <div class="form-group col-sm-2">
                        <label for="evento_valor_negoc_ges">Valor negociado</label>
                        <input type="text" class="form-control form-control-sm" id="evento_valor_negoc_ges" value=''
                            name="evento_valor_negoc_ges">
                    </div>
                    <div class="form-group col-sm-2">
                        <label for="evento_prorrogar_ges">Seguir Prorrogação</label>
                        <select class="form-control-sm form-control" id="evento_prorrogar_ges"
                            name="evento_prorrogar_ges">
                            <option value="n">Não</option>
                            <option value="s">Sim</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-2">
                        <label for="evento_fech_ges">Fechar conta</label>
                        <select class="form-control-sm form-control-smform-control" id="evento_fech_ges"
                            name="evento_fech_ges">
                            <option value="n">Não</option>
                            <option value="s">Sim</option>
                        </select>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    // JS PARA APARECER REL EVENTO ADVERSO
    var select_evento = document.querySelector('#evento_adverso_ges');

    select_evento.addEventListener('change', setEvento);

    function setEvento() {
        var choice_evento = select_evento.value;
        var div_evento = document.getElementById("div_evento")
        if (choice_evento === 's') {
            if (div_evento.style.display === "none") {
                div_evento.style.display = "block";
            }

        }
        if (choice_evento === 'n') {

            if (div_evento.style.display === "block") {
                div_evento.style.display = "none";
            }
        }
    }
    // // JS PARA APARECER REL OPME
    var select_opme = document.querySelector('#opme_ges');

    select_opme.addEventListener('change', setOpme);

    function setOpme() {
        var choice_opme = select_opme.value;
        var div_rel_opme = document.getElementById("div_rel_opme")
        if (choice_opme === 's') {

            if (div_rel_opme.style.display === "none") {
                div_rel_opme.style.display = "block";
            }

        }
        if (choice_opme === 'n') {

            if (div_rel_opme.style.display === "block") {
                div_rel_opme.style.display = "none";
            }
        }
    }
    // // JS PARA APARECER REL DESOSPITALIZACAO
    var select_desospitalizacao = document.querySelector('#desospitalizacao_ges');

    select_desospitalizacao.addEventListener('change', setdesospitalizacao);

    function setdesospitalizacao() {
        var choice_desospitalizacao = select_desospitalizacao.value;
        var div_rel_desospitalizacao = document.getElementById("div_rel_desospitalizacao")
        if (choice_desospitalizacao === 's') {

            if (div_rel_desospitalizacao.style.display === "none") {
                div_rel_desospitalizacao.style.display = "block";
            }

        }
        if (choice_desospitalizacao === 'n') {

            if (div_rel_desospitalizacao.style.display === "block") {
                div_rel_desospitalizacao.style.display = "none";
            }
        }
    }
    // // JS PARA APARECER REL HOME CARE
    var select_home_care = document.querySelector('#home_care_ges');

    select_home_care.addEventListener('change', sethome_care);

    function sethome_care() {
        var choice_home_care = select_home_care.value;
        var div_rel_home_care = document.getElementById("div_rel_home_care")
        if (choice_home_care === 's') {

            if (div_rel_home_care.style.display === "none") {
                div_rel_home_care.style.display = "block";
            }

        }
        if (choice_home_care === 'n') {

            if (div_rel_home_care.style.display === "block") {
                div_rel_home_care.style.display = "none";
            }
        }
    }
    // // JS PARA APARECER REL ALTO CUSTO
    var select_alto_custo = document.querySelector('#alto_custo_ges');

    select_alto_custo.addEventListener('change', setalto_custo);

    function setalto_custo() {
        var choice_alto_custo = select_alto_custo.value;
        var div_rel_alto_custo = document.getElementById("div_rel_alto_custo")
        if (choice_alto_custo === 's') {

            if (div_rel_alto_custo.style.display === "none") {
                div_rel_alto_custo.style.display = "block";
            }

        }
        if (choice_alto_custo === 'n') {

            if (div_rel_alto_custo.style.display === "block") {
                div_rel_alto_custo.style.display = "none";
            }
        }
    }
</script>

<script>
    // function aumentarText(textareaId) {
    //     document.getElementById(textareaId).rows = 20;
    // }

    // function reduzirText(textareaId, originalRows) {
    //     document.getElementById(textareaId).rows = originalRows;
    // }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>