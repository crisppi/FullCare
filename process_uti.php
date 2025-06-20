<?php
require_once("globals.php");
require_once("db.php");
require_once("models/uti.php");
require_once("models/message.php");
require_once("dao/usuarioDao.php");
require_once("dao/utiDao.php");

$message = new Message($BASE_URL);
$userDao = new UserDAO($conn, $BASE_URL);
$utiDao = new utiDAO($conn, $BASE_URL);

// Resgata o tipo do formulário
$typeUTI = filter_input(INPUT_POST, "typeUTI");

// Resgata dados do usuário

if ($typeUTI == "createUTI") {

    // Receber os dados dos inputs
    $fk_internacao_uti = filter_input(INPUT_POST, "fk_internacao_uti");
    $fk_visita_uti = filter_input(INPUT_POST, "fk_visita_uti");
    $criterios_uti = filter_input(INPUT_POST, "criterios_uti");
    $data_alta_uti = filter_input(INPUT_POST, "data_alta_uti");
    $dva_uti = filter_input(INPUT_POST, "dva_uti");
    $data_internacao_uti = filter_input(INPUT_POST, "data_internacao_uti") ?: null;
    $especialidade_uti = filter_input(INPUT_POST, "especialidade_uti");
    $internacao_uti = filter_input(INPUT_POST, "internacao_uti");
    $internado_uti = filter_input(INPUT_POST, "internado_uti");
    $just_uti = filter_input(INPUT_POST, "just_uti");
    $motivo_uti = filter_input(INPUT_POST, "motivo_uti");
    $rel_uti = filter_input(INPUT_POST, "rel_uti");
    $saps_uti = filter_input(INPUT_POST, "saps_uti");
    $score_uti = filter_input(INPUT_POST, "score_uti");
    $vm_uti = filter_input(INPUT_POST, "vm_uti");
    $id_internacao = filter_input(INPUT_POST, "id_internacao");
    $internacao_uti_int = filter_input(INPUT_POST, "internacao_uti_int");
    $fk_user_uti = filter_input(INPUT_POST, "fk_user_uti");
    $glasgow_uti = filter_input(INPUT_POST, "glasgow_uti");
    $suporte_vent_uti = filter_input(INPUT_POST, "suporte_vent_uti");
    $dist_met_uti = filter_input(INPUT_POST, "dist_met_uti");
    $justifique_uti = filter_input(INPUT_POST, "dist_met_uti");

    $uti = new uti();

    // Validação mínima de dados
    if (3 < 4) {

        $uti->fk_internacao_uti = $fk_internacao_uti;
        $uti->fk_visita_uti = $fk_visita_uti;
        $uti->criterios_uti = $criterios_uti;
        $uti->data_alta_uti = $data_alta_uti;
        $uti->dva_uti = $dva_uti;
        $uti->data_internacao_uti = $data_internacao_uti;
        $uti->especialidade_uti = $especialidade_uti;
        $uti->internacao_uti = $internacao_uti;
        $uti->internado_uti = $internado_uti;
        $uti->just_uti = $just_uti;
        $uti->motivo_uti = $motivo_uti;
        $uti->rel_uti = $rel_uti;
        $uti->saps_uti = $saps_uti;
        $uti->score_uti = $score_uti;
        $uti->vm_uti = $vm_uti;
        $uti->id_internacao = $id_internacao;
        $uti->fk_user_uti = $fk_user_uti;
        $uti->internacao_uti_int = $internacao_uti_int;
        $uti->glasgow_uti = $glasgow_uti;
        $uti->suporte_vent_uti = $suporte_vent_uti;
        $uti->dist_met_uti = $dist_met_uti;
        $uti->justifique_uti = $justifique_uti;

        $utiDao->create($uti);
    } else {

        $message->setMessage("Você precisa adicionar pelo menos: Uti!", "error", "back");
    }

    include_once('list_internacao.php');
}
