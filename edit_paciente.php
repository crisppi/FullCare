<?php
include_once("check_logado.php");
include_once("templates/header.php");
include_once("models/message.php");

include_once("models/pacital.php");
include_once("dao/pacitalDao.php");

include_once("models/seguradora.php");
include_once("dao/seguradoraDao.php");

include_once("models/estipulante.php");
include_once("dao/estipulanteDao.php");

include_once("models/paciente.php");
include_once("dao/pacienteDao.php");

$seguradoraDao = new seguradoraDAO($conn, $BASE_URL);
$seguradoras = $seguradoraDao->findAll();

$estipulanteDao = new estipulanteDAO($conn, $BASE_URL);
$estipulantes = $estipulanteDao->findAll();

$user = new Paciente();
$pacienteDao = new pacienteDAO($conn, $BASE_URL);

// Receber id do usuário
$id_paciente = filter_input(INPUT_GET, "id_paciente");
$paciente = $pacienteDao->findById($id_paciente);
extract($paciente);

// Função para formatar CPF
function formatCpf($cpf)
{
    if (!empty($cpf)) {
        $cpf = preg_replace("/\D/", '', $cpf); // Remove caracteres não numéricos
        if (strlen($cpf) == 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
    }
    return $cpf;
}

// Função para formatar CEP
function formatCep($cep)
{
    if (!empty($cep)) {
        $cep = preg_replace("/\D/", '', $cep); // Remove caracteres não numéricos
        if (strlen($cep) == 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
        }
    }
    return $cep;
}

// Função para formatar telefone
function formatPhone($phone)
{
    if (!empty($phone)) {
        $phone = preg_replace("/\D/", '', $phone); // Remove caracteres não numéricos
        if (strlen($phone) == 11) {
            // Formato para celular (11 dígitos)
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7, 4);
        } elseif (strlen($phone) == 10) {
            // Formato para telefone fixo (10 dígitos)
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6, 4);
        }
    }
    return $phone;
}


// Recebendo e formatando as variáveis
$cep_pac = !empty($paciente['0']['cep_pac']) ? formatCep($paciente['0']['cep_pac']) : '';
$cpf_pac = !empty($paciente['0']['cpf_pac']) ? formatCpf($paciente['0']['cpf_pac']) : '';
$telefone01_pac = !empty($paciente['0']['telefone01_pac']) ? formatPhone($paciente['0']['telefone01_pac']) : '';
$telefone02_pac = !empty($paciente['0']['telefone02_pac']) ? formatPhone($paciente['0']['telefone02_pac']) : '';

?>

<!-- Incluindo o Font Awesome para os ícones -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="css/ocultar.css"></script>

<div class="container-fluid fundo_tela_cadastros" id="main-container">
    <div class="progress mb-4">
        <div class="progress-bar bg-success" role="progressbar" id="progressBar" style="width: 33%;" aria-valuenow="33"
            aria-valuemin="0" aria-valuemax="100">Etapa 1 de 3</div>
    </div>

    <form action="<?= $BASE_URL ?>process_paciente.php" id="multi-step-form" method="POST" enctype="multipart/form-data"
        class="needs-validation" novalidate>

        <input type="hidden" name="type" value="update">
        <input type="hidden" name="id_paciente" value="<?= $paciente['0']['id_paciente'] ?>">

        <!-- Step 1: Informações Pessoais -->
        <div id="step-1" class="step">
            <div class="row">
                <div class="form-group col-md-4 mb-3">
                    <label for="cpf_pac">CPF</label>
                    <input class="form-control" type="text" oninput="mascara(this, 'cpf')" value="<?= $cpf_pac ?>"
                        id="cpf_pac" name="cpf_pac" placeholder="000.000.000-00" required>
                    <div class="invalid-feedback">Por favor, insira um CPF válido.</div>
                </div>
                <div class="form-group col-md-8 mb-3">
                    <label for="nome_pac">Nome</label>
                    <input type="text" class="form-control" id="nome_pac" name="nome_pac"
                        value="<?= $paciente['0']['nome_pac'] ?>" required>
                    <div class="invalid-feedback">Por favor, insira o nome.</div>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-4 mb-3">
                    <label for="data_nasc_pac">Nascimento</label>
                    <input type="date" class="form-control" id="data_nasc_pac" name="data_nasc_pac"
                        value="<?= $paciente['0']['data_nasc_pac'] ?>" required>
                    <div class="invalid-feedback">Por favor, insira a data de nascimento.</div>
                </div>
                <div class="form-group col-md-8 mb-3">
                    <label for="nome_social_pac">Nome Social</label>
                    <input type="text" class="form-control" id="nome_social_pac" name="nome_social_pac"
                        value="<?= $paciente['0']['nome_social_pac'] ?>">
                </div>

            </div>

            <div class="row">
                <div class="form-group col-md-4 mb-3">
                    <label for="sexo_pac">Sexo</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="sexo_pac" id="sexo_m" value="m"
                                <?= $paciente['0']['sexo_pac'] == 'm' ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="sexo_m">Masculino</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="sexo_pac" id="sexo_f" value="f"
                                <?= $paciente['0']['sexo_pac'] == 'f' ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="sexo_f">Feminino</label>
                        </div>
                    </div>
                    <div class="invalid-feedback">Por favor, selecione o sexo.</div>
                </div>
                <div class="form-group col-md-8 mb-3">
                    <label for="mae_pac">Mãe</label>
                    <input type="text" class="form-control" id="mae_pac" name="mae_pac"
                        value="<?= $paciente['0']['mae_pac'] ?>">
                </div>

            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>

                <!-- Div de confirmação, oculta inicialmente -->
                <div id="confirm-delete-div" style="font-weight: bold" class="oculto">

                    <div class="d-flex flex-column align-items-center px-3 my-3"
                        style="background-color: #f1f1f1; border-radius: 10px; border: 1px solid #ddd; display: none;">

                        <!-- Texto centralizado acima dos botões -->
                        <p style="font-weight: bold;" class="mb-2 text-center">Confirma Deletar?</p>

                        <!-- Botões de confirmação e cancelamento -->
                        <div class="d-flex justify-content-center mb-3">
                            <button type="button" class="btn btn-success mx-2" onclick="confirmAction()">
                                Sim <i class="fas fa-check"></i>
                            </button>

                            <button type="button" class="btn btn-danger mx-2" onclick="hideConfirmDelete()">
                                Não <i class="fas fa-ban"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-danger" onclick="showConfirmDelete()">
                    Deletar <i class="fas fa-times"></i>
                </button>
            </div>



        </div>

        <!-- Step 2: Informações de Endereço -->
        <div id="step-2" class="step" style="display:none;">
            <div class="row">
                <div class="form-group col-md-3 mb-3">
                    <label for="cep_pac">CEP</label>
                    <input type="text" oninput="mascara(this, 'cep')" onkeyup="consultarCEP(this, 'pac')"
                        value="<?= $cep_pac ?>" class="form-control" id="cep_pac" name="cep_pac" placeholder="00000-000"
                        required>
                    <div class="invalid-feedback">Por favor, insira o CEP.</div>
                </div>
                <div class="form-group col-md-9 mb-3">
                    <label for="endereco_pac">Endereço</label>
                    <input readonly type="text" class="form-control" value="<?= $paciente['0']['endereco_pac'] ?>"
                        id="endereco_pac" name="endereco_pac">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="bairro_pac">Bairro</label>
                    <input readonly type="text" class="form-control" value="<?= $paciente['0']['bairro_pac'] ?>"
                        id="bairro_pac" name="bairro_pac">
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="cidade_pac">Cidade</label>
                    <input readonly type="text" class="form-control" id="cidade_pac"
                        value="<?= $paciente['0']['cidade_pac'] ?>" name="cidade_pac">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="estado_pac">Estado</label>
                    <select readonly class="form-control" id="estado_pac" name="estado_pac">
                        <option value="<?= $paciente['0']['estado_pac'] ?>"><?= $paciente['0']['estado_pac'] ?>
                        </option>
                        <?php foreach ($estado_sel as $estado): ?>
                        <option value="<?= $estado ?>"><?= $estado ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="numero_pac">Número</label>
                    <input type="text" class="form-control" id="numero_pac" value="<?= $paciente['0']['numero_pac'] ?>"
                        name="numero_pac">
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="complemento_pac">Complemento</label>
                <input type="text" class="form-control" id="complemento_pac" name="complemento_pac"
                    value="<?= $paciente['0']['complemento_pac'] ?>">
            </div>
            <hr>
            <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                <i class="fas fa-arrow-left"></i> Voltar
            </button>
            <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                Próximo <i class="fas fa-arrow-right"></i>
            </button>
        </div>

        <!-- Step 3: Informações de Contato -->
        <div id="step-3" class="step" style="display:none;">
            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="email01_pac">Email Principal</label>
                    <input type="email" class="form-control" id="email01_pac" name="email01_pac"
                        value="<?= $paciente['0']['email01_pac'] ?>" placeholder="exemplo@dominio.com" required>
                    <div class="invalid-feedback">Por favor, insira um email válido.</div>
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="email02_pac">Email Alternativo</label>
                    <input type="email" class="form-control" id="email02_pac" name="email02_pac"
                        value="<?= $paciente['0']['email02_pac'] ?>" placeholder="exemplo@dominio.com">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="telefone01_pac">Telefone</label>
                    <input type="text" onkeydown="return mascaraTelefone(event)" class="form-control"
                        id="telefone01_pac" value="<?= $telefone01_pac ?>" name="telefone01_pac"
                        placeholder="(00) 0000-0000">
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="telefone02_pac">Celular</label>
                    <input type="text" onkeydown="return mascaraTelefone(event)" class="form-control"
                        id="telefone02_pac" value="<?= $telefone02_pac ?>" name="telefone02_pac"
                        placeholder="(00) 00000-0000" required>
                    <div class="invalid-feedback">Por favor, insira um número de celular válido.</div>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="fk_seguradora_pac">Seguradora</label>
                    <select class="form-control" id="fk_seguradora_pac" name="fk_seguradora_pac">
                        <option value="<?= $paciente['0']['fk_seguradora_pac'] ?>" selected>
                            <?= $paciente['0']['seguradora_seg'] ?>
                        </option>
                        <?php foreach ($seguradoras as $seguradora): ?>
                        <option value="<?= $seguradora['id_seguradora'] ?>"><?= $seguradora['seguradora_seg'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="fk_seguradora_pac">Estipulante</label>
                    <select class="form-control" id="fk_estipulante_pac" name="fk_estipulante_pac">
                        <option value="<?= $paciente['0']['fk_estipulante_pac'] ?>" selected>
                            <?= $paciente['0']['nome_est'] ?>
                        </option>
                        <?php foreach ($estipulantes as $estipulantes): ?>
                        <option value="<?= $estipulantes['id_estipulante'] ?>"><?= $estipulantes['nome_est'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="matricula_pac">Matrícula</label>
                    <input type="text" class="form-control" id="matricula_pac" name="matricula_pac"
                        value="<?= $paciente['0']['matricula_pac'] ?>">
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="obs_pac">Observações</label>
                <textarea rows="5" class="form-control" id="obs_pac"
                    name="obs_pac"><?= $paciente['0']['obs_pac'] ?></textarea>
            </div>
            <hr>
            <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                <i class="fas fa-arrow-left"></i> Voltar
            </button>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> Atualizar
            </button>
        </div>

        <script>
        // Função para mostrar a div de confirmação
        function showConfirmDelete() {
            const confirmDiv = document.getElementById("confirm-delete-div");
            if (confirmDiv) {
                confirmDiv.style.display = "flex";
            }
        }

        // Função para ocultar a div de confirmação
        function hideConfirmDelete() {
            const confirmDiv = document.getElementById("confirm-delete-div");
            if (confirmDiv) {
                confirmDiv.style.display = "none";
            }
        }

        // Função para confirmar a exclusão
        function confirmAction() {
            hideConfirmDelete(); // Oculta a div de confirmação

            // Inicia o processo de exclusão
            const form = document.getElementById("multi-step-form");
            form.action = "<?= $BASE_URL ?>process_paciente.php";

            // Adiciona campos ocultos para o processo de deletar
            const inputType = document.createElement("input");
            inputType.type = "hidden";
            inputType.name = "type";
            inputType.value = "delUpdate";
            form.appendChild(inputType);

            const inputDeleted = document.createElement("input");
            inputDeleted.type = "hidden";
            inputDeleted.name = "deletado_pac";
            inputDeleted.value = "s";
            form.appendChild(inputDeleted);

            // Envia o formulário
            form.submit();
        }
        </script>

    </form>
</div>

<script>
function mascara(i, t) {
    var v = i.value;
    if (isNaN(v[v.length - 1])) {
        i.value = v.substring(0, v.length - 1);
        return;
    }
    if (t == "cpf") {
        i.setAttribute("maxlength", "14");
        if (v.length == 3 || v.length == 7) i.value += ".";
        if (v.length == 11) i.value += "-";
    }
    if (t == "cep") {
        i.setAttribute("maxlength", "9");
        if (v.length == 5) i.value += "-";
    }
}

function mascaraTelefone(event) {
    let tecla = event.key;
    let telefone = event.target.value.replace(/\D+/g, "");
    if (/^[0-9]$/i.test(tecla)) {
        telefone = telefone + tecla;
        let tamanho = telefone.length;
        if (tamanho >= 12) {
            return false;
        }
        if (tamanho > 10) {
            telefone = telefone.replace(/^(\d\d)(\d{5})(\d{4}).*/, "($1) $2-$3");
        } else if (tamanho > 5) {
            telefone = telefone.replace(/^(\d\d)(\d{4})(\d{0,4}).*/, "($1) $2-$3");
        } else if (tamanho > 2) {
            telefone = telefone.replace(/^(\d\d)(\d{0,5})/, "($1) $2");
        } else {
            telefone = telefone.replace(/^(\d*)/, "($1");
        }
        event.target.value = telefone;
    }
    if (!["Backspace", "Delete"].includes(tecla)) {
        return false;
    }
}
</script>

<?php include_once("templates/footer.php"); ?>