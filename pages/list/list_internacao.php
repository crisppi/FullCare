<?php
require_once(__DIR__ . "/../../globals.php");
include_once("check_logado.php");

include_once("models/pagination.php");

include_once("formularios/form_list_internacao.php");

$pesquisa_nome = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
$pesqInternado = filter_input(INPUT_GET, 'pesqInternado', FILTER_SANITIZE_SPECIAL_CHARS);
$pesquisa_pac = filter_input(INPUT_GET, 'pesquisa_pac', FILTER_SANITIZE_SPECIAL_CHARS);
$bl = filter_input(INPUT_GET, 'bl', FILTER_SANITIZE_SPECIAL_CHARS);
