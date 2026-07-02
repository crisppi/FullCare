<?php
include_once("check_logado.php");

include_once("models/pagination.php");

$appBasePath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
if ($appBasePath === '.' || $appBasePath === '/') {
    $appBasePath = '';
}
$fullCareFavicon = $appBasePath . '/assets/fullcare-icon.png?v=' . (@filemtime(__DIR__ . '/../../assets/fullcare-icon.png') ?: time());

?>
<!DOCTYPE html>
<html lang="pt-br">
<script src="js/timeout.js"></script>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FullCare - Hospitais</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($fullCareFavicon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($fullCareFavicon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($fullCareFavicon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="./css/table_style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/timeout.js"></script>

</head>

<?php

$busca = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
$ativo_hosp = filter_input(INPUT_GET, 'ativo_hosp', FILTER_SANITIZE_SPECIAL_CHARS);
$bl = filter_input(INPUT_GET, 'bl', FILTER_SANITIZE_SPECIAL_CHARS);


include_once("formularios/form_list_hospital.php");
include_once("templates/footer.php");
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/stepper.js"></script>

</html>
