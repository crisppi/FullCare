<?php
require_once(__DIR__ . "/../../globals.php");
include_once("check_logado.php");

include_once("models/pagination.php");
$assetBaseUrl = rtrim((string)$BASE_URL, '/') . '/';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="<?= htmlspecialchars($assetBaseUrl . 'js/timeout.js?v=' . (string)@filemtime(__DIR__ . '/../../js/timeout.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBaseUrl . 'css/table_style.css?v=' . (string)@filemtime(__DIR__ . '/../../css/table_style.css'), ENT_QUOTES, 'UTF-8') ?>">

</head>

<body>
    <div>
        <?php
        include_once("formularios/form_list_internacao.php");
        $pesquisa_nome = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
        $pesqInternado = filter_input(INPUT_GET, 'pesqInternado', FILTER_SANITIZE_SPECIAL_CHARS);
        $pesquisa_pac = filter_input(INPUT_GET, 'pesquisa_pac', FILTER_SANITIZE_SPECIAL_CHARS);
        $bl = filter_input(INPUT_GET, 'bl', FILTER_SANITIZE_SPECIAL_CHARS);


        ?>
    </div>
</body>

<script src="<?= htmlspecialchars($assetBaseUrl . 'js/scriptDataAltaHospitalar.js?v=' . (string)@filemtime(__DIR__ . '/../../js/scriptDataAltaHospitalar.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

</html>
