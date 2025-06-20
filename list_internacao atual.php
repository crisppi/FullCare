<?php
include_once("check_logado.php");

include_once("models/pagination.php");

?>
<!DOCTYPE html>
<html lang="pt-br">
<script src="js/timeout.js"></script>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/timeout.js"></script>

</head>

<body>
    <?php
    $busca = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $ativo_pac = filter_input(INPUT_GET, 'ativo_pac', FILTER_SANITIZE_SPECIAL_CHARS);
    $bl = filter_input(INPUT_GET, 'bl', FILTER_SANITIZE_SPECIAL_CHARS);

    include_once("globals.php");
    require_once("templates/header.php");
    require_once("dao/internacaoDao.php");
    require_once("models/message.php");
    include_once("models/hospital.php");
    include_once("dao/hospitalDao.php");
    include_once("models/patologia.php");
    include_once("dao/patologiaDao.php");
    require_once("dao/pacienteDAO.php");

    $hospital_geral = new hospitalDAO($conn, $BASE_URL);
    $hospitals = $hospital_geral->findGeral();
    $pacienteDao = new pacienteDAO($conn, $BASE_URL);
    $pacientes = $pacienteDao->findGeral($limite, $inicio);
    $patologiaDao = new patologiaDAO($conn, $BASE_URL);
    $patologias = $patologiaDao->findGeral();

    $internacao = new internacaoDAO($conn, $BASE_URL);
    // $internacaoSel = $internacao->findGeral();

    $pesquisa_hosp = filter_input(INPUT_GET, 'pesquisa_hosp');
    isset($_GET['pesqInternado']) ? $pesqInternado = filter_input(INPUT_GET, 'pesqInternado') : "";
    $ativo = filter_input(INPUT_GET, 'pesqInternado');
    $pesquisando = "";
    ?>
    <!-- FORMULARIO DE PESQUISAS -->
    <div class="container">
        <div class="container py-2">
            <form class="formulario visible" action="" id="select-internacao-form" method="GET">
                <h6 style="margin-left: 30px; padding-top:10px" class="page-title">Pesquisa internações</h6>

                <div class="form-group row">
                    <div class="form-group col-sm-3">
                        <input style="margin-left: 30px;" type="text" name="pesquisa_hosp"
                            placeholder="Selecione o Hospital" value="<?= $pesquisa_hosp ?>">
                    </div>
                    <div class="form-group col-sm-1">
                        <input style="margin-left: 30px;" type="hidden" name="pesquisando" id="pesquisando" value=""
                            placeholder="Selecione o Hospital">
                    </div>
                    <div class="form-group col-sm-4">
                        <select class="form-control mb-3" id="pesqInternado" name="pesqInternado">
                            <option value="">Busca por Internados</option>
                            <option value="Sim">Sim</option>
                            <option value="Não">Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-3">
                        <button type="submit" class="btn btn-primary mb-1">Buscar</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- BASE DAS PESQUISAS -->

        <?php

        // validacao do formulario
        if (isset($_GET['pesqInternado'])) {
            $pesqInternado = $_GET['pesqInternado'];
        }

        if (isset($_GET['pesquisa_hosp'])) {
            $pesquisa_hosp = $_GET['pesquisa_hosp'];
        }

        if (isset($_GET['pesquisando'])) {
            $pesquisando = $_GET['pesquisando'];
        }

        // ENCAMINHAMENTO DOS INPUTS DO FORMULARIO

        // filtro limpo
        if (($pesquisando === "")) {
            $internacaoList = $internacao->findByAll($limite, $inicio);
            // echo "chegou no filtro limpo. Limite = " . $limite . "Inicio = " . $inicio . "pesquisa = " . $pesquisando . ".";
        }

        // filtro de hospital
        if (($pesquisa_hosp != "")) {
            $internacaoList = $internacao->findByHospital($pesquisa_hosp, $limite, $inicio);
            echo "chegou no filtro hospital. Limite = " . $limite . "Inicio = " . $inicio . "pesquisa = " . $pesquisa_hosp . ".";
        }

        // filtro de internados
        if (($pesqInternado != "")) {
            $internacaoList = $internacao->findByInternado($pesqInternado, $limite, $inicio);
            // print_r($internacaoList);
        }

        // // filtro vazio
        // if (($pesqInternado === "") || ($pesquisa_hosp === "")) {
        //     $internacaoList = $internacao->findAll($limite, $inicio);
        //     print_r($query);
        // }
        ?>
        <div class="container">
            <h6 class="page-title">Relatório de internações</h6>
            <table class="table table-sm table-striped  table-hover table-condensed">
                <thead>
                    <tr>
                        <th scope="col">Id</th>
                        <th scope="col">Internado</th>
                        <th scope="col">Hospital</th>
                        <th scope="col">Paciente</th>
                        <th scope="col">Data internação</th>
                        <th scope="col">Acomodação</th>
                        <th scope="col">Data visita</th>
                        <th scope="col">Grupo Patologia</th>
                        <th scope="col">Modo Admissão</th>
                        <th scope="col">Tipo Alta</th>
                        <th scope="col">Médico</th>
                        <th scope="col">Especialidade</th>
                        <th scope="col">Relatório</th>
                        <th scope="col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($internacaoList as $intern) :
                    ?>
                    <tr>
                        <td scope="row" class="col-id"><?= $intern["id_internacao"] ?></td>
                        <td scope="row" class="nome-coluna-table"><?= $intern["internado_int"] ?></td>
                        <td scope="row" class="nome-coluna-table"><?= $intern["nome_hosp"] ?></td>
                        <td scope="row"><?= $intern["nome_pac"] ?></td>
                        <td scope="row"><?= $intern["data_intern_int"] ?></td>
                        <td scope="row"><?= $intern["acomodacao_int"] ?></td>
                        <td scope="row"><?= $intern["data_visita_int"] ?></td>
                        <td scope="row"><?= $intern["grupo_patologia_int"] ?></td>
                        <td scope="row"><?= $intern["tipo_admissao_int"] ?></td>
                        <td scope="row"><?= $intern["tipo_alta_int"] ?></td>
                        <td scope="row"><?= $intern["modo_internacao_int"] ?></td>
                        <td scope="row"><?= $intern["titular_int"] ?></td>
                        <td scope="row"><?= $intern["especialidade_int"] ?></td>
                        <td scope="row"><?= $intern["rel_int"] ?></td>

                        <td class="action">
                            <a href="<?= $BASE_URL ?>show_internacao.php?id_internacao=<?= $intern["id_internacao"] ?>"><i
                                    style="color:orange; margin-right:10px"
                                    class="aparecer-acoes fas fa-eye check-icon"></i></a>
                            <a href="<?= $BASE_URL ?>cad_visita.php?id_internacao=<?= $intern["id_internacao"] ?>"><i
                                    style="color:black; font-weigth:bold; margin-left:5px;margin-right:5px" name="type"
                                    value="visita" class="aparecer-acoes bi bi-file-text"></i></a>

                            <form class="d-inline-block delete-form" action="edit_alta.php" method="get">
                                <input type="hidden" name="type" value="alta">
                                <!-- <input type="hidden" name="alta" value="alta"> -->
                                <input type="hidden" name="id_internacao" value="<?= $intern["id_internacao"] ?>">
                                <button type="hidden"
                                    style="margin-left:3px; font-size: 16px; background:transparent; border-color:transparent; color:red"
                                    class="delete-btn"><i class=" d-inline-block bi bi-door-open"></i></button>
                            </form>
                            <form class="d-inline-block delete-form" action="del_internacao.php" method="POST">
                                <input type="hidden" name="type" value="delete">
                                <input type="hidden" name="id_internacao" value="<?= $intern["id_internacao"] ?>">
                                <button type="submit"
                                    style="margin-left:3px; font-size: 16px; background:transparent; border-color:transparent; color:red"
                                    class="delete-btn"><i
                                        class=" d-inline-block aparecer-acoes bi bi-x-square-fill delete-icon"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- <?php $id_internacao = filter_input(INPUT_GET, "id_internacao"); ?> -->

        </div>
        <?php

        //modo cadastro
        $formData = "0";
        $formData = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $total = $internacao->findTotal();

        $totalcasos = $total['0'];
        // echo $totalcasos['0'];
        $reg = ($totalcasos['0']);
        // echo $reg;

        if ($formData !== "0") {
            $_SESSION['msg'] = "<p style='color: green;'>Usuário cadastrado com sucesso!</p>";
            //header("Location: index.php");
        } else {
            echo "<p style='color: #f00;'>Erro: Usuário não cadastrado!</p>";
        };

        try {

            $query_Total = $conn->prepare($sql_Total);
            $query_Total->execute();
            $query_result = $query_Total->fetchAll(PDO::FETCH_ASSOC);
            # conta quantos registros tem no banco de dados
            $query_count = $query_Total->rowCount();

            # calcula o total de paginas a serem exibidas
            $qtdPag = ceil($reg / $limite);
            echo $pesqInternado;
        } catch (PDOexception $error_Total) {

            echo 'Erro ao retornar os Dados. ' . $error_Total->getMessage();
        }
        echo "<div style=margin-left:20px;>";
        echo "<div style='color:blue; margin-left:20px;'>";
        echo "</div>";
        echo "<nav aria-label='Page navigation example'>";
        echo " <ul class='pagination'>";
        echo " <li class='page-item'><a class='page-link' href='list_internacao.php?pg=1'><span aria-hidden='true'>&laquo;</span></a></li>";
        if ($qtdPag > 1 && $pg <= $qtdPag) {
            for ($i = 1; $i <= $qtdPag; $i++) {
                if ($i == $pg) {
                    echo "<li class='page-item active'><a class='page-link' class='ativo'>" . $i . "</a></li>";
                } else {
                    echo "<li class='page-item '><a class='page-link' href='list_internacao.php?pg=$i&pesquisa_hosp=&pesquisando=&pesqInternado=$pesquisando'>" . $i . "</a></li>";
                }
            }
        }
        echo "<li class='page-item'><a class='page-link' href='list_internacao.php?pg=$qtdPag&pesquisa_hosp=&pesquisando=&pesqInternado=$pesquisando'><span aria-hidden='true'>&raquo;</span></a></li>";
        echo " </ul>";
        echo "</nav>";
        echo "</div>"; ?>
        <div>
            <hr>
            <a class="btn btn-success styled" style="margin-left:120px" href="/internacao/novo">Nova internação</a>
        </div>
    </div>
</body>
<?php
include_once("templates/footer.php");
?>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous">
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js">
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>



</html>