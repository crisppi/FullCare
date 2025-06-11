<?php

include_once("check_logado.php");
require_once("templates/header.php");
?>
<script src="js/timeout.js"></script>

<div style="margin:20px">

    <div class="row"
        style="margin-top:20px; background-color:#FFFF; box-shadow: 0px 10px 15px -3px rgba(0,0,0,0.1); border-radius: 10px;">
        <div class="menu_header" style="height: 50px;background-color:#35bae1">
            <h4><i class="bi bi-calendar2-date"></i> Produção</h4>
            <h4><i class="bi bi-person menu_header_i"></i> Administrativo</h4>
            <h4><i class="bi bi-pencil-square menu_header_i"></i> Cadastro</h4>
            <h4><i class="bi bi-book menu_header_i"></i> Listas</h4>
        </div>

        <style>
        .lista_menu li {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        </style>
        <!-- lista producao -->
        <div class="col lista_menu">
            <hr>
            <li>
                <a href="/censo/novo" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-book"
                        style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></i>
                    Censo</a>
            </li>
            <li><a href="<?php $BASE_URL ?>list_censo.php"><i class="bi bi-book"
                        style="font-size: 1rem;margin-right:5px; color: rgb(27, 156, 55);"></i> Lista
                    Censo</a>
            </li>
            <li>
                <a href="/internacao/novo" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-calendar2-date"
                        style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></i> Nova Internação</a>
            </li>
            <li>
                <a href="/visita" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-pencil-square"
                        style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></i> Nova Visita</a>
            </li>
            <hr>

            <li>
                <a href="/internacao" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-calendar2-date"
                        style="font-size: 1rem;margin-right:5px; color: rgb(27,156, 55);"></i> Lista Internação</a>
            </li>
            <li>
                <a href="list_internacao_uti.php" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-clipboard-heart"
                        style="font-size: 1rem;margin-right:5px; color: rgb(27,156, 55);"></i> Internação UTI</a>
            </li>
            <hr>

            <li>
                <a href="list_internacao_cap.php" <?php if ($_SESSION['nivel'] < 0) { ?> style="pointer-events: none"
                    ?<?php } ?>><span id="boot-icon2" class="bi bi-briefcase"
                        style="font-size: 1rem; margin-right:5px; color: rgb(255, 25, 55);"></span> Contas para
                    Auditoria
                </a>
            </li>

            <hr>
            <li>
                <a href="list_internacao_uti_alta.php" <?php if ($_SESSION['nivel'] < 2) { ?>
                    style="pointer-events: none" ?<?php } ?>><span id="boot-icon3" class="bi bi-box-arrow-left"
                        style="font-size: 1rem; margin-right:5px; color: rgb(167, 25, 55);"></span> Alta UTI</a>
            </li>
            <li>
                <a href="list_internacao_alta.php" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><span id="boot-icon3" class="bi bi-box-arrow-left"
                        style="font-size: 1rem; margin-right:5px; color: rgb(16, 15, 155);"></span> Alta Hospitalar</a>
            </li>
            <hr>
            <li>
                <a href="list_gestao.php" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class=" bi bi-clipboard-heart"
                        style="font-size: 1rem;margin-right:5px; color: rgb(142, 15, 15);"></i> Gestão</a>
            </li>
            <hr>
            <li>
                <a href="list_internacao_patologia.php" <?php if ($_SESSION['nivel'] < 2) { ?>
                    style="pointer-events: none" ?<?php } ?>><span id="boot-icon1" class="bi bi-capsule-pill"
                        style="font-size: 1rem; margin-right:5px; color: rgb(77, 155, 67);"> </span> DRG</a>
            </li>
            <br>
        </div>

        <!-- lista admnistrativo -->
        <div class="col lista_menu">

            <hr>

            <li>
                <a href="/usuario/novo" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-person"
                        style="font-size: 1rem;margin-right:5px; color: rgb(155, 155, 76);"></span> Cadastro
                    Usuários</a>
            </li>
            <li>
                <a href="nova_senha.php" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-person"
                        style="font-size: 1rem;margin-right:5px; color: rgb(15, 15, 76);"></span> Alterar senha</a>
            </li>
            <li>
                <a href="cad_acomodacao.php" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-clipboard-heart"
                        style="font-size: 1rem; margin-right:5px; color: rgb(155, 155, 76);"></span> Cadastro
                    Acomodação</a>
            </li>
            <hr>
            <li>
                <a href="/patologia/novo" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-heart-pulse"
                        style="font-size: 1rem;margin-right:5px; color: rgb(15, 215, 55);"></span> Cadastro
                    Patologia</a>
            </li>
            <li>
                <a href="/antecedente/novo" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-heart-pulse"
                        style="font-size: 1rem;margin-right:5px; color: rgb(155, 15, 55);"></span> Cadastro
                    Antecedente</a>
            </li>
            <hr>
            <li>
                <a href="/usuario" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-person"
                        style="font-size: 1rem;margin-right:5px; color: rgb(15, 155, 18);"></span> Relação Usuários</a>
            </li>
            <li>
                <a href="/hospital-user" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-person-add"
                        style="font-size: 1rem; margin-right:5px; color: rgb(15, 15, 276);"></i> Relação Usuários por
                    Hospital</a>
            </li>
            <li>
                <a href="list_acomodacao.php" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class=" bi bi-clipboard-heart"
                        style="font-size: 1rem;margin-right:5px; color: rgb(145, 156, 55);"></i> Relação Acomodação</a>
            </li>
            <li>
                <a href="/patologia" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class=" bi bi-virus"
                        style="font-size: 1rem;margin-right:5px; color: rgb(178, 155, 155);"></span> Relação
                    Patologias</a>
            </li>
            <li>
                <a href="/antecedente" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-people"
                        style="font-size: 1rem;margin-right:5px; color: rgb(178, 156, 55);"></i> Relação Antedentes</a>
            </li>
        </div>
        <!-- lista cadastro -->
        <div style="margin-bottom:80px" class="col lista_menu">
            <!-- <div>
                <h4 class="titulo_menu" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none" ?<?php } ?>>CADASTRO</h4>
            </div> -->
            <hr>
            <li>
                <a href="cad_paciente.php" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-person"
                        style="font-size: 1rem;margin-right:5px; color: rgb(155, 155, 76);"></span> Pacientes</a>
            </li>
            <li>
                <a href="/hospital/novo" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-hospital"
                        style="font-size: 1rem;margin-right:5px; color: rgb(255, 25, 55);"></span> Hospital</a>
            </li>
            <li>
                <a href="/usuario/novo" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-person-add"
                        style="font-size: 1rem; margin-right:5px; color: rgb(155, 15, 276);"></i> Usuário</a>
            </li>
            <li>
                <a href="cad_paciente.php" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-building"
                        style="font-size: 1rem; margin-right:5px; color: rgb(145, 25, 177);"></span> Estipulante</a>
            </li>
            <li>
                <a href="/seguradora/novo" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-heart-pulse"
                        style="font-size: 1rem;margin-right:5px; color: rgb(255, 215, 55);"></span> Seguradora</a>
            </li>
        </div>

        <!-- lista Listas -->
        <div class="col lista_menu">

            <!-- <h4>LISTAS</h4> -->
            <hr>

            <li>
                <a href="list_paciente.php" <?php if ($_SESSION['nivel'] < 2) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-person"
                        style="font-size: 1rem;margin-right:5px; color: rgb(155, 155, 76);"></span> Pacientes</a>
            </li>
            <li>
                <a href="/hospital" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-hospital"
                        style="font-size: 1rem;margin-right:5px; color: rgb(67, 125, 525);"></span> Hospital</a>
            </li>
            <li>
                <a href="/usuario" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-file-medical"
                        style="font-size: 1rem; margin-right:5px; color: rgb(155, 16, 76);"></i> Usuário</a>
            </li>
            <li>
                <a href="/estipulante" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><i class="bi bi-building"
                        style="font-size: 1rem;margin-right:5px; color: rgb(213, 12, 155);"></i> Estipulante</a>
            </li>
            <li>
                <a href="/seguradora" <?php if ($_SESSION['nivel'] < 4) { ?> style="pointer-events: none"
                    ?<?php } ?>><span class="bi bi-heart-pulse"
                        style="font-size: 1rem;margin-right:5px; color: rgb(255, 215, 55);"></span> Seguradora</a>
            </li>
        </div>
        <hr>
        <!-- <?php include_once("nivel_login.php"); ?> -->
        <!-- <div class="container">
            <?php
            print_r($_SESSION); // $dataFech = date('Y-m-d');
            if ($_SESSION['cargo'] === "Enf_auditor") {
                echo "<div class='logado'>";
                echo "Olá ";
                echo $_SESSION['email_user'];
                echo "!! ";
                echo "<br>";
                echo "  Você está logado como Enfermeiro(a)";
                echo "</div>";
            };
            if ($_SESSION['cargo'] === "Diretoria") {
                echo "<div class='logado'>";
                echo "Olá ";
                echo $_SESSION['email_user'];
                echo "!! ";
                echo "<br>";
                echo "  Você está logado como Diretor(a)";
                echo "</div>";
            };
            if ($_SESSION['cargo'] === "Med_auditor") {
                echo "<div class='logado'>";
                echo "Olá ";
                echo "<b>" . $_SESSION['email_user'] . "</b>";
                echo "!! ";
                echo "  Você está logado como Médico(a)";
                echo "</div>";
            };
            if ($_SESSION['cargo'] === "Adm") {
                echo "<div class='logado'>";
                echo "Olá ";
                echo $_SESSION['email_user'];
                echo "!! ";
                echo "  Você está logado como Administrativo(a)";
                echo "</div>";
            };
            ?>
            <br>

        </div> -->

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous">
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<?php
require_once("templates/footer.php");
?>