<?php
include_once("check_logado.php");

include_once("models/pagination.php");

?>
<!DOCTYPE html>
<html lang="pt-br">
<script src="js/timeout.js"></script>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/timeout.js"></script>
    <link rel="stylesheet" href="./css/table_style.css">
</head>

<?php

$busca = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
$ativo_user = filter_input(INPUT_GET, 'ativo_user', FILTER_SANITIZE_SPECIAL_CHARS);
$bl = filter_input(INPUT_GET, 'bl', FILTER_SANITIZE_SPECIAL_CHARS);

include_once("formularios/form_list_usuario.php");
include_once("templates/footer.php");
?>
<script>
function deletar() {
    let idAcoes = (document.getElementById('id-confirmacao'));
    idAcoes.style.display = 'none';
    window.location = "<?= $BASE_URL ?>del_usuario.php?id_usuario=<?= $id_usuario ?>";

};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>

</html>