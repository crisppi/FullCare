<?php

require_once("globals.php");
require_once("db.php");

require_once("models/capeante.php");
require_once("dao/capeanteDao.php");

require_once("models/message.php");
require_once("dao/usuarioDao.php");

$message = new Message($BASE_URL);

$capeanteDao = new capeanteDAO($conn, $BASE_URL);

// Resgata o tipo do formulário
$type = filter_input(INPUT_POST, "type");

// Função para limpar os campos de valores e glosas
function limparCampo($valor)
{
    $valor = str_replace('R$', '', $valor);      // Remove o símbolo de moeda
    $valor = str_replace('.', '', $valor);       // Remove o ponto
    $valor = str_replace(',', '.', $valor);      // Converte a vírgula para ponto
    $valor = preg_replace('/\s+/', '', $valor);  // Remove espaços em branco
    return $valor;
}

if ($type === "create") {
    // Receber os dados dos inputs
    $adm_capeante = filter_input(INPUT_POST, "adm_capeante");
    $aud_enf_capeante = filter_input(INPUT_POST, "aud_enf_capeante");
    $aud_med_capeante = filter_input(INPUT_POST, "aud_med_capeante");

    $fk_int_capeante = filter_input(INPUT_POST, "fk_int_capeante");
    $fk_user_cap = filter_input(INPUT_POST, "fk_user_cap");

    $data_inicial_capeante = filter_input(INPUT_POST, "data_inicial_capeante") ?: null;
    $data_final_capeante = filter_input(INPUT_POST, "data_final_capeante") ?: null;
    $data_fech_capeante = filter_input(INPUT_POST, "data_fech_capeante") ?: null;
    $diarias_capeante = filter_input(INPUT_POST, "diarias_capeante");
    $lote_cap = filter_input(INPUT_POST, "lote_cap");

    $glosa_diaria = limparCampo(filter_input(INPUT_POST, "glosa_diaria"));
    $glosa_honorarios = limparCampo(filter_input(INPUT_POST, "glosa_honorarios"));
    $glosa_matmed = limparCampo(filter_input(INPUT_POST, "glosa_matmed"));
    $glosa_oxig = limparCampo(filter_input(INPUT_POST, "glosa_oxig"));
    $glosa_sadt = limparCampo(filter_input(INPUT_POST, "glosa_sadt"));
    $glosa_taxas = limparCampo(filter_input(INPUT_POST, "glosa_taxas"));
    $glosa_opme = limparCampo(filter_input(INPUT_POST, "glosa_opme"));

    $adm_check = filter_input(INPUT_POST, "adm_check") ?: 'n';
    $med_check = filter_input(INPUT_POST, "med_check") ?: 'n';
    $enfer_check = filter_input(INPUT_POST, "enfer_check") ?: 'n';

    $pacote = filter_input(INPUT_POST, "pacote") ?: "n";
    $parcial_capeante = filter_input(INPUT_POST, "parcial_capeante") ?: "n";
    $parcial_num = filter_input(INPUT_POST, "parcial_num");
    $fk_int_capeante = filter_input(INPUT_POST, "fk_int_capeante");
    $senha_finalizada = filter_input(INPUT_POST, "senha_finalizada") ?: "n";
    $em_auditoria_cap = filter_input(INPUT_POST, "em_auditoria_cap") ?: "n";
    $negociado_desconto_cap = filter_input(INPUT_POST, "negociado_desconto_cap");
    $desconto_valor_cap = filter_input(INPUT_POST, "desconto_valor_cap") ?: NULL;

    $conta_parada_cap = filter_input(INPUT_POST, "conta_parada_cap") ?: NULL;
    $parada_motivo_cap = filter_input(INPUT_POST, "parada_motivo_cap") ?: NULL;

    $valor_apresentado_capeante = limparCampo(filter_input(INPUT_POST, "valor_apresentado_capeante"));
    $valor_final_capeante = limparCampo(filter_input(INPUT_POST, "valor_final_capeante"));
    $valor_diarias = limparCampo(filter_input(INPUT_POST, "valor_diarias"));
    $valor_matmed = limparCampo(filter_input(INPUT_POST, "valor_matmed"));
    $valor_oxig = limparCampo(filter_input(INPUT_POST, "valor_oxig"));
    $valor_sadt = limparCampo(filter_input(INPUT_POST, "valor_sadt"));
    $valor_taxa = limparCampo(filter_input(INPUT_POST, "valor_taxa"));
    $valor_honorarios = limparCampo(filter_input(INPUT_POST, "valor_honorarios"));
    $valor_opme = limparCampo(filter_input(INPUT_POST, "valor_opme"));

    $valor_glosa_enf = limparCampo(filter_input(INPUT_POST, "valor_glosa_enf"));
    $valor_glosa_med = limparCampo(filter_input(INPUT_POST, "valor_glosa_med"));
    $valor_glosa_total = limparCampo(filter_input(INPUT_POST, "valor_glosa_total"));

    $fk_user_cap = filter_input(INPUT_POST, "fk_user_cap");
    $usuario_create_cap = filter_input(INPUT_POST, "usuario_create_cap");
    $data_create_cap = filter_input(INPUT_POST, "data_create_cap");

    $fk_id_aud_enf = filter_input(INPUT_POST, "fk_id_aud_enf");
    $fk_id_aud_med = filter_input(INPUT_POST, "fk_id_aud_med");
    $fk_id_aud_adm = filter_input(INPUT_POST, "fk_id_aud_adm");
    $fk_id_aud_hosp = filter_input(INPUT_POST, "fk_id_aud_hosp");

    $checkbox_imprimir = filter_input(INPUT_POST, "checkbox_imprimir");

    $last_cap = 1;

    $capeante = new capeante();

    // Validação mínima de dados
    if (!empty(3 < 4)) {

        $capeante->adm_capeante = $adm_capeante;
        $capeante->adm_check = $adm_check;
        $capeante->aud_enf_capeante = $aud_enf_capeante;
        $capeante->aud_med_capeante = $aud_med_capeante;
        $capeante->data_fech_capeante = $data_fech_capeante;
        $capeante->data_final_capeante = $data_final_capeante;
        $capeante->data_inicial_capeante = $data_inicial_capeante;
        $capeante->diarias_capeante = $diarias_capeante;
        $capeante->lote_cap = $lote_cap;
        $capeante->glosa_diaria = $glosa_diaria;
        $capeante->glosa_honorarios = $glosa_honorarios;
        $capeante->glosa_matmed = $glosa_matmed;
        $capeante->glosa_oxig = $glosa_oxig;
        $capeante->glosa_sadt = $glosa_sadt;
        $capeante->glosa_taxas = $glosa_taxas;
        $capeante->glosa_opme = $glosa_opme;
        $capeante->med_check = $med_check;
        $capeante->enfer_check = $enfer_check;
        $capeante->pacote = $pacote;
        $capeante->parcial_capeante = $parcial_capeante;
        $capeante->parcial_num = $parcial_num;
        $capeante->fk_int_capeante = $fk_int_capeante;
        $capeante->fk_user_cap = $fk_user_cap;
        $capeante->valor_apresentado_capeante = $valor_apresentado_capeante;
        $capeante->valor_diarias = $valor_diarias;
        $capeante->valor_final_capeante = $valor_final_capeante;
        $capeante->valor_glosa_enf = $valor_glosa_enf;
        $capeante->valor_glosa_med = $valor_glosa_med;
        $capeante->valor_glosa_total = $valor_glosa_total;
        $capeante->valor_honorarios = $valor_honorarios;
        $capeante->valor_matmed = $valor_matmed;
        $capeante->valor_oxig = $valor_oxig;
        $capeante->valor_sadt = $valor_sadt;
        $capeante->valor_taxa = $valor_taxa;
        $capeante->valor_opme = $valor_opme;
        $capeante->senha_finalizada = $senha_finalizada;
        $capeante->em_auditoria_cap = $em_auditoria_cap;
        $capeante->desconto_valor_cap = $desconto_valor_cap;
        $capeante->negociado_desconto_cap = $negociado_desconto_cap;
        $capeante->conta_parada_cap = $conta_parada_cap;
        $capeante->parada_motivo_cap = $parada_motivo_cap;

        $capeante->fk_user_cap = $fk_user_cap;
        $capeante->usuario_create_cap = $usuario_create_cap;
        $capeante->data_create_cap = $data_create_cap;
        $capeante->last_cap = $last_cap;

        $capeante->fk_id_aud_enf = $fk_id_aud_enf;
        $capeante->fk_id_aud_med = $fk_id_aud_med;
        $capeante->fk_id_aud_adm = $fk_id_aud_adm;
        $capeante->fk_id_aud_hosp = $fk_id_aud_hosp;

        $capeanteDao->create($capeante);
    }
    header('location: list_internacao_cap.php');
}

if ($type === "update") {
    // Receber os dados dos inputs
    $id_capeante = filter_input(INPUT_POST, "id_capeante");
    $adm_capeante = filter_input(INPUT_POST, "adm_capeante") ?: null;
    $aud_enf_capeante = filter_input(INPUT_POST, "aud_enf_capeante");
    $aud_med_capeante = filter_input(INPUT_POST, "aud_med_capeante");

    $fk_int_capeante = filter_input(INPUT_POST, "fk_int_capeante");
    $fk_user_cap = filter_input(INPUT_POST, "fk_user_cap");

    $data_inicial_capeante = filter_input(INPUT_POST, "data_inicial_capeante") ?: null;
    $data_fech_capeante = filter_input(INPUT_POST, "data_fech_capeante") ?: null;
    $data_final_capeante = filter_input(INPUT_POST, "data_final_capeante") ?: null;
    $diarias_capeante = filter_input(INPUT_POST, "diarias_capeante");
    $lote_cap = filter_input(INPUT_POST, "lote_cap");

    $glosa_diaria = limparCampo(filter_input(INPUT_POST, "glosa_diaria"));
    $glosa_honorarios = limparCampo(filter_input(INPUT_POST, "glosa_honorarios"));
    $glosa_matmed = limparCampo(filter_input(INPUT_POST, "glosa_matmed"));
    $glosa_oxig = limparCampo(filter_input(INPUT_POST, "glosa_oxig"));
    $glosa_sadt = limparCampo(filter_input(INPUT_POST, "glosa_sadt"));
    $glosa_taxas = limparCampo(filter_input(INPUT_POST, "glosa_taxas"));
    $glosa_opme = limparCampo(filter_input(INPUT_POST, "glosa_opme"));

    $adm_check = filter_input(INPUT_POST, "adm_check") ?: 'n';
    $med_check = filter_input(INPUT_POST, "med_check") ?: 'n';
    $enfer_check = filter_input(INPUT_POST, "enfer_check") ?: 'n';

    $pacote = filter_input(INPUT_POST, "pacote") ?: "n";
    $parcial_capeante = filter_input(INPUT_POST, "parcial_capeante") ?: "n";
    $parcial_num = filter_input(INPUT_POST, "parcial_num");
    $fk_int_capeante = filter_input(INPUT_POST, "fk_int_capeante");
    $negociado_desconto_cap = filter_input(INPUT_POST, "negociado_desconto_cap");
    $desconto_valor_cap = filter_input(INPUT_POST, "desconto_valor_cap") ?: NULL;
    $senha_finalizada = filter_input(INPUT_POST, "senha_finalizada") ?: "n";
    $em_auditoria_cap = filter_input(INPUT_POST, "em_auditoria_cap");
    $encerrado_cap = filter_input(INPUT_POST, "encerrado_cap");
    $aberto_cap = filter_input(INPUT_POST, "aberto_cap");

    $conta_parada_cap = filter_input(INPUT_POST, "conta_parada_cap") ?: NULL;
    $parada_motivo_cap = filter_input(INPUT_POST, "parada_motivo_cap") ?: NULL;

    $valor_apresentado_capeante = limparCampo(filter_input(INPUT_POST, "valor_apresentado_capeante"));
    $valor_final_capeante = limparCampo(filter_input(INPUT_POST, "valor_final_capeante"));
    $valor_diarias = limparCampo(filter_input(INPUT_POST, "valor_diarias"));
    $valor_matmed = limparCampo(filter_input(INPUT_POST, "valor_matmed"));
    $valor_oxig = limparCampo(filter_input(INPUT_POST, "valor_oxig"));
    $valor_sadt = limparCampo(filter_input(INPUT_POST, "valor_sadt"));
    $valor_taxa = limparCampo(filter_input(INPUT_POST, "valor_taxa"));
    $valor_honorarios = limparCampo(filter_input(INPUT_POST, "valor_honorarios"));
    $valor_opme = limparCampo(filter_input(INPUT_POST, "valor_opme"));

    $valor_glosa_enf = limparCampo(filter_input(INPUT_POST, "valor_glosa_enf"));
    $valor_glosa_med = limparCampo(filter_input(INPUT_POST, "valor_glosa_med"));
    $valor_glosa_total = limparCampo(filter_input(INPUT_POST, "valor_glosa_total"));

    $fk_user_cap = filter_input(INPUT_POST, "fk_user_cap");
    $usuario_create_cap = filter_input(INPUT_POST, "usuario_create_cap");
    $data_create_cap = filter_input(INPUT_POST, "data_create_cap");

    $fk_id_aud_enf = filter_input(INPUT_POST, "fk_id_aud_enf");
    $fk_id_aud_med = filter_input(INPUT_POST, "fk_id_aud_med");
    $fk_id_aud_adm = filter_input(INPUT_POST, "fk_id_aud_adm");
    $fk_id_aud_hosp = filter_input(INPUT_POST, "fk_id_aud_hosp");
    $checkbox_imprimir = filter_input(INPUT_POST, "checkbox_imprimir");

    $capeanteUpdate = new capeante();

    // Validação mínima de dados
    if (!empty(3 < 4)) {

        $capeanteUpdate->adm_capeante = $adm_capeante;
        $capeanteUpdate->adm_check = $adm_check;
        $capeanteUpdate->aud_enf_capeante = $aud_enf_capeante;
        $capeanteUpdate->aud_med_capeante = $aud_med_capeante;

        $capeanteUpdate->data_fech_capeante = $data_fech_capeante;
        $capeanteUpdate->data_final_capeante = $data_final_capeante;
        $capeanteUpdate->data_inicial_capeante = $data_inicial_capeante;
        $capeanteUpdate->diarias_capeante = $diarias_capeante;
        $capeanteUpdate->lote_cap = $lote_cap;

        $capeanteUpdate->glosa_diaria = $glosa_diaria;
        $capeanteUpdate->glosa_honorarios = $glosa_honorarios;
        $capeanteUpdate->glosa_matmed = $glosa_matmed;
        $capeanteUpdate->glosa_oxig = $glosa_oxig;
        $capeanteUpdate->glosa_sadt = $glosa_sadt;
        $capeanteUpdate->glosa_taxas = $glosa_taxas;
        $capeanteUpdate->glosa_opme = $glosa_opme;

        $capeanteUpdate->med_check = $med_check;
        $capeanteUpdate->enfer_check = $enfer_check;

        $capeanteUpdate->pacote = $pacote;
        $capeanteUpdate->parcial_capeante = $parcial_capeante;
        $capeanteUpdate->parcial_num = $parcial_num;
        $capeanteUpdate->fk_int_capeante = $fk_int_capeante;

        $capeanteUpdate->senha_finalizada = $senha_finalizada;
        $capeanteUpdate->em_auditoria_cap = $em_auditoria_cap;
        $capeanteUpdate->encerrado_cap = $encerrado_cap;
        $capeanteUpdate->aberto_cap = $aberto_cap;

        $capeanteUpdate->valor_apresentado_capeante = $valor_apresentado_capeante;
        $capeanteUpdate->negociado_desconto_cap = $negociado_desconto_cap;
        $capeanteUpdate->desconto_valor_cap = $desconto_valor_cap;

        $capeanteUpdate->conta_parada_cap = $conta_parada_cap;
        $capeanteUpdate->parada_motivo_cap = $parada_motivo_cap;

        $capeanteUpdate->valor_glosa_enf = $valor_glosa_enf;
        $capeanteUpdate->valor_glosa_med = $valor_glosa_med;
        $capeanteUpdate->valor_glosa_total = $valor_glosa_total;
        $capeanteUpdate->valor_final_capeante = $valor_final_capeante;

        $capeanteUpdate->valor_diarias = $valor_diarias;
        $capeanteUpdate->valor_honorarios = $valor_honorarios;
        $capeanteUpdate->valor_matmed = $valor_matmed;
        $capeanteUpdate->valor_oxig = $valor_oxig;
        $capeanteUpdate->valor_sadt = $valor_sadt;
        $capeanteUpdate->valor_taxa = $valor_taxa;
        $capeanteUpdate->valor_opme = $valor_opme;

        $capeanteUpdate->id_capeante = $id_capeante;

        $capeanteUpdate->fk_user_cap = $fk_user_cap;
        $capeanteUpdate->usuario_create_cap = $usuario_create_cap;
        $capeanteUpdate->data_create_cap = $data_create_cap;

        // $capeanteUpdate->impresso_cap = $impresso_cap;
        $capeanteUpdate->fk_id_aud_enf = $fk_id_aud_enf;
        $capeanteUpdate->fk_id_aud_med = $fk_id_aud_med;
        $capeanteUpdate->fk_id_aud_adm = $fk_id_aud_adm;
        $capeanteUpdate->fk_id_aud_hosp = $fk_id_aud_hosp;
        error_log("Atualizando capeante:" . print_r($capeanteUpdate, true));
        $capeanteDao->update($capeanteUpdate);
    }
    if ($checkbox_imprimir == '1') {
        header('location: show_capeantePrt.php?id_capeante=' . $id_capeante);
    } else {
        header('location: list_internacao_cap.php');
    }
}