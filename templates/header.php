<?php

include_once("globals.php");
include_once("db.php");
include_once("models/mensagem.php");
require_once("dao/mensagemDao.php");
$mensagemDao = new mensagemDAO($conn, $BASE_URL);
$mensagensNaoLidasCount = $mensagemDao->getMensagensNaoLidas($_SESSION['id_usuario']);
date_default_timezone_set('America/Sao_Paulo');
header("Content-type: text/html; charset=utf-8");

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


?>
<!DOCTYPE html>
<html lang="pt-br">

<head style="position:fixed">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">

    <link rel="shortcut icon" type="image/x-icon" href="img/full-ico.ico">

    <title>FullCare</title>
    <!-- Boostrap -->
    <link href="<?php $BASE_URL ?>css/style.css" rel="stylesheet">
    <link href="<?php $BASE_URL ?>css/legendas.css" rel="stylesheet">
    <link href="<?php $BASE_URL ?>css/styleMenu.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.4/bootstrap-icons.svg">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">

    <!-- boostrap icones -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Fontfaces CSS-->
    <link href="./diversos/CoolAdmin-master/css/font-face.css" rel="stylesheet" media="all">
    <link href="./diversos/CoolAdmin-master/vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet"
        media="all">
    <link href="./diversos/CoolAdmin-master/vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet"
        media="all">
    <link href="./diversos/CoolAdmin-master/vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet"
        media="all">

    <!-- Bootstrap CSS-->
    <link href="./diversos/CoolAdmin-master/vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all">

    <!-- Vendor CSS-->
    <link href="./diversos/CoolAdmin-master/vendor/animsition/animsition.min.css" rel="stylesheet" media="all">
    <link href="./diversos/CoolAdmin-master/vendor/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css"
        rel="stylesheet" media="all">
    <link href="./diversos/CoolAdmin-master/vendor/wow/animate.css" rel="stylesheet" media="all">
    <link href="./diversos/CoolAdmin-master/vendor/css-hamburgers/hamburgers.min.css" rel="stylesheet" media="all">
    <link href="./diversos/CoolAdmin-master/vendor/slick/slick.css" rel="stylesheet" media="all">
    <link href="./diversos/CoolAdmin-master/vendor/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="./diversos/CoolAdmin-master/vendor/perfect-scrollbar/perfect-scrollbar.css" rel="stylesheet"
        media="all">

    <!-- Main CSS-->
    <link href="./diversos/CoolAdmin-master/css/theme.css" rel="stylesheet"  media="all">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.3/css/all.css">
    <nav class="navbar navbar-light bg-light">

</head>


<body>
    <div class="col-md-12" style="padding:0 !important">


        <nav class="navbar navbar-expand-lg navbar-light bg-light nav_bar_custom fixed-top">
            <div class="bar_color" style="position:fixed;top:0;z-index:1000;width:100%;height:5px;background-image: linear-gradient(to right, #5e2363,#5bd9f3);
            ">
            </div>
            <div class="container-fluid">
                <a class="navbar-brand" href="inicio">
                    <img src="img/logo_novo.png" class="logo-novo" style="max-width: 100%;
    height: auto;
    width: auto\9;
    max-height: 100px;
    min-height: 50px;" alt="Full">
                    <!-- <img src="img/full-02 (1).png" style="margin:10px 50px 0px 0px; text-align:center;width:80px; height:50px" alt="Full"> -->
                </a>
                <!-- <div>
                    <h3 class="titulo_header" style="margin:0 50px 0 20px; text-align:center">Full Sistem</h3>
                </div> -->
                <!-- <div> -->
                <!-- <h4 class="titulo_header" style="margin:0 50px 0 0px; text-align:center">Full System</h4> -->
                <!-- <img src="img/full-02 (1).png" style="margin:10px 50px 0px 0px; text-align:center;width:70px; height:50px" alt="Full"> -->
                <!-- </div> -->
                <div class="collapse navbar-collapse" id="navbarScroll">
                    <ul class="nav-tabs navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll"
                        style="--bs-scroll-height: 80px;">
                        <!-- Ícone de mensagem -->
                        <?php if ($_SESSION['nivel'] == -1) { ?>

                        <li><a class="dropdown-item" href="<?php $BASE_URL ?>list_internacao_cap_fin.php"> <span
                                    id="boot-icon" class="bi bi-shield-check fw-bold"
                                    style="font-size: 1rem; margin-right:5px;color: rgb(21, 56, 210);"> </span>
                                Contas Para Validar
                            </a>
                        </li>
                        <?php }; ?>
                        <?php if ($_SESSION['nivel'] > 0) { ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle " href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i style="font-size: 1rem;margin-right:5px; color:#5e2363;" name="type" value="edite"
                                    class="bi bi-stack edit-icon"></i>
                                Menu
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>menu"><i class="bi bi-person"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></i>
                                        Dashboard</a></li>
                                <!-- <li><a class="dropdown-item" href="<?php $BASE_URL ?>menu.php"><span
                                            class="bi bi-hospital"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(67, 125, 525);"></span>
                                        Menu</a></li> -->
                            </ul>
                        </li>
                        <?php }; ?>

                        <?php if ($_SESSION['nivel'] > 3) { ?>
                        <li id="drop1" class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="<?php $BASE_URL ?>pacientes"
                                id="navbarScrollingDropdown" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i style="font-size: 1rem;margin-right:5px; color:#5e2363;" name="type" value="edite"
                                    class="bi bi-people-fill edit-icon"></i>
                                Usuários
                            </a>
                            <ul class="dropdown-menu" id="dropMenu1" aria-labelledby="navbarScrollingDropdown">
                                <!-- <li><a class="dropdown-item" href="<?php $BASE_URL ?>cad_usuario.php"><i class="bi bi-person-add" style="font-size: 1rem; margin-right:5px; color: rgb(15, 155, 76);"></i> Cadastro Usuário</a></li>
                  <li>
                  <li><a class="dropdown-item" href="<?php $BASE_URL ?>cad_hospitalUser.php"><i class="bi bi-person-add" style="font-size: 1rem; margin-right:5px; color: rgb(15, 15, 276);"></i> Cadastro
                      Hospital/Usuário</a></li>
                  <li>
                    <hr class="dropdown-divider">
                  </li> -->
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>usuarios"><i
                                            class="bi bi-file-medical"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(155, 95, 76);"></i>
                                        Pesquisa Usuários</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>hospital-usuario"><i
                                            class="bi bi-person-badge"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(15, 155, 176);"></i>
                                        Hospital por Usuário</a>
                                </li>
                            </ul>
                        </li>

                        <?php }; ?>
                        <?php if ($_SESSION['nivel'] > 3) { ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle " href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i style="font-size: 1rem;margin-right:5px; color:#5e2363;" name="type" value="edite"
                                    class="fa-solid fa-pen-to-square edit-icon"></i>
                                Cadastros
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>pacientes"><i class="bi bi-person"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></i>
                                        Pacientes</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>hospitais"><span
                                            class="bi bi-hospital"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(67, 125, 525);"></span>
                                        Hospitais</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>seguradoras"><span
                                            class=" bi bi-heart-pulse"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(178, 156, 55);"></span>
                                        Seguradora</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>estipulantes"><i
                                            class="bi bi-building"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(213, 12, 155);"></i>
                                        Estipulante</a></li>
                                <li>

                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>acomodacoes"><i
                                            class=" bi bi-clipboard-heart"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(145, 156, 55);"></i>
                                        Acomodação</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>patologias"><span
                                            class=" bi bi-virus"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(178, 155, 155);"></span>
                                        Patologia</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>antecedentes"><i
                                            class="bi bi-people"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(178, 156, 55);"></i>
                                        Antecedente</a></li>
                            </ul>
                        </li>
                        <?php }; ?>

                        <?php if ($_SESSION['nivel'] >= 3) { ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i style="font-size: 1rem;margin-right:5px; color:#5e2363;" name="type" value="edite"
                                    class="fa-solid fa-calendar edit-icon"></i>
                                Produção
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">

                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>internacoes"><i
                                            class="bi bi-calendar2-date"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></i> Nova
                                        Internação</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>censos"><i class="bi bi-book"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(222, 156, 55);"></i>
                                        Censo</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>internacoes">
                                        <i class="bi bi-calendar2-date"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></i>

                                        Internação</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>internacoes-uti"> <i
                                            class="bi bi-clipboard-heart"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(27,156, 55);"></i>
                                        Internação UTI</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>gestao"><i
                                            class="bi bi-postcard-heart"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(144, 17, 194);"></i>
                                        Gestão</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>internacoes-alta"><span
                                            id="boot-icon3" class="bi bi-postcard-heart"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(16, 15, 155);"></span>
                                        Altas</a>
                                </li>
                            </ul>
                        </li>
                        <?php }; ?>
                        <?php if ($_SESSION['nivel'] >= 3) { ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle " href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i style="font-size: 1rem;margin-right:5px; color:#5e2363;" name="type" value="edite"
                                    class="fa-solid fa-list edit-icon"></i>
                                Listas
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">

                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>censos"><i class="bi bi-book"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(222, 156, 55);"></i>
                                        Censo</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>internacoes"> <i
                                            class="bi bi-calendar2-date"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></i>
                                        Internação</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>internacoes-uti"> <i
                                            class="bi bi-clipboard-heart"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(27,156, 55);"></i>
                                        Internação UTI</a>
                                </li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>gestao"><i
                                            class="bi bi-postcard-heart"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(144, 17, 194);"></i>
                                        Gestão</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>internacoes-alta"><i
                                            class="bi bi-postcard-heart"
                                            style="font-size: 1rem;margin-right:5px; color: rgb(27,156, 55);"></i>
                                        Altas</a></li>

                            </ul>
                        </li>
                        <?php }; ?>
                        <li class="nav-item dropdown">
                            <?php if ($_SESSION['nivel'] >= 3) { ?>

                            <a class="nav-link dropdown-toggle " href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i style="font-size: 1rem;margin-right:5px; color:#5e2363;" name="type" value="edite"
                                    class="fa-solid fa-file-invoice edit-icon"></i>
                                Contas
                            </a>

                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>capeantes"><span id="boot-icon1"
                                            class="bi bi-currency-dollar"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(77, 155, 67);">
                                        </span> Contas para Auditar</a></li>
                                <li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>capeantes-finalizados"> <span
                                            id="boot-icon" class="bi bi-shield-check fw-bold"
                                            style="font-size: 1rem; margin-right:5px;color: rgb(21, 56, 210);"> </span>
                                        Contas Finalizadas
                                    </a>
                                </li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>senhas-finalizadas">
                                        <span id="boot-icon" class="bi bi-bookmark-check"
                                            style="font-size: 1rem; margin-right:5px;color: rgb(213, 56, 210);"> </span>
                                        Senhas Finalizadas
                                    </a>
                                </li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>contas-paradas"><span
                                            id="boot-icon1" class="bi bi-slash-circle"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(77, 155, 67);">
                                        </span> Contas Paradas</a></li>
                                <li>

                                    <?php }; ?>

                            </ul>
                        </li>
                        <?php if ($_SESSION['nivel'] >= 2) { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle " href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i style="font-size: 1rem;margin-right:5px; color:#5e2363;" name="type" value="edite"
                                    class="fa-solid fa-pills edit-icon"></i>
                                DRG
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>internacoes-patologias"><span
                                            id="boot-icon1" class="bi bi-capsule-pill"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(77, 155, 67);"> </span>
                                        Pesquisa internações
                                    </a></li>
                                <li>
                            </ul>
                        </li>
                        <?php }; ?>
                        <?php if ($_SESSION['nivel'] > 3) { ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle " href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i style="font-size: 1rem;margin-right:5px; color:#5e2363;" name="type" value="edite"
                                    class="fa-solid fa-print edit-icon"></i>
                                Relatórios
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>relatorios"><span id="boot-icon1"
                                            class="bi bi-clipboard-data"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(77, 155, 67);">
                                        </span> Relatórios </a></li>
                                <li>
                                <li><a class="dropdown-item"
                                        href="https://app.powerbi.com/reportEmbed?reportId=162595d1-241c-45dc-b282-e5134dc77636&autoAuth=true&ctid=5d8203ef-bc77-4057-86a0-56d58ebd6258">
                                        <span id="boot-icon1" class="bi bi-clipboard-data"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(77, 155, 67);">
                                        </span> Relatórios - APP</a></li>
                                <li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>relatorios-capeante"><span
                                            id="boot-icon1" class="bi bi-clipboard-data"
                                            style="font-size: 1rem; margin-right:5px; color: rgb(77, 155, 67);">
                                        </span> Relatórios Capeantes</a></li>
                                <li>
                            </ul>
                        </li>

                        <?php }; ?>
                    </ul>
                    <a href="show_chat.php" class="bi bi-chat-dots"
                        style="font-size: 1.5rem; color: #5e2363; position: relative;">
                        <?php if ($mensagensNaoLidasCount > 0): ?>
                        <span class="badge badge-danger" style="position: absolute; top: -5px; right: -10px;">
                            <?= $mensagensNaoLidasCount ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </div>


            </div>



            <div class="account-wrap">
                <div class="account-item clearfix js-item-menu" style="margin-right:30px">
                    <div class="image" style="margin-top:15px">
                        <img src="./uploads/usuarios/<?= $_SESSION['foto_usuario'] ?>" alt="John Doe" />
                    </div>
                    <div class="content">
                        <a class="js-acc-btn" href="#"><?php print $_SESSION['usuario_user'] ?></a>
                    </div>
                    <div class="account-dropdown js-dropdown">

                        <!-- <div class="account-dropdown__body">
                                <div class="account-dropdown__item">
                                    <a href="#">
                                        <i class="zmdi zmdi-account"></i>Account</a>
                                </div>
                                <div class="account-dropdown__item">
                                    <a href="#">
                                        <i class="zmdi zmdi-settings"></i>Setting</a>
                                </div>
                                <div class="account-dropdown__item">
                                    <a href="#">
                                        <i class="zmdi zmdi-money-box"></i>Billing</a>
                                </div>
                            </div> -->
                        <div class="account-dropdown__footer">
                            <a href="<?php $BASE_URL ?>destroi.php">
                                <i class="zmdi zmdi-power"></i>Sair</a>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </nav>
    <!-- <div class="bar_color" style="width:100%;height:3px;background-image: linear-gradient(to right, #18b6f5,#421849);
            ">
    </div> -->
</body>
<script src="js/fix-header.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous">
</script>
<!-- Jquery JS-->
<!-- <script src="./diversos/CoolAdmin-master/vendor/jquery-3.2.1.min.js"></script> -->
<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>

<!-- Bootstrap JS-->
<script src="./diversos/CoolAdmin-master/vendor/bootstrap-4.1/popper.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/bootstrap-4.1/bootstrap.min.js"></script>
<!-- Vendor JS       -->
<script src="./diversos/CoolAdmin-master/vendor/slick/slick.min.js">
</script>
<script src="./diversos/CoolAdmin-master/vendor/wow/wow.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/animsition/animsition.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/bootstrap-progressbar/bootstrap-progressbar.min.js">
</script>
<script src="./diversos/CoolAdmin-master/vendor/counter-up/jquery.waypoints.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/counter-up/jquery.counterup.min.js">
</script>
<script src="./diversos/CoolAdmin-master/vendor/circle-progress/circle-progress.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/chartjs/Chart.bundle.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/select2/select2.min.js">
</script>

</html>