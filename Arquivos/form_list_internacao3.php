    <?php

    require_once("templates/header.php");

    require_once("models/message.php");

    include_once("models/internacao.php");
    include_once("dao/internacaoDao.php");

    include_once("models/patologia.php");
    include_once("dao/patologiaDao.php");

    include_once("models/paciente.php");
    include_once("dao/pacienteDao.php");

    include_once("models/hospital.php");
    include_once("dao/hospitalDao.php");

    include_once("models/capeante.php");
    include_once("dao/capeanteDao.php");

    include_once("models/pagination.php");

    $Internacao_geral = new internacaoDAO($conn, $BASE_URL);
    $Internacaos = $Internacao_geral->findGeral();

    $pacienteDao = new pacienteDAO($conn, $BASE_URL);
    $pacientes = $pacienteDao->findGeral($limite, $inicio);

    $capeante_geral = new HospitalDAO($conn, $BASE_URL);
    $capeantes = $capeante_geral->findGeral($limite, $inicio);

    $hospital_geral = new HospitalDAO($conn, $BASE_URL);
    $hospitals = $hospital_geral->findGeral($limite, $inicio);

    $patologiaDao = new patologiaDAO($conn, $BASE_URL);
    $patologias = $patologiaDao->findGeral();

    $internacao = new internacaoDAO($conn, $BASE_URL);

    ?>
    <!-- FORMULARIO DE PESQUISAS -->
    <div class="container">
        <nav class="navbar navbar-light bg-light">
            <div class="container py-2">
                <button onclick="exibirPesquisa()" class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarToggleExternalContent2" aria-controls="navbarToggleExternalContent2" style="color:rgb(55,75,355)" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="page-title">Internações</h4>

            </div>
        </nav>
        <div class="container oculto py-2" id="navbarToggleExternalContent">

            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

            <form class="formulario visible" action="" id="select-internacao-form" method="GET">
                <h6 style="margin-left: 30px; padding-top:10px" class="page-title">Pesquisa internações</h6>
                <?php $pesquisa_nome = filter_input(INPUT_GET, 'pesquisa_nome');
                $pesqInternado = filter_input(INPUT_GET, 'pesqInternado') ?: "s";
                $limite = filter_input(INPUT_GET, 'limite');
                $pesquisa_pac = filter_input(INPUT_GET, 'pesquisa_pac');
                $ordenar = filter_input(INPUT_GET, 'ordenar');
                ?>
                <div class="form-group row">
                    <div class="form-group col-sm-3">
                        <label style="margin-left: 30px;">Pesquisa por Hospital</label>

                        <input style="margin-left: 30px;" class="form-control" type="text" name="pesquisa_nome" placeholder="Selecione o Hospital" value="<?= $pesquisa_nome ?>">

                    </div>
                    <div class="form-group col-sm-3">
                        <label style="margin-left: 30px;">Pesquisa por Paciente</label>

                        <input style="margin-left: 30px;" class="form-control" type="text" name="pesquisa_pac" placeholder="Selecione o Paciente" value="<?= $pesquisa_pac ?>">
                    </div>

                    <div style="margin-left:20px" class="form-group col-sm-2">
                        <label>Internados</label>
                        <select class="form-control mb-2" id="pesqInternado" name="pesqInternado">
                            <option value="">Busca por Internados</option>
                            <option value="s" <?= $pesqInternado == 's' ? 'selected' : null ?>>Sim</option>
                            <option value="n" <?= $pesqInternado == 'n' ? 'selected' : null ?>>Não</option>
                        </select>
                    </div>
                    <div style="margin-left:20px" class="form-group col-sm-1">
                        <label>Limite</label>
                        <select class="form-control mb-3" id="limite" name="limite">
                            <option value="">Reg por página</option>
                            <option value="5" <?= $limite == '5' ? 'selected' : null ?>>5</option>
                            <option value="10" <?= $limite == '10' ? 'selected' : null ?>>10</option>
                            <option value="20" <?= $limite == '20' ? 'selected' : null ?>>20</option>
                            <option value="50" <?= $limite == '50' ? 'selected' : null ?>>50</option>
                        </select>
                    </div>
                    <div style="margin-left:20px" class="form-group col-sm-1">
                        <label>Classificar</label>
                        <select class="form-control mb-1" id="ordenar" name="ordenar">
                            <option value="">Classificar por</option>
                            <option value="id_internacao" <?= $ordenar == 'id_internacao' ? 'selected' : null ?>>Internação</option>
                            <option value="nome_pac" <?= $ordenar == 'nome_pac' ? 'selected' : null ?>>Paciente</option>
                            <option value="nome_hosp" <?= $ordenar == 'nome_hosp' ? 'selected' : null ?>>Hospital</option>
                            <option value="data_intern_int" <?= $ordenar == 'data_intern_int' ? 'selected' : null ?>>Data Internação</option>
                        </select>
                    </div>
                </div>
                <div class="form-group col-sm-1" style="margin:0px 0px 10px 30px">
                    <button type="submit" class="btn btn-primary mb-1 btn-int-pesq"><span class="material-icons">
                            person_search
                        </span></button>
                </div>
        </div>
        </form>
    </div>

    <!-- BASE DAS PESQUISAS -->
    <?php
    //Instanciando a classe
    $QtdTotalInt = new internacaoDAO($conn, $BASE_URL);
    // METODO DE BUSCA DE PAGINACAO 
    $pesquisa_nome = filter_input(INPUT_GET, 'pesquisa_nome');
    $pesqInternado = filter_input(INPUT_GET, 'pesqInternado') ?: "s";
    $limite = filter_input(INPUT_GET, 'limite') ? filter_input(INPUT_GET, 'limite') : 10;
    $pesquisa_pac = filter_input(INPUT_GET, 'pesquisa_pac');
    $ordenar = filter_input(INPUT_GET, 'ordenar') ? filter_input(INPUT_GET, 'ordenar') : 1;
    // $buscaAtivo = in_array($buscaAtivo, ['s', 'n']) ?: "";

    $condicoes = [
        strlen($pesquisa_nome) ? 'ho.nome_hosp LIKE "%' . $pesquisa_nome . '%"' : NULL,
        strlen($pesquisa_pac) ? 'nome_pac LIKE "%' . $pesquisa_pac . '%"' : NULL,
        strlen($pesqInternado) ? 'internado_int = "' . $pesqInternado . '"' : NULL,
    ];
    $condicoes = array_filter($condicoes);
    // print_r($condicoes);
    // REMOVE POSICOES VAZIAS DO FILTRO
    $where = implode(' AND ', $condicoes);
    // print_r($where);

    // QUANTIDADE InternacaoS
    $qtdIntItens1 = $QtdTotalInt->QtdInternacao($where);

    $qtdIntItens = ($qtdIntItens1['qtd']);
    $totalcasos = ceil($qtdIntItens / $limite);

    print_r("quantidade " . $qtdIntItens1['qtd']);
    print_r("total casos " . $totalcasos);
    print_r("limite " . $limite);


    // PAGINACAO
    $order = $ordenar;

    $obPagination = new pagination($qtdIntItens, $_GET['pag'] ?? 1, $limite ?? 10);

    $obLimite = $obPagination->getLimit();

    // PREENCHIMENTO DO FORMULARIO COM QUERY
    // $where = $order = $obLimite = null;
    $query = $internacao->selectAllInternacao($where, $order, $limite);

    // GETS 
    unset($_GET['pag']);
    unset($_GET['pg']);
    $gets = http_build_query($_GET);

    // PAGINACAO
    $paginacao = '';
    $paginas = $obPagination->getPages();
    // print_r($paginas);

    foreach ($paginas as $pagina) {
        $class = $pagina['atual'] ? 'btn-primary' : 'btn-light';
        $paginacao .= '<li class="page-item"><a href="?pag=' . $pagina['pg'] . '&' . $gets . '"> 
    <button type="button" class="btn ' . $class . '">' . $pagina['pg'] . '</button>
    <li class="page-item"></a>';
    };


    ?>
    <div class="container">
        <h6 class="page-title">Relatório de internações</h6>
        <?php
        if ($_SESSION['cargo'] === "Enf_auditor") {
            echo "<div class='logado'>";
            echo "Olá ";
            echo  $_SESSION['login_user'];
            echo "!! ";
            echo "<br>";
            echo "  Você está logado como Enfermeiro(a)";
            echo "</div>";
        };
        if ($_SESSION['cargo'] === "Med_auditor") {
            echo "<div class='logado'>";
            echo "Olá ";
            echo  "<b>" . $_SESSION['login_user'] . "</b>";
            echo "!! ";
            echo "  Você está logado como Médico(a)";
            echo "</div>";
        };
        if ($_SESSION['cargo'] === "Adm") {
            echo "<div class='logado'>";
            echo "Olá ";
            echo  $_SESSION['login_user'];
            echo "!! ";
            echo "  Você está logado como Administrativo(a)";
            echo "</div>";
        };
        ?>
        <table class="table table-sm table-striped  table-hover table-condensed">
            <thead>
                <tr>
                    <th scope="col" style="width:3%">Id</th>
                    <th scope="col" style="width:3%">Internado</th>
                    <th scope="col" style="width:10%">Hospital</th>
                    <th scope="col" style="width:10%">Paciente</th>
                    <th scope="col" style="width:4%">Data internação</th>
                    <th scope="col" style="width:4%">Acomodação</th>
                    <th scope="col" style="width:4%">Data visita</th>
                    <th scope="col" style="width:4%">Modo Admissão</th>
                    <th scope="col" style="width:4%">Tipo internação</th>
                    <th scope="col" style="width:10%">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($query as $intern) :
                    extract($query);
                ?>
                    <tr>
                        <td scope="row" class="col-id"><?= $intern["id_internacao"] ?></td>
                        <td scope="row" class="nome-coluna-table"><?php if ($intern["internado_int"] == "s") {
                                                                        echo "Sim";
                                                                    } else {
                                                                        echo "Não";
                                                                    }; ?></td>
                        <td scope="row" class="nome-coluna-table"><?= $intern["nome_hosp"] ?></td>
                        <td scope="row" style="font-weigth:800 "><?= $intern["nome_pac"] ?></td>
                        <td scope="row"><?= date('d/m/Y', strtotime($intern["data_intern_int"])) ?></td>
                        <td scope="row"><?= $intern["acomodacao_int"] ?></td>
                        <td scope="row"><?= date('d/m/Y', strtotime($intern["data_visita_int"])) ?></td>
                        <td scope="row"><?= $intern["tipo_admissao_int"] ?></td>
                        <td scope="row"><?= $intern["modo_internacao_int"] ?></td>

                        <td class="action">
                            <a href="<?= $BASE_URL ?>show_internacao.php?id_internacao=<?= $intern["id_internacao"] ?>"><i style="color:green; font-size:12px;margin-right:10px" class="aparecer-acoes fas fa-eye check-icon"></i></a>

                            <?php if ($pesqInternado == "s") { ?>
                                <a href="<?= $BASE_URL ?>cad_visita.php?id_internacao=<?= $intern["id_internacao"] ?>"><i style="color:black; text-decoration: none; font-size: 10px; font-weigth:bold; margin-left:5px;margin-right:5px" name="type" value="visita" class="aparecer-acoes bi bi-file-text"> Visita</i></a>
                            <?php }; ?>

                            <?php if ($pesqInternado == "s") { ?><form class="d-inline-block delete-form" action="edit_alta.php" method="get">
                                    <input type="hidden" name="type" value="alta">
                                    <input type="hidden" name="id_internacao" value="<?= $intern["id_internacao"] ?>">
                                    <button type="hidden" style="margin-left:3px; font-size: 10px; background:transparent; border-color:transparent; font-weight:bold; color:red" class="delete-btn"><i class=" d-inline-block bi bi-door-open">ALTA</i></button>
                                </form>
                            <?php }; ?>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div>
            <?php

            "<div style=margin-left:20px;>";
            echo "<div style='color:blue; margin-left:20px;'>";
            echo "</div>";
            echo "<nav aria-label='Page navigation example'>";
            echo " <ul class='pagination'>";
            echo " <li class='page-item'><a class='page-link' href='list_internacao.php?pag=1&" . $gets . "''><span aria-hidden='true'>&laquo;</span></a></li>"; ?>
            <?= $paginacao ?>
            <?php echo "<li class='page-item'><a class='page-link' href='list_internacao.php?pag=$totalcasos&" . $gets . "''><span aria-hidden='true'>&raquo;</span></a></li>";
            echo " </ul>";
            echo "</nav>";
            echo "</div>"; ?>
            <hr>
            <div>
                <a class="btn btn-success styled" href="/internacao/novo">Nova internação</a>
            </div>
        </div>


    </div>
    <script>
        function exibirPesquisa() {
            let formPesquisa = document.getElementById("navbarToggleExternalContent");

            if (formPesquisa.style.display == "none") {
                formPesquisa.style.display = "block";

            } else {
                formPesquisa.style.display = "none";

            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>