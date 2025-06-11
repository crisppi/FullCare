<body>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="./scripts/cadastro/general.js"></script>
    <!-- <script src="./js/load/form_list_paciente.php"></script> -->

    <?php
    include_once("globals.php");
    include_once("models/paciente.php");
    include_once("models/message.php");
    include_once("dao/pacienteDao.php");
    include_once("templates/header.php");
    include_once("array_dados.php");

    //Instanciando a classe 
    $paciente = new PacienteDAO($conn, $BASE_URL);
    $QtdTotalpac = new PacienteDAO($conn, $BASE_URL);

    // METODO DE BUSCA DE PAGINACAO
    $busca = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $pesquisa_nome = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $buscaAtivo = filter_input(INPUT_GET, 'ativo_pac', FILTER_SANITIZE_SPECIAL_CHARS);
    $limite = filter_input(INPUT_GET, 'limite') ? filter_input(INPUT_GET, 'limite') : 10;
    $ordenar = filter_input(INPUT_GET, 'ordenar') ? filter_input(INPUT_GET, 'ordenar') : 1;
    $buscaAtivo = in_array($buscaAtivo, ['s', 'n']) ?: "";
    $pacienteInicio = ' 1 ';
    $condicoes = [
        strlen($busca) ? 'nome_pac LIKE "%' . $busca . '%"' : null,
        strlen($buscaAtivo) ? 'ativo_pac = "' . $buscaAtivo . '"' : null,
        strlen($pacienteInicio) ? 'id_paciente > ' . $pacienteInicio . ' ' : NULL,

    ];
    $condicoes = array_filter($condicoes);
    // print_r($condicoes);
    $order = $ordenar;

    // REMOVE POSICOES VAZIAS DO FILTRO
    $where = implode(' AND ', $condicoes);
    $qtdpacItens1 = $QtdTotalpac->selectAllpaciente($where, $order, $obLimite ?? null);
    $qtdIntItens = count($qtdpacItens1); // total de registros
    // PAGINACAO
    $obPagination = new pagination($qtdIntItens, $_GET['pag'] ?? 1, $limite ?? 10);
    $obLimite = $obPagination->getLimit();

    // PREENCHIMENTO DO FORMULARIO COM QUERY
    $query = $paciente->selectAllpaciente($where, $order, $obLimite);

    $totalcasos = ceil($qtdIntItens / 5);

    // PAGINACAO
    if ($qtdIntItens > $limite) {
        $paginacao = '';
        $paginas = $obPagination->getPages();
        $pagina = 1;
        $total_pages = count($paginas);

        // FUNCAO PARA CONTROLE DO NUMERO DE PAGINAS, UTILIZANDO A QUANTIDADE DE PAGINAS CALCULADAS NA VARIAVEL PAGINAS PELE METODO getPages

        function paginasAtuais($var)
        {
            $blocoAtual = isset($_GET['bl']) ? $_GET['bl'] : 0;
            return $var['bloco'] == (($blocoAtual) / 5) + 1;
        }
        $block_pages = array_filter($paginas, "paginasAtuais"); // REFERENCIA FUNCAO CRIADA ACIMA
        $first_page_in_block = reset($block_pages)["pg"];
        $last_page_in_block = end($block_pages)["pg"];
        $first_block = reset($paginas)["bloco"];
        $last_block = end($paginas)["bloco"];
        $current_block = reset($block_pages)["bloco"];
    }
    ?>

    <!--tabela paciente-->
    <div class="container-fluid form_container" id="main-container" style="margin-top:12px;">
        <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 0;">
            <h4 class="page-title" style="margin-top:-10px;line-height: 1.5;">Pacientes</h4>
            <div style="margin-left: auto;">
                <button onclick="openModal('cad_paciente.php')" data-bs-toggle="modal" data-bs-target="#myModal"
                    class="btn btn-success styled"
                    style="border-radius:10px;background-color: #35bae1;font-family:var(--bs-font-sans-serif);box-shadow: 0px 10px 15px -3px rgba(0,0,0,0.1);border:none">
                    <i class="fa-solid fa-plus" style='font-size: 1rem;margin-right:5px;'></i>Novo Paciente
                </button>
            </div>
        </div>
        <hr style="margin-top: 1px; margin-bottom: 10px;">

        <!-- div filtro -->
        <div class="complete-table">
            <div id="navbarToggleExternalContent" class="table-filters">
                <form id="form_pesquisa" method="GET">
                    <div class="row">
                        <div class="form-group col-sm-2" style="padding:2px !important;padding-left:16px !important;">
                            <!-- <label>Pesquisa Nome</label> -->
                            <input class="form-control form-control-sm" style="margin-top:7px" type="text"
                                value="<?= $busca ?>" name="pesquisa_nome" id="pesquisa_nome"
                                placeholder="Pesquisa por paciente">
                        </div>
                        <div class="col-sm-1" style="padding:2px !important">
                            <select class="form-control mb-3 form-control-sm" style="margin-top:7px;" id="limite"
                                name="limite">
                                <option value="">Reg por página</option>
                                <option value="5" <?= $limite == '5' ? 'selected' : null ?>>Reg por pág = 5
                                </option>
                                <option value="10" <?= $limite == '10' ? 'selected' : null ?>>Reg por pág = 10
                                </option>
                                <option value="20" <?= $limite == '20' ? 'selected' : null ?>>Reg por pág = 20
                                </option>
                                <option value="50" <?= $limite == '50' ? 'selected' : null ?>>Reg por pág = 50
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-sm-2" style="padding:2px !important">
                            <!-- <label>Classificar</label> -->
                            <select class="form-control form-control-sm"
                                style="margin-top:7px;font-size:.8em; color:#878787" id="ordenar" name="ordenar">
                                <option value="">Classificar por</option>
                                <option value="id_paciente" <?= $ordenar == 'id_paciente' ? 'selected' : null ?>>Id
                                    Paciente
                                </option>
                                <option value="nome_pac" <?= $ordenar == 'nome_pac' ? 'selected' : null ?>>Nome Paciente
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-sm-1" style="padding:2px !important" style="margin:0px 0px 20px 0px">
                            <button type="submit" class="btn btn-primary"
                                style="background-color:#5e2363;width:42px;height:32px;margin-top:7px;border-color:#5e2363"><span
                                    class="material-icons" style="margin-left:-3px;margin-top:-2px;">
                                    search
                                </span></button>
                        </div>


                    </div>
                </form>
            </div>
            <!-- div filtro -->
            <div>
                <div id="table-content">
                    <table class="table table-sm table-striped table-hover table-condensed">
                        <thead>
                            <tr>
                                <th scope="col">Id</th>
                                <th scope="col">Paciente</th>
                                <th scope="col">Mãe</th>
                                <th scope="col">CPF</th>
                                <th scope="col">Endereço</th>
                                <th scope="col">Cidade</th>
                                <th scope="col" width="8%">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            foreach ($query as $paciente):
                                extract($paciente);
                            ?>
                            <?php

                                if (strlen($cpf_pac) > 0) {
                                    $cpf_format = substr($cpf_pac, 0, 3) . '.' .
                                        substr($cpf_pac, 3, 3) . '.' .
                                        substr($cpf_pac, 6, 3) . '-' .
                                        substr($cpf_pac, 9, 2);
                                } else {
                                    $cpf_format = null;
                                }

                                ?>

                            <?php if ($id_paciente >= 1) { ?>
                            <tr style="font-size:15px">
                                <td scope="row" class="col-id">
                                    <?= $id_paciente ?>
                                </td>
                                <td scope="row" class="nome-coluna-table">
                                    <?= $nome_pac ?>
                                </td>
                                <td scope="row" class="nome-coluna-table">
                                    <?= $mae_pac ?>
                                </td>
                                <td scope="row" class="nome-coluna-table">
                                    <?= $cpf_format ?>
                                </td>
                                <td scope="row" class="nome-coluna-table">
                                    <?= $endereco_pac ?>
                                </td>
                                <td scope="row" class="nome-coluna-table">
                                    <?= $cidade_pac ?>
                                </td>

                                <td class="action">
                                    <div class="dropdown">
                                        <button class="btn btn-default dropdown-toggle" id="navbarScrollingDropdown"
                                            role="button" data-bs-toggle="dropdown" style="color:#5e2363"
                                            aria-expanded="false">
                                            <i class="bi bi-stack"></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                            <li>
                                                <button class="btn btn-default" style="font-size: .9rem;"
                                                    onclick="openModal('<?= $BASE_URL ?>show_paciente.php?id_paciente=<?= $id_paciente ?>')"
                                                    data-bs-toggle="modal" data-bs-target="#myModal"><i
                                                        class="fas fa-eye"
                                                        style="font-size: 1rem;margin-right:5px; color: rgb(27,156, 55);"></i>Ver</button>
                                            </li>
                                            <li>
                                                <button class="btn btn-default" style="font-size: .9rem;"
                                                    onclick="openModal('<?= $BASE_URL ?>edit_paciente.php?id_paciente=<?= $id_paciente ?>')"
                                                    data-bs-toggle="modal" data-bs-target="#myModal"><i
                                                        style="font-size: 1rem;margin-right:5px; color: rgb(67, 125, 525);"
                                                        name="type" value="edite"
                                                        class="far fa-edit edit-icon"></i>Editar</button>
                                            </li>
                                            <li>
                                                <button class="btn btn-default" style="font-size: .9rem;"
                                                    onclick="openModal('<?= $BASE_URL ?>show_paciente_historico.php?id_paciente=<?= $id_paciente ?>')"
                                                    data-bs-toggle="modal" data-bs-target="#myModal"><i
                                                        style="font-size: 1rem;margin-right:5px; color: rgb(67, 125, 525);"
                                                        name="type" value="edite"
                                                        class="fa-solid fa-clock edit-icon"></i>Historico</button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <?php }; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php if ($qtdIntItens == 0): ?>
                            <tr>
                                <td colspan="7" scope="row" class="col-id" style='font-size:15px'>
                                    Não foram encontrados registros
                                </td>
                            </tr>

                            <?php endif ?>
                        </tbody>
                    </table>
                    <hr>
                    <!-- salvar variavel qtdIntItens no PHP para passar para JS -->
                    <div style="text-align:right">
                        <input type="hidden" id="qtd" value="<?php echo $qtdIntItens ?>">
                    </div>
                    <!-- paginacao que aparece abaixo da tabela -->
                    <div style="display: flex;margin-top:20px">
                        <!-- Modal para abrir tela de cadastro -->
                        <div class="modal fade" id="myModal">
                            <div class="modal-dialog  modal-lg modal-dialog-centered modal-xl">
                                <div class="modal-content">
                                    <div style="padding-left:20px;padding-top:20px;">
                                        <h4>Paciente</h4>
                                        <p class="page-description">Informações
                                            do paciente</p>
                                    </div>
                                    <div class="modal-body">
                                        <div id="content-php"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- pagination -->

                        <div class="pagination" style="margin: 0 auto;">
                            <?php if ($total_pages ?? 1 > 1): ?>
                            <ul class="pagination">
                                <?php
                                    $blocoAtual = isset($_GET['bl']) ? $_GET['bl'] : 0;
                                    $paginaAtual = isset($_GET['pag']) ? $_GET['pag'] : 1;
                                    ?>
                                <?php if ($current_block > $first_block): ?>
                                <li class="page-item">
                                    <a class="page-link" id="blocoNovo" href="#"
                                        onclick="loadContent('list_paciente.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print 1 ?>&bl=<?php print 0 ?>')">
                                        <i class="fa-solid fa-angles-left"></i></a>
                                </li>
                                <?php endif; ?>
                                <?php if ($current_block <= $last_block && $last_block > 1 && $current_block != 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="#"
                                        onclick="loadContent('list_paciente.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $paginaAtual - 1 ?>&bl=<?php print $blocoAtual - 5 ?>')">
                                        <i class="fa-solid fa-angle-left"></i> </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = $first_page_in_block; $i <= $last_page_in_block; $i++): ?>
                                <li class="page-item <?php print ($_GET['pag'] ?? 1) == $i ? "active" : "" ?>">

                                    <a class="page-link" href="#"
                                        onclick="loadContent('list_paciente.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $i ?>&bl=<?php print $blocoAtual ?>')">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($current_block < $last_block): ?>
                                <li class="page-item">
                                    <a class="page-link" id="blocoNovo" href="#"
                                        onclick="loadContent('list_paciente.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $paginaAtual + 1 ?>&bl=<?php print $blocoAtual + 5 ?>')"><i
                                            class="fa-solid fa-angle-right"></i></a>
                                </li>
                                <?php endif; ?>
                                <?php if ($current_block < $last_block): ?>
                                <li class="page-item">
                                    <a class="page-link" id="blocoNovo" href="#"
                                        onclick="loadContent('list_paciente.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print count($paginas) ?>&bl=<?php print ($last_block - 1) * 5 ?>')"><i
                                            class="fa-solid fa-angles-right"></i></a>
                                </li>
                                <?php endif; ?>
                            </ul>
                            <?php endif; ?>
                        </div>

                        <div>
                            <p
                                style="margin-bottom:25px; font-size:1em; font-weight:600; font-family:var(--bs-font-sans-serif); text-align:right">
                                <?php echo "Total: " . $qtdIntItens ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>


<script>
// ajax para submit do formulario de pesquisa
$(document).ready(function() {
    $('#form_pesquisa').submit(function(e) {
        e.preventDefault(); // Impede o comportamento padrão de enviar o formulário

        var formData = $(this).serialize(); // Serializa os dados do formulário

        $.ajax({
            url: $(this).attr('action'), // URL do formulário
            type: $(this).attr('method'), // Método do formulário (POST)
            data: formData, // Dados serializados do formulário
            success: function(response) {


                // Crie um elemento temporário para armazenar a resposta HTML
                var tempElement = document.createElement('div');
                tempElement.innerHTML = response;

                // Encontre o elemento com o ID "table-content" dentro do elemento temporário
                var tableContent = tempElement.querySelector('#table-content');
                $('#table-content').html(tableContent);



            },


            error: function() {
                $('#responseMessage').html('Ocorreu um erro ao enviar o formulário.');
            }
        });
    });
});

$(document).ready(function() {
    loadContent(
        'list_paciente.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print 1 ?>&bl=<?php print 0 ?>'
    );
});
</script>

<style>
.modal-backdrop {
    display: none;

}

.modal {
    background: rgba(0, 0, 0, 0.5);

}

.modal-header {
    color: white;
    background: #35bae1;


}
</style>
<script src="./js/input-estilo.js"></script>

<script src="./js/ajaxNav.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
src = "https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js";
</script>