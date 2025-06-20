<?php
include_once("check_logado.php");

require_once("models/usuario.php");
require_once("models/acomodacao.php");
require_once("dao/usuarioDao.php");
require_once("dao/acomodacaoDao.php");
require_once("templates/header.php");

$user = new acomodacao();
$userDao = new UserDAO($conn, $BASE_URL);
$acomodacaoDao = new acomodacaoDAO($conn, $BASE_URL);

// Receber id do usuário
$id_acomodacao = filter_input(INPUT_GET, "id_acomodacao");

$acomodacao = $acomodacaoDao->joinAcomodacaoHospitalshow($id_acomodacao);

?>

<!-- formulario update -->
<!-- formulario update -->
<!-- formulario update -->
<div id="main-container" class="container">
    <div class="row">

        <form class="borderless" action="<?= $BASE_URL ?>process_acomodacao.php" id="add-acomodacao-form" method="POST"
            enctype="multipart/form-data">
            <input type="hidden" name="type" value="update">

            <div class="form-group row">
                <input type="hidden" class="form-control" id="id_acomodacao" name="id_acomodacao"
                    value="<?= $acomodacao['id_acomodacao'] ?>" placeholder="ID">

                <div class="form-group col-sm-6">
                    <label class="control-label" for="acomodacao_aco">Acomodação</label>
                    <select class="form-control" id="acomodacao_aco" name="acomodacao_aco">
                        <option value=<?= $acomodacao['acomodacao_aco'] ?>><?= $acomodacao['acomodacao_aco'] ?></option>
                        <option value="UTI">UTI</option>
                        <option value="Semi">Semi</option>
                        <option value="Apto">Apto</option>
                        <option value="Enfermaria">Enfermaria</option>
                        <option value="Uco">Uco</option>
                        <option value="Maternidade">Maternidade</option>
                        <option value="Berçário">Berçário</option>
                    </select>
                </div>

                <div class="form-group col-sm-6">
                    <label class="control-label" for="fk_hospital">Hospital</label>
                    <select class="form-control" id="fk_hospital" name="fk_hospital">
                        <option value="<?= $acomodacao['fk_hospital'] ?>"><?= $acomodacao['nome_hosp'] ?></option>
                        <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?= $hospital["id_hospital"] ?>"><?= $hospital["nome_hosp"] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="form-group col-sm-6">
                    <label for="valor_diaria">Valor Diária</label>
                    <input onkeyup="formatAcomod(event)" type="text" class="dinheiro form-control" id="valor_aco"
                        value="<?= $acomodacao['valor_aco'] ?>" name="valor_aco" placeholder="R$0,00" required>
                </div>

                <div class="form-group col-sm-6">
                    <label for="data_contrato_aco">Data contrato</label>
                    <input type="date" class="form-control" id="data_contrato_aco"
                        value="<?= $acomodacao['data_contrato_aco'] ?>" name="data_contrato_aco">
                </div>
            </div>

            <input type="hidden" class="form-control" id="usuario_update_acomodacao"
                value="<?= $_SESSION['email_user'] ?>" name="usuario_update_acomodacao">
            <input type="hidden" class="form-control" id="fk_usuario_acomodacao" value="<?= $_SESSION['id_usuario'] ?>"
                name="fk_usuario_acomodacao">

            <hr>
            <button type="submit" class="btn btn-success">
                <i style="font-size: 1rem;margin-right:5px;" name="type" value="edite"
                    class="fa-solid fa-check edit-icon"></i>Atualizar
            </button>
        </form>
    </div>
</div>



<script>
function mascara(i) {

    var v = i.value;

    if (isNaN(v[v.length - 1])) { // impede entrar outro caractere que não seja número
        i.value = v.substring(0, v.length - 1);
        return;
    }

    i.setAttribute("maxlength", "14");
    if (v.length == 3 || v.length == 7) i.value += ".";
    if (v.length == 11) i.value += "-";

}
</script>
<script>
function mascara(i, t) {

    var v = i.value;

    if (isNaN(v[v.length - 1])) {
        i.value = v.substring(0, v.length - 1);
        return;
    }

    if (t == "data") {
        i.setAttribute("maxlength", "10");
        if (v.length == 2 || v.length == 5) i.value += "/";
    }

    if (t == "cpf") {
        i.setAttribute("maxlength", "14");
        if (v.length == 3 || v.length == 7) i.value += ".";
        if (v.length == 11) i.value += "-";
    }

    if (t == "cnpj") {
        i.setAttribute("maxlength", "18");
        if (v.length == 2 || v.length == 6) i.value += ".";
        if (v.length == 10) i.value += "/";
        if (v.length == 15) i.value += "-";
    }

    if (t == "cep") {
        i.setAttribute("maxlength", "9");
        if (v.length == 5) i.value += "-";
    }

    if (t == "tel") {
        if (v[0] == 12) {

            i.setAttribute("maxlength", "10");
            if (v.length == 5) i.value += "-";
            if (v.length == 0) i.value += "(";

        } else {
            i.setAttribute("maxlength", "9");
            if (v.length == 4) i.value += "-";
        }
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


<?php
include_once("templates/footer.php");
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>

</html>