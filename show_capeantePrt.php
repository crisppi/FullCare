<?php
include_once("templates/header.php");

include_once("globals.php");
include_once("models/internacao.php");
require_once("dao/internacaoDao.php");

include_once("models/hospital.php");
include_once("dao/hospitalDao.php");

include_once("models/patologia.php");
include_once("dao/patologiaDao.php");

include_once("models/paciente.php");
include_once("dao/pacienteDAO.php");

include_once("models/capeante.php");
include_once("dao/capeanteDAO.php");


// Pegar o id da internacao
$id_capeante = filter_input(INPUT_GET, "id_capeante", FILTER_SANITIZE_NUMBER_INT);
$fk_int_capeante = filter_input(INPUT_GET, "fk_int_capeante", FILTER_SANITIZE_NUMBER_INT);
$where = $fk_int_capeante;
$condicoes = [
    strlen($id_capeante) ? 'ca.id_capeante LIKE "%' . $id_capeante . '%"' : null,
];

$condicoes = array_filter($condicoes);
// REMOVE POSICOES VAZIAS DO FILTRO
$where = implode(' AND ', $condicoes);
$internacao;
$order = null;
$obLimite = null;
$capeanteDao = new capeanteDAO($conn, $BASE_URL);

//Instanciar o metodo internacao   
$internacao = $capeanteDao->selectAllcapeante($where, $order, $obLimite);
?>
<script src="js/timeout.js"></script>
<!-- <link rel="stylesheet" href="print.css" media="print"> -->


<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="<?php $BASE_URL ?>css/impressao.css" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
        integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

</head>
<div id="main-container" style="margin:15px">
    <hr>
    <div id="content">
        <img src="img/logo_novo.png" alt="Full"
            style="max-width: 100%;height: auto;width: auto\9;max-height: 100px;min-height: 50px;">
        <div class="collapse navbar-collapse" id="navbarScroll">
            <ul class="nav-tabs navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll" style="--bs-scroll-height: 100px;">
            </ul>
        </div>
        <hr>
        <h6 style="margin:10px 0 15px 20px; font-size:1.2em;font-weight: 800;"> Conta - Capeante nº:
            <?= $internacao['0']['id_capeante'] ?>
        </h6>
        </span>
        <div class=" card-header container-fluid" id="view-contact-container">
            <span style="font-weight: 700;margin-left:15%" class="card-title bold">Hospital:</span>
            <span class="card-title bold"><?= $internacao['0']['nome_hosp'] ?></span>
            <span style="display:none" id="id-capeante"
                class="card-title bold"><?= $internacao['0']['id_capeante'] ?></span>
            <span style="font-weight: 700;margin-left:15%" class="card-title bold">Paciente: </span>
            <span class="card-title bold"><?= $internacao['0']['nome_pac'] ?></span>
            <span style="font-weight: 700;margin-left:15%" class="card-title bold">Senha: </span>
            <span class="card-title bold"><?= $internacao['0']['senha_int'] ?: "" ?></span>
        </div>


        <div class="card-body">
            <span style="font-weight: 500;" class="texto1">Data Internação:</span>
            <span class=" texto1"><?= date("d/m/Y", strtotime($internacao['0']['data_intern_int'])) ?></span>
            <span class=" texto1" style="font-weight: 500;margin-left:8%">Tipo Internação:</span>
            <span class=" texto1"><?= $internacao['0']['tipo_admissao_int'] ?></span>
            <span style="font-weight: 500;margin-left:8%" class="texto1">Modo Admissão:</span>
            <span class=" texto1"><?= $internacao['0']['modo_internacao_int'] ?></span>
            <span style="font-weight: 500;margin-left:8%" class="texto1">Data inicial:</span>
            <span class="texto1"><?php echo date('d/m/Y', strtotime($internacao[0]['data_inicial_capeante'])); ?></span>
            <span style="font-weight: 500;margin-left:8%" class="texto1">Data Final:</span>
            <span class="texto1">
                <?php echo date('d/m/Y', strtotime($internacao['0']['data_final_capeante'])); ?>
            </span>
        </div>
        <hr>
        <div id="view-contact-container">
            <h6
                style="border: 1px solid #6c757d; font-size:1em;background-color: #e9ecef; padding: 10px 0; text-align: center; width: 100%; box-sizing: border-box;margin-bottom:10px">
                Consolidado Conta</h6>

            <span style="font-weight: 500;margin-left:15%" class="texto1">Valor Apresentado:</span>
            <span class="texto2">
                <?php
                $numero = floatval($internacao['0']['valor_apresentado_capeante']);
                echo "R$ " . number_format($numero, 2, ',', '.');
                ?>
            </span>

            <span style="font-weight: 500;margin-left:45%" class="texto1">Valor Final:</span>
            <span class="texto2">
                <?php
                $numero = floatval($internacao['0']['valor_final_capeante']);
                echo "R$ " . number_format($numero, 2, ',', '.');
                ?>
            </span>
        </div>

        <hr>
        <div>
            <h6
                style="border: 1px solid #6c757d; font-size:1em;background-color: #e9ecef; padding: 10px 0; text-align: center; width: 100%; box-sizing: border-box;margin-bottom:10px">
                Glosas Consolidado</h6>
            <span style="font-weight: 500;margin-left:10%" class="texto1">Glosa Total:</span>
            <span class="texto2"><?php
                                    $numero = floatval($internacao['0']['valor_glosa_total']);
                                    echo "R$ " . number_format($numero, 2, ',', '.')
                                    ?></span>

            <span style="font-weight: 500; margin-left:15% " class="texto1">Glosa Médica:</span>
            <span class="texto2"><?php
                                    $numero = floatval($internacao['0']['valor_glosa_med']);
                                    echo "R$ " . number_format($numero, 2, ',', '.')
                                    ?></span>

            <span style="font-weight: 500;margin-left:15% " class="texto1">Glosa Enfermagem:</span>
            <span class="texto2"><?php
                                    $numero = floatval($internacao['0']['valor_glosa_enf']);
                                    echo "R$ " . number_format($numero, 2, ',', '.')
                                    ?></span>
        </div>

        <hr>
        <h6
            style="border: 1px solid #6c757d; font-size:1em;background-color: #e9ecef; padding: 10px 0; text-align: center; width: 100%; box-sizing: border-box;margin-bottom:10px">
            Valores por Seguimento</h6>
        <span style="font-weight: 500;margin-left:10%" class="texto1"> Honorários:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['valor_honorarios']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <span style="font-weight: 500;margin-left:10% " class="texto1"> MatMed:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['valor_matmed']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <span style="font-weight: 500;margin-left:10% " class="texto1"> SADT:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['valor_sadt']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <span style="font-weight: 500;margin-left:10% " class="texto1"> Oxigenioterapia:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['valor_oxig']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <span style="font-weight: 500;margin-left:10% " class="texto1"> Taxas:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['valor_taxa']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <hr>
        <h6
            style="border: 1px solid #6c757d; font-size:1em;background-color: #e9ecef; padding: 10px 0; text-align: center; width: 100%; box-sizing: border-box;margin-bottom:10px">
            Glosas por Seguimento</h6>
        <span style="font-weight: 500;margin-left:10%" class="texto1"> Honorários:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['glosa_honorarios']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <span style="font-weight: 500;margin-left:10% " class="texto1"> MatMed:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['glosa_matmed']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <span style="font-weight: 500;margin-left:10% " class="texto1"> SADT:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['glosa_sadt']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <span style="font-weight: 500;margin-left:10% " class="texto1"> Oxigenioterapia:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['glosa_oxig']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <span style="font-weight: 500;margin-left:10% " class="texto1"> Taxas:</span>
        <span class="texto2"><?php
                                $numero = floatval($internacao['0']['glosa_taxas']);
                                echo "R$ " . number_format($numero, 2, ',', '.')
                                ?></span>
        <hr>
        <!-- </div> -->

        <div class="container" style="display: flex; width: 100%;margin-top:100px">
            <div class="container row" style="flex: 1; padding: 10px; box-sizing: border-box; text-align: center;">
                <div style="font-size: 2em ; font-weight: bold; font-style: italic; font-family: 'Brush Script MT', 'Cursive', 'Segoe Script', 'Comic Sans MS'"
                    class="item">
                    <?= $internacao['0']['nome_med'] ?>
                </div>
                <div style="font-size: small;" class="item">Médico(a) Auditor(a)</div>
            </div>
            <div class="container row" style="flex: 1; padding: 10px; box-sizing: border-box; text-align: center;">
                <div style="font-size: 2em ; font-weight: bold; font-style: italic; font-family: 'Brush Script MT', 'Cursive', 'Segoe Script', 'Comic Sans MS'"
                    class="item">
                    <?= $internacao['0']['nome_enf'] ?>
                </div>
                <div style="font-size: small;" class="item">Enfermeiro(a) Auditor(a)</div>
            </div>
            <div class="container row" style="flex: 1; padding: 10px; box-sizing: border-box; text-align: center;">
                <div style="font-size: 2em ; font-weight: bold; font-style: italic; font-family: 'Brush Script MT', 'Cursive', 'Segoe Script', 'Comic Sans MS'"
                    class="item">
                    <?= $internacao['0']['nome_adm'] ?>
                </div>
                <div style="font-size: small;" class="item">Administrativo(a)</div>
            </div>
            <div class="container row" style="flex: 1; padding: 10px; box-sizing: border-box; text-align: center;">
                <div style="font-size: 2em ; font-weight: bold; font-style: italic; font-family: 'Brush Script MT', 'Cursive', 'Segoe Script', 'Comic Sans MS'"
                    class="item">
                    <?= $internacao['0']['nome_aud_hosp'] ?>
                </div>
                <div style="font-size: small;" class="item">Responsável Hospital</div>
            </div>
        </div>

        <div style="text-align:center; margin-top:100px">
            <h6 class="texto1">
                _____________________________________________________________________________________________________________
            </h6>

            <br>
            <?php
            setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
            $dataFormatada = strftime("%d de %B de %Y");
            echo "São Paulo, " .  $dataFormatada . ".";
            ?>
        </div>

        <div style="margin: 15px; margin-top:100px">

            <!-- <h6 style="margin-bottom:25px" class="pdf">Clique no Botão para salvar o arquivo em PDF</h6> -->
            <button style="margin-top:25px" onclick="generatePdf()" class="btn btn-success" id="generate-pdf">Gerar
                Pdf</button>
            <hr>
        </div>
    </div>
    <br>
    <script>
    function generatePdf() {
        const element = document.getElementById('main-container'); // Elemento que será convertido para PDF

        // Captura o número do capeante do <span> com o ID "id-capeante"
        const idCapeante = document.getElementById('id-capeante').textContent; // Número do capeante

        // Captura a data atual
        const hoje = new Date(); // Data de hoje
        const dia = String(hoje.getDate()).padStart(2, '0'); // Dia (com zero à esquerda se necessário)
        const mes = String(hoje.getMonth() + 1).padStart(2, '0'); // Mês (com zero à esquerda se necessário)
        const ano = hoje.getFullYear(); // Ano

        // Formata a data no padrão dd_mm_aaaa
        const dataFormatada = `${dia}_${mes}_${ano}`;

        // Gera o nome do arquivo no formato CapNoX#_Data_dd_mm_aaaa.pdf
        const nomeArquivo = `CapNo${idCapeante}#_Data_${dataFormatada}.pdf`;

        const options = {
            margin: [0.5, 0.5, 0.5, 0.5], // Margens menores para ajustar tudo
            filename: nomeArquivo, // Usa o nome formatado
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 3, // Aumente a escala para melhor qualidade
                useCORS: true,
                scrollY: 0 // Garante que toda a página seja capturada
            },
            jsPDF: {
                unit: 'cm',
                format: 'a4',
                orientation: 'landscape' // Alterado para paisagem
            },
            pagebreak: {
                mode: ['avoid-all', 'css', 'legacy'], // Controla quebras automáticas
            }
        };

        // Gera e salva o PDF
        html2pdf().set(options).from(element).save();
    }
    </script>