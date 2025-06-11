<style>
    .form-group.row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-start;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-control {
        width: 100%;
        padding: 5px;
    }

    .btn {
        padding: 5px 10px;
        font-size: 0.9rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-add {
        background-color: #007bff;
        color: white;
    }

    .btn-remove {
        background-color: #dc3545;
        color: white;
    }
</style>
<div id="container-tuss" style="display:none; margin:5px;">
    <div class="titulo-abas">


        <h7 style="font-weight: 600; color:white">TUSS</h7>
        <?php if (!empty($tussIntern) && count($tussIntern) > 0): ?>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTUSS" id="openmodal">
                <i class="fas fa-eye"></i> TUSS Liberados
            </button>
        <?php endif; ?>

    </div>
    <div id="tussFieldsContainer">
        <!-- Primeira linha de entrada -->
        <div class="tuss-field-container form-group row">
            <input type="hidden" id="tuss-json" name="tuss-json">
            <input type="hidden" class="form-control" id="fk_int_tuss" name="fk_int_tuss" value="<?= $ultimoReg + 1 ?>">
            <input type="hidden" value="<?= $_SESSION["id_usuario"] ?>" id="fk_usuario_tuss" name="fk_usuario_tuss">

            <div class="form-group col-sm-3">
                <label class="control-label" for="tuss_solicitado">Descrição Tuss</label>
                <select onchange="generateTussJSON()" class="form-control-sm form-control selectpicker show-tick"
                    data-size="5" data-live-search="true" id="tuss_solicitado" name="tuss_solicitado">
                    <option value="">...</option>
                    <?php foreach ($tussGeral as $tuss): ?>
                        <option value="<?= $tuss["cod_tuss"] ?>">
                            <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="data_realizacao_tuss">Data</label>
                <input onchange="generateTussJSON()" type="date" class="form-control-sm form-control"
                    id="data_realizacao_tuss" value="<?php echo date('Y-m-d') ?>" name="data_realizacao_tuss">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_solicitado">Qtd Solicitada</label>
                <input onchange="generateTussJSON()" type="text" class="form-control-sm form-control"
                    id="qtd_tuss_solicitado" name="qtd_tuss_solicitado">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_liberado">Qtd Liberada</label>
                <input onchange="generateTussJSON()" type="text" class="form-control-sm form-control"
                    id="qtd_tuss_liberado" name="qtd_tuss_liberado">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="tuss_liberado_sn">Liberado</label>
                <select onchange="generateTussJSON()" class="form-control-sm form-control" id="tuss_liberado_sn"
                    name="tuss_liberado_sn">
                    <option value="">Selecione</option>
                    <option value="s">Sim</option>
                    <option value="n">Não</option>
                </select>
            </div>
            <div class="form-group col-sm-1" style="margin-top:25px;">
                <button type="button" class="btn btn-add" onclick="addTussField()">+</button>
                <button type="button" class="btn btn-remove" onclick="removeTussField(this)">-</button>
            </div>
        </div>

    </div>
    <!-- <button type="button" class="btn btn-add" onclick="generateTussJSON()">Gravar Tuss</button> -->
    <div id="success-message" class="alert alert-success" style="display:none; margin-top:10px;">
        TUSS gravados com sucesso!
    </div>
</div>

<div class="modal fade" id="modalTUSS">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="page-title" style="color:white">TUSS Liberados</h4>
                <p class="page-description" style="color:white; margin-top:5px">Informações sobre TUSS liberados</p>
            </div>
            <div class="modal-body">
                <?php
                // Check if the visitas array is not empty
                if (empty($visitas)) {
                    echo ("<br>");
                    echo ("<p style='margin-left:100px'> <b>-- Esta internação ainda não possui TUSS liberados -- </b></p>");
                    echo ("<br>");
                } else { ?>
                    <table class="table table-sm table-striped table-hover table-condensed">
                        <thead>
                            <tr>
                                <th scope="col" style="width:15%">TUSS Solicitado</th>
                                <th scope="col" style="width:15%">TUSS Liberado?</th>
                                <th scope="col" style="width:15%">Quantidade Solicitada</th>
                                <th scope="col" style="width:10%">Quantidade Liberada</th>
                                <th scope="col" style="width:10%">Data TUSS</th>
                                <th scope="col" style="width:5%">Visualizar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($tussIntern as $intern) {
                                $visitaId = $intern["fk_internacao_vis"] ?? "N/A";
                                $dataVisita = !empty($intern['data_visita_vis'])
                                    ? date("d/m/Y", strtotime($intern['data_visita_vis']))
                                    : date("d/m/Y", strtotime($intern['data_visita_int']));
                                $tussSolicitado = $intern["terminologia_tuss"] ?? "Desconhecido";
                                $tussLiberado = ($intern["tuss_liberado_sn"] ?? '') === 's' ? 'Sim' : 'Não';
                                $qtdSolicitado = $intern["qtd_tuss_solicitado"] ?? "Desconhecido";
                                $qtdLiberado = $intern["qtd_tuss_liberado"] ?? "--";
                                $dataTuss = date("d/m/Y", strtotime($intern['data_realizacao_tuss']));
                                $linkVisualizar = $BASE_URL . "show_visita.php?id_visita=" . $visitaId;
                            ?>
                                <tr>
                                    <td><?= $tussSolicitado ?></td>
                                    <td><?= $tussLiberado ?></td>
                                    <td><?= $qtdSolicitado ?></td>
                                    <td><?= $qtdLiberado ?></td>
                                    <td><?= $dataTuss ?></td>
                                    <td>
                                        <a href="<?= $linkVisualizar ?>">
                                            <i style="color:green; margin-right:10px" class="fas fa-eye check-icon"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                <?php } ?>
                <br>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Mostra ou esconde o container com base no valor do select_tuss
        const selectTuss = document.getElementById("select_tuss");
        const containerTuss = document.getElementById("container-tuss");

        if (selectTuss) {

            const toggleTussContainer = () => {
                containerTuss.style.display = selectTuss.value === "s" ? "block" : "none";

            };

            selectTuss.addEventListener("change", toggleTussContainer);
            toggleTussContainer(); // Verifica no carregamento inicial

        }


    });

    // Adiciona uma nova linha de campos para TUSS
    function addTussField() {
        const tussFieldsContainer = document.getElementById("tussFieldsContainer");
        const newField = `
        <div class="tuss-field-container form-group row">
            <input type="hidden" class="form-control" name="fk_int_tuss" value="<?= $ultimoReg + 1 ?>">
            <input type="hidden" value="<?= $_SESSION["id_usuario"] ?>" name="fk_usuario_tuss">

            <div class="form-group col-sm-3">
                <label class="control-label" for="tuss_solicitado">Descrição Tuss</label>
                <select onchange="generateTussJSON()" class="form-control-sm form-control selectpicker" data-size="5"
                    data-live-search="true" name="tuss_solicitado">
                    <option value="">...</option>
                    <?php foreach ($tussGeral as $tuss): ?>
                        <option value="<?= $tuss["cod_tuss"] ?>">
                            <?= $tuss['cod_tuss'] . " - " . $tuss["terminologia_tuss"] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="data_realizacao_tuss">Data</label>
                <input onchange="generateTussJSON()" type="date" class="form-control-sm form-control" value="<?php echo date('Y-m-d') ?>" name="data_realizacao_tuss">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_solicitado">Qtd Solicitada</label>
                <input onchange="generateTussJSON()" type="text" class="form-control-sm form-control" name="qtd_tuss_solicitado">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="qtd_tuss_liberado">Qtd Liberada</label>
                <input onchange="generateTussJSON()" type="text" class="form-control-sm form-control" name="qtd_tuss_liberado">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="tuss_liberado_sn">Liberado</label>
                <select onchange="generateTussJSON()" class="form-control-sm form-control" name="tuss_liberado_sn">
                    <option value="">Selecione</option>
                    <option value="s">Sim</option>
                    <option value="n">Não</option>
                </select>
            </div>
            <div class="form-group col-sm-1" style="margin-top:25px;">
                <button type="button" class="btn btn-add" onclick="addTussField()">+</button>
                <button type="button" class="btn btn-remove" onclick="removeTussField(this)">-</button>
            </div>
        </div>
    `;
        tussFieldsContainer.insertAdjacentHTML("beforeend", newField);

        // Inicializa o plugin Bootstrap Selectpicker apenas para o novo select
        const newSelect = tussFieldsContainer.querySelector('.tuss-field-container:last-child select.selectpicker');
        $(newSelect).selectpicker();
    }

    // Remove uma linha de campos para TUSS
    function removeTussField(button) {
        const fieldContainer = button.closest(".tuss-field-container");
        if (fieldContainer) {
            fieldContainer.remove();
        }
    }
    // Função para gerar o JSON para os campos de TUSS
    function generateTussJSON() {
        const fkIntTussField = document.getElementById("fk_int_tuss").value;

        // Verifica se o elemento existe
        if (!fkIntTussField) {
            console.error("Erro: O campo 'fk_int_tuss' não foi encontrado no DOM.");
            return;
        }

        const fkIntTuss = fkIntTussField.value; // Obtém o valor do campo fk_int_tuss

        const tussFieldContainers = document.querySelectorAll(".tuss-field-container");

        const tussEntries = Array.from(tussFieldContainers).map((container) => {
            return {
                fk_int_tuss: container.querySelector('[name="fk_int_tuss"]').value,
                fk_usuario_tuss: container.querySelector('[name="fk_usuario_tuss"]').value,
                tuss_solicitado: container.querySelector('[name="tuss_solicitado"]').value,
                data_realizacao_tuss: container.querySelector('[name="data_realizacao_tuss"]').value,
                qtd_tuss_solicitado: container.querySelector('[name="qtd_tuss_solicitado"]').value,
                qtd_tuss_liberado: container.querySelector('[name="qtd_tuss_liberado"]').value,
                tuss_liberado_sn: container.querySelector('[name="tuss_liberado_sn"]').value,
            };
        });

        const jsonData = {
            tussEntries,
        };

        const jsonString = JSON.stringify(jsonData, null, 2);
        // console.log(jsonString);

        // Salva o JSON em um campo oculto para envio posterior
        const tussJsonField = document.getElementById("tuss-json");
        if (tussJsonField) {
            tussJsonField.value = jsonString;
        }
        // Remove mensagens de erro
        document.querySelectorAll('.error-message').forEach((element) => {
            element.textContent = '';
            element.style.display = 'none';
        });

        // Exibir mensagem de sucesso
        const successMessage = document.getElementById("success-message");
        // successMessage.textContent = "Códigos TUSS gravados com sucesso!";
        // successMessage.style.display = "block";


        // Ocultar mensagem após 5 segundos
        setTimeout(() => {
            successMessage.style.display = "none";
        }, 5000);


        // Opcional: Esconde o container de prorrogação
        // document.getElementById("container-tuss").style.display = "none";
    }

    function clearTussInputs() {
        // Seleciona todos os contêineres de campos TUSS
        const tussFieldContainers = document.querySelectorAll(".tuss-field-container");

        // Mantém apenas o primeiro conjunto de campos e remove os adicionais
        tussFieldContainers.forEach((container, index) => {
            if (index === 0) {
                // Apenas limpa os campos do primeiro grupo
                container.querySelectorAll('input:not([type="hidden"])').forEach((input) => {
                    input.value = ''; // Limpa o valor do input
                });

                container.querySelectorAll('select').forEach((select) => {
                    select.value = ''; // Define o valor vazio
                    $(select).selectpicker('refresh'); // Atualiza o Bootstrap Select
                });
            } else {
                container.remove(); // Remove os elementos extras
            }
        });

        // Limpa o JSON armazenado
        document.getElementById("tuss-json").value = "";
    }
</script>


<!-- <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script> -->
<!-- <script>

    <-- JavaScript para sincronizar os valores do input com o select -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>