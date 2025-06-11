<?php

require_once("globals.php");
require_once("db.php");

require_once("models/internacao.php");
require_once("dao/internacaoDao.php");

require_once("models/gestao.php");
require_once("dao/gestaoDao.php");

require_once("models/tuss.php");
require_once("dao/tussDao.php");

require_once("models/uti.php");
require_once("dao/utiDao.php");

require_once("models/negociacao.php");
require_once("dao/negociacaoDao.php");

require_once("models/visita.php");
require_once("dao/visitaDao.php");

require_once("models/prorrogacao.php");
require_once("dao/prorrogacaoDao.php");

require_once("models/message.php");

require_once("models/usuario.php");
require_once("dao/usuarioDao.php");

require_once("models/internacao_antecedente.php");
require_once("dao/internacaoAntecedenteDao.php");

// $message = new Message($BASE_URL);
$userDao = new UserDAO($conn, $BASE_URL);
$internacaoDao = new InternacaoDAO($conn, $BASE_URL);

$gestaoDao = new gestaoDAO($conn, $BASE_URL);
$utiDao = new utiDAO($conn, $BASE_URL);
$negociacaoDao = new negociacaoDAO($conn, $BASE_URL);
$tussDao = new tussDAO($conn, $BASE_URL);
$prorrogacaoDao = new prorrogacaoDAO($conn, $BASE_URL);
$userDao = new UserDAO($conn, $BASE_URL);
$visitaDao = new visitaDAO($conn, $BASE_URL);

$message = new Message($BASE_URL);
$internAntecedenteDao = new InternacaoAntecedenteDAO($conn, $BASE_URL);
// Resgata o tipo do formulário
$type = filter_input(INPUT_POST, "type");

// Resgata dados da visita
if ($type === "create") {

    // Receber os dados dos inputs
    $fk_internacao_vis = filter_input(INPUT_POST, "fk_internacao_vis");
    $usuario_create = filter_input(INPUT_POST, "usuario_create");
    $rel_visita_vis = filter_input(INPUT_POST, "rel_visita_vis");
    $acoes_int_vis = filter_input(INPUT_POST, "acoes_int_vis");
    $data_visita_vis = filter_input(INPUT_POST, "data_visita_vis");
    $visita_no_vis = filter_input(INPUT_POST, "visita_no_vis");
    $visita_enf_vis = filter_input(INPUT_POST, "visita_enf_vis");
    $visita_med_vis = filter_input(INPUT_POST, "visita_med_vis");
    $visita_auditor_prof_enf = filter_input(INPUT_POST, "visita_auditor_prof_enf");
    $visita_auditor_prof_med = filter_input(INPUT_POST, "visita_auditor_prof_med");

    //inputs da visita enfermagem
    $exames_enf = filter_input(INPUT_POST, "exames_enf");
    $oportunidades_enf = filter_input(INPUT_POST, "oportunidades_enf");
    $programacao_enf = filter_input(INPUT_POST, "programacao_enf");

    // Receber os dados dos inputs TUSS - bloco 1
    $select_tuss = filter_input(INPUT_POST, "select_tuss");
    $fk_int_tuss = filter_input(INPUT_POST, "fk_int_tuss");
    $tuss_liberado_sn = filter_input(INPUT_POST, "tuss_liberado_sn");
    $tuss_solicitado = filter_input(INPUT_POST, "tuss_solicitado");
    $qtd_tuss_solicitado = filter_input(INPUT_POST, "qtd_tuss_solicitado");
    $qtd_tuss_liberado = filter_input(INPUT_POST, "qtd_tuss_liberado");
    $data_realizacao_tuss = filter_input(INPUT_POST, "data_realizacao_tuss");

    // pegar dados input da gestao
    $select_gestao = filter_input(INPUT_POST, "select_gestao");
    $fk_internacao_ges = filter_input(INPUT_POST, "fk_internacao_ges");
    $fk_visita_ges = filter_input(INPUT_POST, "fk_visita_ges");

    $alto_custo_ges = filter_input(INPUT_POST, "alto_custo_ges");
    $rel_alto_custo_ges = filter_input(INPUT_POST, "rel_alto_custo_ges");

    $opme_ges = filter_input(INPUT_POST, "opme_ges");
    $rel_opme_ges = filter_input(INPUT_POST, "rel_opme_ges");
    $home_care_ges = filter_input(INPUT_POST, "home_care_ges");
    $rel_home_care_ges = filter_input(INPUT_POST, "rel_home_care_ges");

    $desospitalizacao_ges = filter_input(INPUT_POST, "desospitalizacao_ges");
    $rel_desospitalizacao_ges = filter_input(INPUT_POST, "rel_desospitalizacao_ges");

    $fk_user_ges = filter_input(INPUT_POST, "fk_user_ges");

    $evento_adverso_ges = filter_input(INPUT_POST, "evento_adverso_ges");
    $rel_evento_adverso_ges = filter_input(INPUT_POST, "rel_evento_adverso_ges");
    $tipo_evento_adverso_gest = filter_input(INPUT_POST, "tipo_evento_adverso_gest");
    $evento_sinalizado_ges = filter_input(INPUT_POST, "evento_sinalizado_ges");
    $evento_discutido_ges = filter_input(INPUT_POST, "evento_discutido_ges");
    $evento_retorno_qual_hosp_ges = filter_input(INPUT_POST, "evento_retorno_qual_hosp_ges");
    $evento_classificado_hospital_ges = filter_input(INPUT_POST, "evento_classificado_hospital_ges");
    $evento_negociado_ges = filter_input(INPUT_POST, "evento_negociado_ges");
    $evento_valor_negoc_ges = filter_input(INPUT_POST, "evento_valor_negoc_ges");
    $evento_data_ges = filter_input(INPUT_POST, "evento_data_ges");
    $evento_encerrar_ges = filter_input(INPUT_POST, "evento_encerrar_ges");
    $evento_prorrogar_ges = filter_input(INPUT_POST, "evento_prorrogar_ges");
    $evento_impacto_financ_ges = filter_input(INPUT_POST, "evento_impacto_financ_ges");
    $evento_prolongou_internacao_ges = filter_input(INPUT_POST, "evento_prolongou_internacao_ges");
    $evento_concluido_ges = filter_input(INPUT_POST, "evento_concluido_ges");
    $evento_classificacao_ges = filter_input(INPUT_POST, "evento_classificacao_ges");
    $evento_fech_ges = filter_input(INPUT_POST, "evento_fech_ges");

    // Receber os dados dos inputs UTI
    $select_uti = filter_input(INPUT_POST, "select_uti");
    $fk_internacao_uti = filter_input(INPUT_POST, "fk_internacao_uti");
    $rel_uti = filter_input(INPUT_POST, "rel_uti") ?: null;
    $fk_paciente_int = filter_input(INPUT_POST, "fk_paciente_int");
    $internado_uti = filter_input(INPUT_POST, "internado_uti");
    $criterios_uti = filter_input(INPUT_POST, "criterios_uti");
    $data_alta_uti = filter_input(INPUT_POST, "data_alta_uti");
    $data_internacao_uti = filter_input(INPUT_POST, "data_internacao_uti");
    $dva_uti = filter_input(INPUT_POST, "dva_uti");
    $especialidade_uti = filter_input(INPUT_POST, "especialidade_uti");
    $internacao_uti = filter_input(INPUT_POST, "internacao_uti");
    $just_uti = filter_input(INPUT_POST, "just_uti");
    $motivo_uti = filter_input(INPUT_POST, "motivo_uti");
    $saps_uti = filter_input(INPUT_POST, "saps_uti");
    $score_uti = filter_input(INPUT_POST, "score_uti");
    $vm_uti = filter_input(INPUT_POST, "vm_uti");
    $id_internacao = filter_input(INPUT_POST, "id_internacao");
    $data_create_uti = filter_input(INPUT_POST, "data_create_uti") ?: null;
    $fk_user_uti = filter_input(INPUT_POST, "fk_user_uti");
    $glasgow_uti = filter_input(INPUT_POST, "glasgow_uti");
    $suporte_vent_uti = filter_input(INPUT_POST, "suporte_vent_uti");
    $dist_met_uti = filter_input(INPUT_POST, "dist_met_uti");
    $justifique_uti = filter_input(INPUT_POST, "justifique_uti");
    $hora_internacao_uti = filter_input(INPUT_POST, "hora_internacao_uti");
    $programacao_vis = filter_input(INPUT_POST, "programacao_vis");

    // Receber os dados dos inputs prorrogacao
    $select_prorrog = filter_input(INPUT_POST, "select_prorrog");
    $fk_internacao_pror = filter_input(INPUT_POST, "fk_internacao_pror");
    $acomod1_pror = filter_input(INPUT_POST, "acomod1_pror");
    $isol_1_pror = filter_input(INPUT_POST, "isol_1_pror");
    $prorrog1_fim_pror = filter_input(INPUT_POST, "prorrog1_fim_pror") ?: null;
    $prorrog1_ini_pror = filter_input(INPUT_POST, "prorrog1_ini_pror") ?: null;
    $fk_usuario_pror = filter_input(INPUT_POST, "fk_usuario_pror");
    $fk_usuario_pror = filter_input(INPUT_POST, "fk_usuario_pror");
    $fk_int_visita = filter_input(INPUT_POST, "fk_int_visita");

    // Receber os dados dos inputs neggoc
    $select_negoc = filter_input(INPUT_POST, "select_negoc");
    $fk_id_int = filter_input(INPUT_POST, "fk_id_int");
    $fk_usuario_neg = filter_input(INPUT_POST, "fk_usuario_neg");

    // $antecedentes = filter_input(INPUT_POST, 'antecedentes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $jsonAntec = filter_input(INPUT_POST, 'json-antec', FILTER_DEFAULT);
    if ($jsonAntec) {
        $antecedentes = json_decode($jsonAntec, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            var_dump($antecedentes); // Exibe os dados decodificados
        } else {
            echo "Erro na decodificação do JSON: " . json_last_error_msg();
        }
    }
    if (!$jsonAntec) {
        error_log("Nenhum JSON foi recebido.");
    } else {
        error_log("JSON recebido: " . $jsonAntec);
    }

    $visita = new visita();

    // Validação mínima de dados
    if (3 < 4) {

        $visita->fk_internacao_vis = $fk_internacao_vis;
        $visita->usuario_create = $usuario_create;
        $visita->rel_visita_vis = $rel_visita_vis;
        $visita->acoes_int_vis = $acoes_int_vis;
        $visita->data_visita_vis = $data_visita_vis;
        $visita->visita_no_vis = $visita_no_vis;
        $visita->visita_enf_vis = $visita_enf_vis;
        $visita->visita_med_vis = $visita_med_vis;
        $visita->visita_auditor_prof_enf = $visita_auditor_prof_enf;
        $visita->visita_auditor_prof_med = $visita_auditor_prof_med;

        $visita->exames_enf = $exames_enf;
        $visita->oportunidades_enf = $oportunidades_enf;
        $visita->programacao_enf = $programacao_enf;
        // echo "<pre>";
        // // print_r($visita);
        // print(var_dump($visita));
        // echo "</pre>";
        // // print("cjslkjsd" . " - " . $type . " ");
        // exit;

        $visitaDao->create($visita);

        //lancar dados antecedentes
        if ($jsonAntec) {
            $antecedentes = json_decode($jsonAntec, true);

            foreach ($antecedentes as $antecedenteData) {
                error_log("Processando antecedente: " . print_r($antecedenteData, true));
                try {
                    $intern_antec = $internAntecedenteDao->buildintern_antec($antecedenteData);
                    $internAntecedenteDao->create($intern_antec);
                    error_log("Antecedente salvo com sucesso.");
                } catch (Exception $e) {
                    error_log("Erro ao salvar antecedente: " . $e->getMessage());
                }
            }
        }

        // lancar dados gestao 
        if ($select_gestao == "s") {

            $gestao = new gestao();

            // lancar dados do input gestao se selecionado

            $gestao->fk_internacao_ges = $fk_internacao_vis;
            $gestao->alto_custo_ges = $alto_custo_ges;
            $gestao->fk_visita_ges = $fk_visita_ges;

            $gestao->alto_custo_ges = $alto_custo_ges;
            $gestao->rel_alto_custo_ges = $rel_alto_custo_ges;

            $gestao->opme_ges = $opme_ges;
            $gestao->rel_opme_ges = $rel_opme_ges;

            $gestao->home_care_ges = $home_care_ges;
            $gestao->rel_home_care_ges = $rel_home_care_ges;

            $gestao->desospitalizacao_ges = $desospitalizacao_ges;
            $gestao->rel_desospitalizacao_ges = $rel_desospitalizacao_ges;

            $gestao->evento_adverso_ges = $evento_adverso_ges;
            $gestao->rel_evento_adverso_ges = $rel_evento_adverso_ges;
            $gestao->tipo_evento_adverso_gest = $tipo_evento_adverso_gest;
            $gestao->evento_retorno_qual_hosp_ges = $evento_retorno_qual_hosp_ges;
            $gestao->evento_classificado_hospital_ges = $evento_classificado_hospital_ges;
            $gestao->evento_data_ges = $evento_data_ges;
            $gestao->evento_encerrar_ges = $evento_encerrar_ges;
            $gestao->evento_impacto_financ_ges = $evento_impacto_financ_ges;
            $gestao->evento_prolongou_internacao_ges = $evento_prolongou_internacao_ges;
            $gestao->evento_concluido_ges = $evento_concluido_ges;
            $gestao->evento_classificacao_ges = $evento_classificacao_ges;
            $gestao->evento_fech_ges = $evento_fech_ges;
            $gestao->fk_user_ges = $fk_user_ges;
            // $gestao->fk_usuario_ges = $fk_usuario_ges;
            // print_r($gestao);
            // exit();
            $gestaoDao->create($gestao);
        }
        ;

        // lancar dados tuss 
        if ($select_tuss == "s") {

            // Decodifica o JSON enviado pelo input tuss-json
            $tussJson = isset($_POST['tuss-json']) ? $_POST['tuss-json'] : '[]';
            $tussArray = json_decode($tussJson, true);

            // Verifica se o JSON foi decodificado corretamente
            if (is_array($tussArray) && isset($tussArray['tussEntries'])) {
                foreach ($tussArray['tussEntries'] as $tussData) {
                    $tuss = new tuss();

                    // Preenche os campos do objeto tuss com os dados do JSON
                    $tuss->fk_int_tuss = $fk_internacao_vis ?? null;
                    $tuss->fk_usuario_tuss = $tussData['fk_usuario_tuss'] ?? null;
                    $tuss->tuss_solicitado = $tussData['tuss_solicitado'] ?? null;
                    $tuss->data_realizacao_tuss = $tussData['data_realizacao_tuss'] ?? null;
                    $tuss->qtd_tuss_solicitado = $tussData['qtd_tuss_solicitado'] ?? null;
                    $tuss->qtd_tuss_liberado = $tussData['qtd_tuss_liberado'] ?? null;
                    $tuss->tuss_liberado_sn = $tussData['tuss_liberado_sn'] ?? null;
                    $tuss->fk_vis_tuss = $tussData['fk_int_tuss'];
                    $tuss->data_create_tuss = $data_visita_vis;

                    // Chama o método DAO para salvar os dados no banco
                    $tussDao->create($tuss);
                }
            } else {
                // Erro ao decodificar o JSON
                throw new Exception("Erro ao processar os dados de TUSS.");
            }
        }
        ;

        // lancar dados UTI 
        if ($select_uti == "s") {

            $uti = new uti();

            // lancar dados do input uti se selecionado
            $uti->fk_internacao_uti = $fk_internacao_vis;
            $uti->internado_uti = $internado_uti;
            $uti->criterios_uti = $criterios_uti;
            $uti->data_alta_uti = $data_alta_uti;
            $uti->data_internacao_uti = $data_internacao_uti;
            $uti->dva_uti = $dva_uti;
            $uti->especialidade_uti = $especialidade_uti;
            $uti->internacao_uti = $internacao_uti;
            $uti->just_uti = $just_uti;
            $uti->motivo_uti = $motivo_uti;
            $uti->rel_uti = $rel_uti;
            $uti->saps_uti = $saps_uti;
            $uti->score_uti = $score_uti;
            $uti->vm_uti = $vm_uti;
            $uti->id_internacao = $id_internacao;
            $uti->usuario_create_uti = $usuario_create;
            // $uti->data_create_uti = $data_create_int;
            $uti->fk_user_uti = $fk_user_uti;
            $uti->glasgow_uti = $glasgow_uti;
            $uti->suporte_vent_uti = $suporte_vent_uti;
            $uti->justifique_uti = $justifique_uti;
            $uti->hora_internacao_uti = $hora_internacao_uti;
            $uti->dist_met_uti = $dist_met_uti;
            $uti->fk_visita_uti = $fk_int_visita;

            $utiDao->create($uti);
        }
        ;

        // lancar dados negociacao 
        if ($select_negoc == "s") {
            $negociacoesJSON = $_POST['negociacoes_json'] ?? '[]'; // Obtém o JSON ou define um array vazio
            $negociacoesArray = json_decode($negociacoesJSON, true);
            // Se não houver negociações válidas, não faz nada e continua o fluxo
            if (!is_array($negociacoesArray) || count($negociacoesArray) === 0) {
                error_log("Nenhuma negociação foi enviada ou válida. Prosseguindo com o restante do processo." . $negociacoesJSON);
            } else {
                foreach ($negociacoesArray as $negociacaoData) {
                    // Validação adicional dos campos
                    $trocaDe = filter_var($negociacaoData['troca_de'], FILTER_VALIDATE_INT);
                    $trocaPara = filter_var($negociacaoData['troca_para'], FILTER_VALIDATE_INT);
                    $qtd = filter_var($negociacaoData['qtd'], FILTER_VALIDATE_INT);
                    $saving = filter_var($negociacaoData['saving'], FILTER_VALIDATE_FLOAT);

                    $tipo_negociacao = filter_var($negociacaoData['tipo_negociacao']);
                    $data_inicio_negoc = ($negociacaoData['data_inicio_negoc']);
                    $data_fim_negoc = ($negociacaoData['data_fim_negoc']);

                    // Ignora negociações com dados inválidos
                    if (!$trocaDe || !$trocaPara || !$qtd || $saving === false) {
                        error_log("Negociação inválida ignorada: " . print_r($negociacaoData, true));
                        continue;
                    }

                    // Criação da negociação
                    $negociacao = new Negociacao();
                    $negociacao->fk_id_int = $fk_internacao_vis;
                    $negociacao->fk_usuario_neg = $negociacaoData['fk_usuario_neg'];
                    $negociacao->troca_de = $trocaDe;
                    $negociacao->troca_para = $trocaPara;
                    $negociacao->qtd = $qtd;
                    $negociacao->saving = $saving;
                    $negociacao->fk_visita_neg = $fk_int_visita;

                    $negociacao->tipo_negociacao = $tipo_negociacao;
                    $negociacao->data_inicio_neg = $data_inicio_negoc;
                    $negociacao->data_fim_neg = $data_fim_negoc;

                    // Verifica duplicidade antes de criar
                    if (!$negociacaoDao->existeNegociacao($negociacao)) {
                        if (!$negociacaoDao->create($negociacao)) {
                            error_log("Erro ao salvar negociação: " . print_r($negociacao, true));
                        }
                    } else {
                        error_log("Negociação duplicada ignorada: " . print_r($negociacao, true));
                    }
                }
            }
        }
        ;
        if ($select_prorrog == "s") {

            // Obtém o JSON das prorrogações enviado pelo formulário
            $prorrogacoesJson = $_POST['prorrogacoes-json'] ?? '[]';

            // Decodifica o JSON
            $prorrogacoesArray = json_decode($prorrogacoesJson, true);

            // Verifica se houve erro na decodificação
            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                throw new Exception("Erro ao decodificar o JSON de prorrogações: " . $jsonError);
            }

            // Valida se o formato do JSON está correto
            if (is_array($prorrogacoesArray) && isset($prorrogacoesArray['prorrogations'])) {

                // Itera pelas entradas de prorrogações
                foreach ($prorrogacoesArray['prorrogations'] as $prorrogacaoData) {

                    // Preenche o objeto prorrogação com os dados do JSON
                    $prorrogacao = new prorrogacao();
                    $prorrogacao->fk_internacao_pror = $fk_internacao_vis;
                    $prorrogacao->fk_usuario_pror = $prorrogacaoData['fk_usuario_pror'];
                    $prorrogacao->acomod1_pror = $prorrogacaoData['acomod1_pror'];
                    $prorrogacao->prorrog1_ini_pror = $prorrogacaoData['prorrog1_ini_pror'];
                    $prorrogacao->prorrog1_fim_pror = $prorrogacaoData['prorrog1_fim_pror'];
                    $prorrogacao->isol_1_pror = $prorrogacaoData['isol_1_pror'] ?? null;
                    $prorrogacao->diarias_1 = $prorrogacaoData['diarias_1'];
                    $prorrogacao->fk_visita_pror = $fk_int_visita;

                    // Insere no banco
                    $prorrogacaoDao->create($prorrogacao);
                }
            } else {
                throw new Exception("Formato de JSON inválido para prorrogações.");
            }
        }
        ;
        header('location:list_internacao.php');
    } else {

        $message->setMessage("Você precisa adicionar pelo menos uma visita!", "error", "back");
    }
} else if ($type === "update") {

    $visita = new visita();

    // Receber os dados dos inputs
    $id_visita = filter_input(INPUT_POST, "id_visita");
    $fk_hospital = filter_input(INPUT_POST, "fk_hospital");
    $valor_diaria = filter_input(INPUT_POST, "valor_diaria");

    $visita = $visitaDao->findById($id_visita);

    $visita['id_visita'] = $id_visita;
    $visita['fk_hospital'] = $fk_hospital;
    $visita['valor_diaria'] = $valor_diaria;

    $visitaDao->update($visita);

    include_once('list_visita.php');
}

$type = filter_input(INPUT_POST, "type");

if ($type === "delete") {
    // Recebe os dados do form
    $id_visita = filter_input(INPUT_GET, "id_visita");

    $visitaDao = new visitaDAO($conn, $BASE_URL);

    $visita = $visitaDao->findById($id_visita);
    if ($visita) {

        $visitaDao->destroy($id_visita);

        include_once('list_visita.php');
    } else {

        $message->setMessage("Informações inválidas!", "error", "index.php");
    }
}