<?php
include_once("check_logado.php");

include_once("globals.php");

include_once("models/acomodacao.php");
include_once("dao/acomodacaoDao.php");
include_once("templates/header.php");

// Pegar o id do paceinte
$id_acomodacao = filter_input(INPUT_GET, "id_acomodacao", FILTER_SANITIZE_NUMBER_INT);

$acomodacao;

$acomodacaoDao = new acomodacaoDAO($conn, $BASE_URL);

//Instanciar o metodo acomodacao   
$acomodacao = $acomodacaoDao->joinAcomodacaoHospitalshow($id_acomodacao);

?> <h5>Dados do acomodação Registro no: <?= $acomodacao['id_acomodacao'] ?></h5>
<br>

<div class="card" id="main-container" class="container">
    <br>
    <script src="js/timeout.js"></script>

    <div class="card-header container-fluid" id="view-contact-container">
        <span class="card-title bold">Reg da Acomodação: </span>
        <span style="font-size:large; font-weight:bold"
            class="card-title bold"><?= $acomodacao['id_acomodacao'] ?></span>
        <br>

    </div>
    <div class="card-body">
        <span class=" card-text bold">Hospital:</span>
        <span style="font-size:large; font-weight:bold" class=" card-text bold"><?= $acomodacao['nome_hosp'] ?></span>
        <br>
        <span style="font-size:large" class=" card-text bold">Valor da Diária:</span>
        <span style="font-size:large; font-weight:bold" class=" card-text bold">R$
            <?= number_format($acomodacao['valor_aco'], 2, ',', '.') ?></span>
        <br>
        <span class=" card-text bold">Acomodação:</span>
        <span style="font-size:large; font-weight:bold"
            class=" card-text bold"><?= $acomodacao['acomodacao_aco'] ?></span>
        <br>

        <hr>
    </div>

</div>
<?php include_once("diversos/backbtn_acomodacao.php"); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<?php
require_once("templates/footer.php");
?>