<style>
.prorrogacao-container .form-group.row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-start;
}

.prorrogacao-container .form-group {
    margin-bottom: 15px;
}

.prorrogacao-container .form-group label {
    margin-bottom: 5px;
    font-weight: bold;
}

.prorrogacao-container .form-control {
    width: 100%;
    padding: 5px;
}

.prorrogacao-container .btn {
    padding: 5px 10px;
    font-size: 0.9rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.prorrogacao-container .btn-add {
    background-color: #007bff;
    color: white;
}

.prorrogacao-container .btn-remove {
    background-color: #dc3545;
    color: white;
}

.prorrogacao-container #prorrogacoes-json {
    margin-top: 20px;
    width: 100%;
    padding: 10px;
    font-size: 1rem;
}
</style>
<div class="prorrogacao-container" id="container-prorrog" style="display:none;">
    <div id="fieldsContainer">
        <div class="titulo-abas">
            <h7 style="font-weight: 600; color:white">Prorrogação</h7>
            <?php if (!empty($prorrogIntern) && count($prorrogIntern) > 0): ?>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalProrrog"
                id="openmodal">
                <i class="fas fa-eye"></i> Prorrogações Anteriores
            </button>
            <?php endif; ?>
        </div>
        <div class="field-container form-group row">
            <input type="hidden" id="fk_internacao_pror" name="fk_internacao_pror" value="<?= $ultimoReg ?>">
            <input type="hidden" value="<?= $_SESSION["id_usuario"] ?>" id="fk_usuario_pror" name="fk_usuario_pror">
            <div class="form-group col-sm-2">
                <label class="control-label" for="acomod1_pror">Acomodação</label>
                <select onchange="generateProrJSON()" class="form-control-sm form-control" id="acomod1_pror"
                    name="acomod1_pror">
                    <option value=""> </option>
                    <?php sort($dados_acomodacao, SORT_ASC);
                    foreach ($dados_acomodacao as $acomd) { ?>
                    <option value="<?= $acomd; ?>"><?= $acomd; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="prorrog1_ini_pror">Data inicial</label>
                <input onchange="generateProrJSON()" type="date" class="form-control-sm form-control"
                    id="prorrog1_ini_pror" name="prorrog1_ini_pror">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="prorrog1_fim_pror">Data final</label>
                <input onchange="generateProrJSON()" type="date" class="form-control-sm form-control"
                    id="prorrog1_fim_pror" name="prorrog1_fim_pror">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="diarias_1">Diárias</label>
                <input type="text" style="text-align:center; font-weight:600; background-color:darkgray" readonly
                    class="form-control-sm form-control" id="diarias_1" name="diarias_1">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="isol_1_pror">Isolamento</label>
                <select onchange="generateProrJSON()" class="form-control-sm form-control" id="isol_1_pror"
                    name="isol_1_pror">
                    <option value="n">Não</option>
                    <option value="s">Sim</option>
                </select>
            </div>
            <div class="form-group col-sm-2" style="margin-top:25px">
                <button type="button" class="btn btn-remove" onclick="removeField(this)">-</button>
                <button type="button" class="btn btn-add" onclick="addField()">+</button>
            </div>
            <p class="error-message" style="color:red; font-size:0.8em; display:none;"></p>
        </div>
    </div>
    <!-- <button type="button" class="btn btn-add" onclick="generateJSON()">Gravar Prorrogação</button> -->
    <input type="hidden" id="prorrogacoes-json" name="prorrogacoes-json">
    <!-- <div id="success-message" class="alert alert-success" style="display:none; margin-top:10px;">
        Prorrogação gravada com sucesso!
    </div> -->
    <!-- <textarea id="json-preview" rows="10" readonly placeholder="Pré-visualização do JSON"></textarea> -->
</div>

<div class="modal fade" id="modalProrrog">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="page-title" style="color:white">Prorrogações </h4>
                <p class="page-description" style="color:white; margin-top:5px">Informações sobre prorrogações
                    anteriores</p>
            </div>
            <div class="modal-body">
                <?php
                // Check if the visitas array is not empty
                if (empty($visitas)) {
                    echo ("<br>");
                    echo ("<p style='margin-left:100px'> <b>-- Esta internação ainda não possui Prorrogações  -- </b></p>");
                    echo ("<br>");
                } else { ?>
                <table class="table table-sm table-striped table-hover table-condensed">
                    <thead>
                        <tr>
                            <th scope="col" style="width:5%">Id</th>
                            <th scope="col" style="width:10%">Acomodação</th>
                            <th scope="col" style="width:15%">Inicio</th>
                            <th scope="col" style="width:15%">Fim</th>
                            <th scope="col" style="width:15%">Diárias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($prorrogIntern as $intern) {
                                $idProrrog = $intern["id_prorrogacao"] ?? "Desconhecido";
                                $acomod = $intern["acomod1_pror"] ?? "Desconhecido";
                                $inicio = date("d/m/Y", strtotime($intern['prorrog1_ini_pror'])) ?? "Desconhecido";
                                $fim = date("d/m/Y", strtotime($intern['prorrog1_fim_pror'])) ?? "--";

                                $diarias = $intern["diarias_1"] ?? "--";
                            ?>
                        <tr>
                            <td><?= $idProrrog ?></td>
                            <td><?= $acomod ?></td>
                            <td><?= $inicio ?></td>
                            <td><?= $fim ?></td>
                            <td><?= $diarias ?></td>
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
// Exibe o container ao carregar a página
dataInternacao = document.getElementById('data_intern_int').value;
document.addEventListener("DOMContentLoaded", function() {
    const selectProrrog = document.getElementById("select_prorrog");
    if (selectProrrog && selectProrrog.value === "s") {
        document.getElementById("container-prorrog").style.display = "block";
    }
});

/// Código novo para preencher a data inicial da primeira linha
function setFirstProrrogationDate() {
    const dataInternacao = document.getElementById("data_intern_int").value;
    const firstContainer = document.querySelector(".field-container");

    if (firstContainer && dataInternacao) {
        firstContainer.querySelector('[name="prorrog1_ini_pror"]').value = dataInternacao;
    }
}

// Adiciona o evento onchange ao campo data_intern_int
document.getElementById("data_intern_int").addEventListener("change", setFirstProrrogationDate);

// Ao carregar a página, define a data inicial da primeira linha, se a data de internação já estiver preenchida
document.addEventListener("DOMContentLoaded", function() {
    setFirstProrrogationDate();
});

// Função para criar novos campos dinâmicos
function createProrrogationField() {
    return `
        <div class="field-container form-group row">
            <input type="hidden" id="fk_internacao_pror" name="fk_internacao_pror" value="<?= $ultimoReg ?>">
            <input type="hidden" value="<?= $_SESSION["id_usuario"] ?>" id="fk_usuario_pror" name="fk_usuario_pror">
            <div class="form-group col-sm-2">
                <label class="control-label" for="acomod1_pror">Acomodação</label>
                <select onchange="generateProrJSON()" class="form-control-sm form-control" id="acomod1_pror" name="acomod1_pror">
                    <option value=""> </option>
                    <?php sort($dados_acomodacao, SORT_ASC);
                    foreach ($dados_acomodacao as $acomd) { ?>
                        <option value="<?= $acomd; ?>"><?= $acomd; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="prorrog1_ini_pror">Data inicial</label>
                <input onchange="generateProrJSON()" type="date" class="form-control-sm form-control" id="prorrog1_ini_pror" name="prorrog1_ini_pror">
            </div>
            <div class="form-group col-sm-2">
                <label class="control-label" for="prorrog1_fim_pror">Data final</label>
                <input onchange="generateProrJSON()" type="date" class="form-control-sm form-control" id="prorrog1_fim_pror" name="prorrog1_fim_pror">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="diarias_1">Diárias</label>
                <input type="text" style="text-align:center; font-weight:600; background-color:darkgray" readonly
                    class="form-control-sm form-control" id="diarias_1" name="diarias_1">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="isol_1_pror">Isolamento</label>
                <select onchange="generateProrJSON()" class="form-control-sm form-control" id="isol_1_pror" name="isol_1_pror">
                    <option value="n">Não</option>
                    <option value="s">Sim</option>
                </select>
            </div>
            <div class="form-group col-sm-2" style="margin-top:25px">
                <button type="button" class="btn btn-remove" onclick="removeField(this)">-</button>
                <button type="button" class="btn btn-add" onclick="addField()">+</button>
            </div>
            <p class="error-message" style="color:red; font-size:0.8em; display:none;"></p>
        </div>
        `;
}


// Função para adicionar campos dinâmicos

function addField() {
    const fieldsContainer = document.getElementById("fieldsContainer");
    const newField = createProrrogationField();
    fieldsContainer.insertAdjacentHTML("beforeend", newField);

    // Define a data inicial da nova linha como a data final da linha anterior
    const fieldContainers = document.querySelectorAll(".field-container");
    if (fieldContainers.length > 1) {
        const lastContainer = fieldContainers[fieldContainers.length - 2];
        const newContainer = fieldContainers[fieldContainers.length - 1];

        const lastEndDate = lastContainer.querySelector('[name="prorrog1_fim_pror"]').value;
        newContainer.querySelector('[name="prorrog1_ini_pror"]').value = lastEndDate;
    }
}

// Função para remover campos dinâmicos
function removeField(button) {
    const fieldContainer = button.closest(".field-container");
    fieldContainer.remove();
}

// Função para calcular as diárias e validar as datas
function calculateDiarias(container) {
    const dataAtual = new Date().toISOString().split("T")[0];
    const dataInicial = container.querySelector('[name="prorrog1_ini_pror"]').value;
    const dataFinal = container.querySelector('[name="prorrog1_fim_pror"]').value;
    const diariasField = container.querySelector('[name="diarias_1"]');
    const errorMessage = container.querySelector(".error-message");

    errorMessage.textContent = ""; // Limpa mensagens de erro

    if (dataInicial && dataFinal) {
        const inicio = new Date(dataInicial);
        const fim = new Date(dataFinal);
        const internacao = new Date(dataInternacao);

        if (inicio < internacao) {
            errorMessage.textContent = "A data inicial não pode ser menor que a data de internação.";
            errorMessage.style.display = "block";
            diariasField.value = "";
            return;
        }

        if (fim < inicio) {
            errorMessage.textContent = "A data final não pode ser menor que a data inicial.";
            errorMessage.style.display = "block";
            diariasField.value = "";
            return;
        }

        if (fim > new Date(dataAtual)) {
            errorMessage.textContent = "A data final não pode ser maior que a data atual.";
            errorMessage.style.display = "block";
            diariasField.value = "";
            return;
        }

        const diffTime = Math.abs(fim - inicio);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        diariasField.value = diffDays;
        errorMessage.style.display = "none";
    }
}

// Adiciona listeners para validação automática ao alterar as datas
document.getElementById("fieldsContainer").addEventListener("input", (event) => {
    const fieldContainer = event.target.closest(".field-container");
    if (fieldContainer) {
        calculateDiarias(fieldContainer);
    }
});


// Função para gerar o JSON
function generateProrJSON() {

    const fk_internacao_pror = document.getElementById("fk_internacao_pror").value;
    const fieldContainers = document.querySelectorAll(".field-container");
    const prorrogations = Array.from(fieldContainers).map((container) => {
        return {
            fk_internacao_pror: fk_internacao_pror,
            fk_usuario_pror: container.querySelector('[name="fk_usuario_pror"]').value,
            acomod1_pror: container.querySelector('[name="acomod1_pror"]').value,
            prorrog1_ini_pror: container.querySelector('[name="prorrog1_ini_pror"]').value,
            prorrog1_fim_pror: container.querySelector('[name="prorrog1_fim_pror"]').value,
            isol_1_pror: container.querySelector('[name="isol_1_pror"]').value,
            diarias_1: container.querySelector('[name="diarias_1"]').value,
        };
    });

    const jsonData = {
        prorrogations,
    };

    const jsonString = JSON.stringify(jsonData, null, 2);
    document.getElementById("prorrogacoes-json").value = jsonString;

    // Remove mensagens de erro
    // document.querySelectorAll('.error-message').forEach((element) => {
    //     element.textContent = '';
    //     element.style.display = 'none';
    // });

    // Exibir mensagem de sucesso
    // const successMessage = document.getElementById("success-message");
    // successMessage.textContent = "Prorrogação gravada com sucesso!";
    // successMessage.style.display = "block";

    // Ocultar mensagem após 5 segundos
    // setTimeout(() => {
    //     successMessage.style.display = "none";
    // }, 5000);

    // Opcional: Esconde o container de prorrogação
    // document.getElementById("container-prorrog").style.display = "none";
    // console.log(document.getElementById("prorrogacoes-json").value);

    // Zera todos os inputs e selects da página form_cad_internacao_prorrog
    // document.querySelectorAll('#container-prorrog input, #container-prorrog select').forEach((element) => {
    //     if (element.tagName === 'SELECT') {
    //         element.value = ''; // Reseta selects
    //     } else {
    //         element.value = ''; // Reseta inputs
    //     }
    // });
}

function clearProrrogInputs() {
    // Seleciona todos os contêineres de campos de prorrogação
    const fieldContainers = document.querySelectorAll(".field-container");

    // Verifica a quantidade de contêineres
    const containerCount = fieldContainers.length;

    fieldContainers.forEach((container, index) => {
        // Remover campos adicionais, mantendo apenas o primeiro
        if (index > 0) {
            container.remove();
        } else {
            // Limpar todos os inputs, exceto os campos ocultos (hidden)
            container.querySelectorAll('input:not([type="hidden"])').forEach((input) => {
                input.value = ''; // Limpa o valor do input
            });

            // Limpar todos os selects
            container.querySelectorAll('select').forEach((select) => {
                select.selectedIndex = 0; // Reseta o select para a primeira opção
            });
        }
    });

}
</script>