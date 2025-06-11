<form id="myForm" action="process_internacao.php" method="post">

    <div id="container-gestao">
        <input type="text" style="display:none" name="type" id="typeGes" value="create">
        <div class="form-group row" style="display:none">

            <div class="form-group col-sm-1">
                <input type="text" readonly class="form-control" id="fk_internacao_ges" name="fk_internacao_ges"
                    value="<?= $int_gestao->fk_internacao_ges ?>">
            </div>
        </div>
        <div class="form-group row">

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
                            <select class="form-control-sm form-control" id="evento_fech_ges" name="evento_fech_ges">
                                <option value="n">Não</option>
                                <option value="s">Sim</option>
                            </select>
                        </div>
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
        // var input_type_ges = document.querySelector('#typeGes');

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>