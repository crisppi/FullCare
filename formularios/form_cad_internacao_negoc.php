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

    .form-group label,
    .form-group .form-control,
    .form-group select {
        font-size: 1em;
    }
</style>

<div id="container-negoc" style="display:none; margin:5px">
    <div class="titulo-abas">
        <h7 class="page-title" style="font-weight: 600;color:white">Negociações</h7>

    </div>
    <!-- <input type="hidden" name="type" value="create"> -->
    <input type="hidden" readonly class="form-control" id="fk_id_int" value="<?= $ultimoReg ?>" name="fk_id_int">
    <input type="hidden" class="form-control" value="<?= $_SESSION["id_usuario"] ?>" id="fk_usuario_neg"
        name="fk_usuario_neg">

    <div id="negotiationFieldsContainer" style="margin:5px;">

        <input type="hidden" id="negociacoes_json" name="negociacoes_json" value="">
        <!-- Primeira linha -->
        <div class="negotiation-field-container form-group row">
            <div class="form-group col-sm-2">
                <label for="tipo_negociacao">Tipo Negociação</label>
                <select name="tipo_negociacao" id="tipo_negociacao" class="form-control">
                    <option value="">Selecione</option>
                    <option value="TROCA UTI/APTO">TROCA UTI/APTO</option>
                    <option value="TROCA UTI/SEMI">TROCA UTI/SEMI</option>
                    <option value="TROCA SEMI/APTO">TROCA SEMI/APTO</option>
                    <option value="VESPERA">VESPERA</option>
                    <option value="GLOSA UTI">GLOSA UTI</option>
                    <option value="GLOSA APTO">GLOSA APTO</option>
                    <option value="GLOSA SEMI">GLOSA SEMI</option>
                    <option value="1/2 DIARIA APTO">1/2 DIARIA APTO</option>
                    <option value="TARDIA APTO">TARDIA APTO</option>
                    <option value="TARDIA UTI">TARDIA UTI</option>
                    <option value="DIARIA ADM">DIARIA ADM</option>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="data_inicio_negoc">Data inicial</label>
                <input onchange="generateNegotiationsJSON()" type="date" class="form-control-sm form-control"
                    id="data_inicio_negoc" name="data_inicio_negoc">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="data_fim_negoc">Data final</label>
                <input onchange="generateNegotiationsJSON()" type="date" class="form-control-sm form-control"
                    id="data_fim_negoc" name="data_fim_negoc">
            </div>
            <div class="form-group col-sm-2">
                <label for="troca_de">Acomodação Solicitada</label>
                <select onchange="generateNegotiationsJSON()" name="troca_de" class="form-control">
                    <option value=""> </option>
                    <?php sort($dados_acomodacao, SORT_ASC);
                    foreach ($dados_acomodacao as $acomd) { ?>
                        <option value="<?= $acomd; ?>"><?= $acomd; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label for="troca_para">Acomodação Liberada</label>
                <select onchange="generateNegotiationsJSON()" name="troca_para" class="form-control">
                    <option value=""> </option>
                    <?php sort($dados_acomodacao, SORT_ASC);
                    foreach ($dados_acomodacao as $acomd) { ?>
                        <option value="<?= $acomd; ?>"><?= $acomd; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label for="qtd">Quantidade</label>
                <input onchange="generateNegotiationsJSON()" type="number" name="qtd" class="form-control" min="1"
                    max="30">
            </div>
            <div class="form-group col-sm-1">
                <label for="saving_show">Saving</label>
                <input type="text" name="saving_show" class="form-control" readonly>
                <input type="hidden" name="saving" class="form-control">
            </div>
            <div class="form-group col-sm-1" style="margin-top:25px;">
                <button type="button" class="btn btn-add" onclick="addNegotiationField()">+</button>
                <button type="button" class="btn btn-remove" onclick="removeNegotiationField(this)">-</button>
            </div>
        </div>
    </div>

</div>
<hr>

<script>
    // Função para calcular o saving
    function calculateSaving(container) {
        const trocaDe = parseFloat(container.find('select[name="troca_de"] option:selected').data('valor') || 0);
        const trocaPara = parseFloat(container.find('select[name="troca_para"] option:selected').data('valor') || 0);
        const quantidade = parseInt(container.find('input[name="qtd"]').val(), 10) || 0;

        const tipoNegociacao = container.find('select[name="tipo_negociacao"] option:selected').text().toUpperCase().trim();

        if (isNaN(trocaDe) || isNaN(trocaPara) || isNaN(quantidade)) {
            console.error("Erro: Valores inválidos para cálculo do saving.", {
                trocaDe,
                trocaPara,
                quantidade
            });
            return;
        }

        let saving = 0;

        if (tipoNegociacao.startsWith("TROCA")) {
            // TROCA calculation (default logic)
            saving = (trocaDe - trocaPara) * quantidade;
        } else if (tipoNegociacao.includes("1/2 DIARIA")) {
            // 1/2 DIARIA calculation
            saving = quantidade * (trocaDe / 2);
        } else {
            // Other cases
            saving = quantidade * trocaDe;
        }

        container.find('input[name="saving"]').val(saving.toFixed(2));
        container.find('input[name="saving_show"]').val(
            saving >= 0 ? `R$ ${saving.toFixed(2)}` : `-R$ ${Math.abs(saving).toFixed(2)}`
        ).css('color', saving >= 0 ? 'green' : 'red');
    }



    // Função para adicionar uma nova linha de negociação
    function addNegotiationField() {
        const negotiationContainer = $('#negotiationFieldsContainer');
        const newField = `
        <div class="negotiation-field-container form-group row">
            <input type="hidden" readonly class="form-control" id="fk_id_int" name="fk_id_int">
            <input type="hidden" id="negociacoes_json" name="negociacoes_json" value="">
            <div class="form-group col-sm-2">
                <label for="tipo_negociacao">Tipo Negociação</label>
                <select name="tipo_negociacao" class="form-control">
                    <option value="">Selecione</option>
                    <option value="TROCA UTI/APTO">TROCA UTI/APTO</option>
                    <option value="TROCA UTI/SEMI">TROCA UTI/SEMI</option>
                    <option value="TROCA SEMI/APTO">TROCA SEMI/APTO</option>
                    <option value="VESPERA">VESPERA</option>
                    <option value="GLOSA UTI">GLOSA UTI</option>
                    <option value="GLOSA APTO">GLOSA APTO</option>
                    <option value="GLOSA SEMI">GLOSA SEMI</option>
                    <option value="1/2 DIARIA APTO">1/2 DIARIA APTO</option>
                    <option value="TARDIA APTO">TARDIA APTO</option>
                    <option value="TARDIA UTI">TARDIA UTI</option>
                    <option value="DIARIA ADM">DIARIA ADM</option>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="data_inicio_negoc">Data inicial</label>
                <input onchange="generateNegotiationsJSON()" type="date" class="form-control-sm form-control"
                    id="data_inicio_negoc" name="data_inicio_negoc">
            </div>
            <div class="form-group col-sm-1">
                <label class="control-label" for="data_fim_negoc">Data final</label>
                <input onchange="generateNegotiationsJSON()" type="date" class="form-control-sm form-control"
                    id="data_fim_negoc" name="data_fim_negoc">
            </div>
            <div class="form-group col-sm-2">
                <label for="troca_de">Acomodação Solicitada</label>
                <select onchange="generateNegotiationsJSON()" name="troca_de" class="form-control">
                     <option value=""> </option>
                    <?php sort($dados_acomodacao, SORT_ASC);
                    foreach ($dados_acomodacao as $acomd) { ?>
                        <option value="<?= $acomd; ?>"><?= $acomd; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-2">
                <label for="troca_para">Acomodação Liberada</label>
                <select onchange="generateNegotiationsJSON()" name="troca_para" class="form-control">
                     <option value=""> </option>
                    <?php sort($dados_acomodacao, SORT_ASC);
                    foreach ($dados_acomodacao as $acomd) { ?>
                        <option value="<?= $acomd; ?>"><?= $acomd; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group col-sm-1">
                <label for="qtd">Quantidade</label>
                <input type="number" onchange="generateNegotiationsJSON()" name="qtd" class="form-control" min="1" max="30">
            </div>
            <div class="form-group col-sm-1">
                <label for="saving_show">Saving</label>
                <input type="text" name="saving_show" class="form-control" readonly>
                <input type="hidden" name="saving" class="form-control">
            </div>
            <div class="form-group col-sm-1" style="margin-top:25px;">
                <button type="button" class="btn btn-add" onclick="addNegotiationField()">+</button>
                <button type="button" class="btn btn-remove" onclick="removeNegotiationField(this)">-</button>
            </div>
        </div>`;
        negotiationContainer.append(newField);

        // Popula os selects com as opções disponíveis (reutiliza as do primeiro campo)
        const options = $('select[name="troca_de"]').first().html();
        negotiationContainer.find('select[name="troca_de"]').last().html(options);
        negotiationContainer.find('select[name="troca_para"]').last().html(options);
    }

    function generateNegotiationsJSON() {

        const fkIdInt = document.getElementById("fk_id_int").value; // ID da internação
        const fkUsuarioNeg = document.getElementById("fk_usuario_neg").value; // ID do usuário
        const negotiationContainers = document.querySelectorAll(".negotiation-field-container");

        const negotiations = Array.from(negotiationContainers).map((container) => {
            const trocaDeSelect = container.querySelector('[name="troca_de"]');
            const trocaParaSelect = container.querySelector('[name="troca_para"]');
            const qtdInput = container.querySelector('[name="qtd"]');
            const savingInput = container.querySelector('[name="saving"]');
            const tipoNegociacao = container.querySelector('[name="tipo_negociacao"]');
            const dataInicioNegoc = container.querySelector('[name="data_inicio_negoc"]');
            const dataFimNegoc = container.querySelector('[name="data_fim_negoc"]');


            const valorTrocaDe = trocaDeSelect ? trocaDeSelect.value : null;
            const valorTrocaPara = trocaParaSelect ? trocaParaSelect.value : null;
            const qtd = qtdInput ? parseInt(qtdInput.value, 10) : null;
            const saving = savingInput ? parseFloat(savingInput.value) : null;

            const tipo = tipoNegociacao ? tipoNegociacao.value : null;
            const dataInicio = dataInicioNegoc ? dataInicioNegoc.value : null;
            const dataFim = dataFimNegoc ? dataFimNegoc.value : null;


            if (!valorTrocaDe || !valorTrocaPara || !qtd || saving === null || !tipo || !dataInicio || !dataFim) {
                console.warn("Negociação inválida ignorada:", {
                    valorTrocaDe,
                    valorTrocaPara,
                    qtd,
                    saving
                });
                return null; // Ignorar negociações inválidas
            }

            return {
                fk_id_int: fkIdInt,
                fk_usuario_neg: fkUsuarioNeg,
                troca_de: valorTrocaDe,
                troca_para: valorTrocaPara,
                qtd: qtd,
                saving: saving,
                tipo_negociacao: tipo,
                data_inicio_negoc: dataInicio,
                data_fim_negoc: dataFim
            };
        }).filter(Boolean);

        // Elimina duplicatas
        const uniqueNegotiations = Array.from(new Set(negotiations.map(JSON.stringify))).map(JSON.parse);

        // Gera o JSON
        const negotiationsJSON = JSON.stringify(uniqueNegotiations);

        document.getElementById("negociacoes_json").value = negotiationsJSON;
        saveNegotiations();
    }

    function saveNegotiations() {

        const fieldContainers = document.querySelectorAll(".negotiation-field-container");
        const negotiationEntries = Array.from(fieldContainers).map(container => ({
            troca_de: container.querySelector('[name="troca_de"]').value,
            troca_para: container.querySelector('[name="troca_para"]').value,
            qtd: container.querySelector('[name="qtd"]').value,
            saving: container.querySelector('[name="saving"]').value,
            tipo_negociacao: container.querySelector('[name="tipo_negociacao"]').value,
            data_inicio_negoc: container.querySelector('[name="data_inicio_negoc"]').value,
            data_fim_negoc: container.querySelector('[name="data_fim_negoc"]').value
        }));

    }


    // Adicione um evento no envio do formulário para garantir que o JSON seja gerado
    document.querySelector("form").addEventListener("submit", function (event) {
        generateNegotiationsJSON(); // Gera o JSON antes do envio
    });

    // Função para remover uma linha de negociação
    function removeNegotiationField(button) {
        $(button).closest('.negotiation-field-container').remove();
    }

    // Adicione um evento no envio do formulário para garantir que o JSON seja gerado
    document.querySelector("form").addEventListener("submit", function (event) {
        generateNegotiationsJSON(); // Gera o JSON antes do envio
        console.log("JSON gerado:", document.getElementById("negociacoes_json").value);
    });

    // Função para calcular o saving dinamicamente
    $(document).on('change keyup', 'select[name="troca_de"], select[name="troca_para"], input[name="qtd"]', function () {
        const container = $(this).closest('.negotiation-field-container');
        calculateSaving(container);
    });


    // Função para salvar negociações
    function saveNegotiations() {
        const fieldContainers = document.querySelectorAll(".negotiation-field-container");
        const negotiationEntries = Array.from(fieldContainers).map(container => ({
            troca_de: container.querySelector('[name="troca_de"]').value,
            troca_para: container.querySelector('[name="troca_para"]').value,
            qtd: container.querySelector('[name="qtd"]').value,
            saving: container.querySelector('[name="saving"]').value,
            tipo_negociacao: container.querySelector('[name="tipo_negociacao"]').value,
            data_inicio_negoc: container.querySelector('[name="data_inicio_negoc"]').value,
            data_fim_negoc: container.querySelector('[name="data_fim_negoc"]').value
        }));

    }

    document.querySelector("form").addEventListener("submit", function (event) {
        generateNegotiationsJSON();
        setTimeout(clearNegotiationFields, 1000); // Limpa os campos após o envio
    });

    function clearNegotiationFields() {
        document.querySelectorAll(".negotiation-field-container").forEach((container, index) => {
            if (index === 0) {
                container.querySelectorAll("input, select").forEach(el => {
                    if (!el.hasAttribute("readonly")) {
                        el.value = "";
                        el.removeAttribute("disabled"); // Garante que os campos não fiquem desativados

                    }
                });
            } else {
                container.remove();
            }
        });
    }

    function clearNegocInputs() {
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

    // Função para remover uma linha de negociação
    function removeNegotiationField(button) {
        $(button).closest('.negotiation-field-container').remove();
    }

    // Adicione um evento no envio do formulário para garantir que o JSON seja gerado
    document.querySelector("form").addEventListener("submit", function (event) {
        generateNegotiationsJSON(); // Gera o JSON antes do envio

    });

    // Função para calcular o saving dinamicamente
    $(document).on('change keyup', 'select[name="troca_de"], select[name="troca_para"], input[name="qtd"]', function () {
        const container = $(this).closest('.negotiation-field-container');
        calculateSaving(container);
    });


    // Função para salvar negociações
    function saveNegotiations() {
        const fieldContainers = document.querySelectorAll(".negotiation-field-container");
        const negotiationEntries = Array.from(fieldContainers).map(container => ({
            troca_de: container.querySelector('[name="troca_de"]').value,
            troca_para: container.querySelector('[name="troca_para"]').value,
            qtd: container.querySelector('[name="qtd"]').value,
            saving: container.querySelector('[name="saving"]').value,
            tipo_negociacao: container.querySelector('[name="tipo_negociacao"]').value,
            data_inicio_negoc: container.querySelector('[name="data_inicio_negoc"]').value,
            data_fim_negoc: container.querySelector('[name="data_fim_negoc"]').value
        }));

    }

    document.querySelector("form").addEventListener("submit", function (event) {
        generateNegotiationsJSON();
        setTimeout(clearNegotiationFields, 1000); // Limpa os campos após o envio
    });

    function clearNegotiationFields() {
        document.querySelectorAll(".negotiation-field-container").forEach((container, index) => {
            if (index === 0) {
                container.querySelectorAll("input, select").forEach(el => {
                    if (!el.hasAttribute("readonly")) {
                        el.value = "";
                        el.removeAttribute("disabled"); // Garante que os campos não fiquem desativados

                    }
                });
            } else {
                container.remove();
            }
        });
    }

    function clearNegocInputs() {
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous">
    </script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>

<!-- CSS do Bootstrap-Select -->
<link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/css/bootstrap-select.min.css">

<!-- JS do Bootstrap-Select -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/js/bootstrap-select.min.js"></script>