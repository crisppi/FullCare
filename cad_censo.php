<?php
// include_once("check_logado.php");

include_once("templates/header.php");

include_once("models/internacao.php");
include_once("dao/internacaoDao.php");

include_once("models/message.php");

include_once("models/hospital.php");
include_once("dao/hospitalDao.php");

include_once("models/patologia.php");
include_once("dao/patologiaDao.php");

include_once("models/paciente.php");
include_once("dao/pacienteDAO.php");

include_once("models/uti.php");
include_once("dao/utiDao.php");

include_once("models/gestao.php");
include_once("dao/gestaoDao.php");

include_once("models/prorrogacao.php");
include_once("dao/prorrogacaoDao.php");

include_once("models/negociacao.php");
include_once("dao/negociacaoDao.php");

include_once("array_dados.php");

$internacaoDao = new internacaoDAO($conn, $BASE_URL);

$hospital_geral = new hospitalDAO($conn, $BASE_URL);
$hospitals = $hospital_geral->findGeral($limite, $inicio);

$pacienteDao = new pacienteDAO($conn, $BASE_URL);
$pacientes = $pacienteDao->findGeral($limite, $inicio);

$patologiaDao = new patologiaDAO($conn, $BASE_URL);
$patologias = $patologiaDao->findGeral();

$gestao = new gestaoDAO($conn, $BASE_URL);
$gestaoIdMax = $gestao->findMax();
$findMaxGesInt = $gestao->findMaxGesInt();

$uti = new utiDAO($conn, $BASE_URL);
$utiIdMax = $uti->findMaxUTI();
$findMaxUtiInt = $uti->findMaxUtiInt();

$prorrogacao = new prorrogacaoDAO($conn, $BASE_URL);
$prorrogacaoIdMax = $prorrogacao->findMaxPror();
$prorrogacaoGeral = $prorrogacao->findGeral();
$findMaxProInt = $prorrogacao->findMaxProInt();


$negociacao = new negociacaoDAO($conn, $BASE_URL);
$negociacaoLast = new negociacaoDAO($conn, $BASE_URL);

?>

<body>
    <div id="main-container" style="margin:15px;">
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>

        <!-- FORMULARIO INTERNACAO -->
        <?php include_once('formularios/form_cad_censo.php'); ?>

        <!-- FORMULARIO DE GESTÃO -->
        <?php include_once('formularios/form_cad_internacao_gestao.php'); ?>

        <!-- FORMULARIO DE UTI -->
        <?php include_once('formularios/form_cad_internacao_uti.php'); ?>

        <!-- FORMULARIO DE PRORROGACOES -->
        <?php include_once('formularios/form_cad_internacao_prorrog.php'); ?>

        <!-- <FORMULARO DE NEGOCIACOES -->
        <?php include_once('formularios/form_cad_internacao_negoc.php'); ?>


        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>

        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script> -->
    </div>
</body>
<?php
require_once("templates/footer.php");
?>