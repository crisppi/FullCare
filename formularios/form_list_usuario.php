<?php
include_once("globals.php");
include_once("models/usuario.php");
include_once("models/message.php");
include_once("dao/usuarioDao.php");
include_once("templates/header.php");
include_once("array_dados.php");

//Instanciando a classe
$usuarioDAO = new UserDAO($conn, $BASE_URL);
$QtdTotalUser = new UserDAO($conn, $BASE_URL);

// METODO DE BUSCA DE PAGINACAO
$usuario = filter_input(INPUT_GET, 'pesquisa_nome');
$pesquisa_nome = filter_input(INPUT_GET, 'pesquisa_nome');
$cargo = filter_input(INPUT_GET, 'cargo');
$depto = filter_input(INPUT_GET, 'depto');
$buscaAtivo = filter_input(INPUT_GET, 'ativo_user');
$limite = filter_input(INPUT_GET, 'limite') ? filter_input(INPUT_GET, 'limite') : 10;
$ordenar = filter_input(INPUT_GET, 'ordenar') ? filter_input(INPUT_GET, 'ordenar') : 1;

$order = null;
$obLimite = null;
// $pag = null;

$condicoes = [
    strlen($usuario) ? 'usuario_user LIKE "%' . $usuario . '%"' : null,
    strlen($cargo) ? 'cargo_user LIKE "%' . $cargo . '%"' : null,
    strlen($depto) ? 'depto_user LIKE "%' . $depto . '%"' : null,
    strlen($buscaAtivo) ? 'ativo_user = "' . $buscaAtivo . '"' : null
];
$condicoes = array_filter($condicoes);
// REMOVE POSICOES VAZIAS DO FILTRO
$where = implode(' AND ', $condicoes);
// print_r($condicoes);
$qtdUserItens1 = $QtdTotalUser->selectAllUsuario($where, $order, $obLimite);

$order = $ordenar;
$qtdIntItens = count($qtdUserItens1);
// PAGINACAO
$obPagination = new pagination($qtdIntItens, $_GET['pag'] ?? 1, $limite ?? 10);
$obLimite = $obPagination->getLimit();

// PREENCHIMENTO DO FORMULARIO COM QUERY
$query = $usuarioDAO->selectAllUsuario($where, $order, $obLimite);

// PAGINACAO
$paginacao = '';
$paginas = $obPagination->getPages();
$paginaAtual = isset($_GET['pag']) ? $_GET['pag'] : 1;
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

<!--tabela evento-->
<div class="container-fluid form_container" style="margin-top:12px;">
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="./scripts/cadastro/general.js"></script>

    <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 0;">
        <h4 style="margin-top:-10px" class="page-title">Usuários - Listagem</h4>
        <div style="margin-left: auto;">
            <button onclick="openModal('cad_usuario.php')" data-bs-toggle="modal" data-bs-target="#myModal"
                class="btn btn-success"
                style="border-radius:10px;background-color: #35bae1;font-family:var(--bs-font-sans-serif);box-shadow: 0px 10px 15px -3px rgba(0,0,0,0.1);border:none">
                <i class="fa-solid fa-plus" style='font-size: 1rem;margin-right:5px;'></i>Novo Usuário
            </button>
        </div>
    </div>
    <hr style="margin-top: 5px; margin-bottom: 10px;">
    <div class="complete-table">
        <div id="navbarToggleExternalContent" class="table-filters">
            <div class="row">
                <form id="form_pesquisa" method="GET">
                    <div class="row">

                        <div class="col-sm-3" style="padding:2px !important;padding-left:16px !important;">
                            <input style="margin-top:7px;" class="form-control form-control-sm" type="text"
                                name="pesquisa_nome" placeholder="Selecione o Usuário" value="<?= $busca ?>">
                        </div>
                        <div class="col-sm-2" style="padding:2px !important">
                            <input style="margin-top:7px;" class="form-control form-control-sm" type="text" name="cargo"
                                placeholder="Selecione o Cargo" value="<?= $cargo ?>">
                        </div>
                        <div class="col-sm-2" style="padding:2px !important">
                            <input style="margin-top:7px;" class="form-control form-control-sm" type="text" name="depto"
                                placeholder="Selecione o Depto" value="<?= $depto ?>">
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
                        <div class="col-sm-2" style="padding:2px !important">
                            <select class="form-control mb-3 form-control-sm"
                                style="margin-top:7px;font-size:.8em; color:#878787" style="font-size:0.6em"
                                id="ordenar" name="ordenar">
                                <option value="">Classificar por</option>
                                <option value="id_usuario" <?= $ordenar == 'id_usuario' ? 'selected' : null ?>>ID
                                    Usuário
                                </option>
                                <option value="usuario_user" <?= $ordenar == 'usuario_user' ? 'selected' : null ?>>
                                    Usuário
                                </option>
                                <option value="cargo_user" <?= $ordenar == 'cargo_user' ? 'selected' : null ?>>Cargo
                                </option>
                                <option value="depto_user" <?= $ordenar == 'depto_user' ? 'selected' : null ?>>Depto
                                </option>
                                <option value="nivel_user" <?= $ordenar == 'nivel_user' ? 'selected' : null ?>>Nível
                                </option>

                            </select>
                        </div>
                        <div class="col-sm-1" style="padding:2px !important" style="margin:0px 0px 20px 0px">
                            <button type="submit" class="btn btn-primary"
                                style="background-color:#5e2363;width:42px;height:32px;margin-top:7px;border-color:#5e2363"><span
                                    class="material-icons" style="margin-left:-3px;margin-top:-2px;">
                                    search
                                </span></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <div id="table-content">
                <!-- <div> -->
                <!-- <h6 class="page-title">Relação de usuários</h6> -->
                <!-- </div> -->
                <table class="table table-sm table-striped table-hover table-condensed">
                    <thead>
                        <tr>
                            <th scope="col">Id</th>
                            <th scope="col">Usuário</th>
                            <th scope="col">CPF</th>
                            <th scope="col">Endereço</th>
                            <th scope="col">Cargo</th>
                            <th scope="col">Depto</th>
                            <th scope="col">Nível</th>
                            <th scope="col">Email</th>
                            <th scope="col">Telefone</th>
                            <th scope="col">Ativo</th>
                            <th scope="col" width="8%">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        foreach ($query as $usuario):
                            extract($usuario);

                            if (strlen($cpf_user) > 0) {
                                $cpf_format = substr($cpf_user, 0, 3) . '.' .
                                    substr($cpf_user, 3, 3) . '.' .
                                    substr($cpf_user, 6, 3) . '-' .
                                    substr($cpf_user, 9, 2);
                            } else {
                                $cpf_format = null;
                            }

                            if (strlen($telefone01_user) > 0) {

                                $telefone01_format = '(' .
                                    substr($telefone01_user, 0, 2) . ') ' .
                                    substr($telefone01_user, 2, 4) . '-' .
                                    substr($telefone01_user, 6, 9);
                            } else {
                                $telefone01_format = null;
                            }

                        ?>
                        <tr style='font-size:15px'>
                            <td scope="row" class="col-id">
                                <?= $id_usuario ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $usuario_user ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $cpf_format ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $endereco_user ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $cargo_user ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $depto_user ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $nivel_user ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $email_user ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $telefone01_format ?>
                            </td>
                            <td scope="row" class="nome-coluna-table">
                                <?= $ativo_user ?>
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
                                                onclick="openModal('<?= $BASE_URL ?>show_usuario.php?id_usuario=<?= $id_usuario ?>')"
                                                data-bs-toggle="modal" data-bs-target="#myModal"><i class="fas fa-eye"
                                                    style="font-size: 1rem;margin-right:5px; color: rgb(27,156, 55);"></i>Ver</button>
                                        </li>
                                        <li>
                                            <button class="btn btn-default" style="font-size: .9rem;"
                                                onclick="openModal('<?= $BASE_URL ?>edit_usuario.php?id_usuario=<?= $id_usuario ?>')"
                                                data-bs-toggle="modal" data-bs-target="#myModal"><i
                                                    style="font-size: 1rem;margin-right:5px; color: rgb(67, 125, 525);"
                                                    name="type" value="edite"
                                                    class="far fa-edit edit-icon"></i>Editar</button>
                                        </li>
                                        <li>

                                            <button onclick="resetSenha('<?= $id_usuario ?>')" class="btn btn-default"
                                                style="font-size: .9rem;"><i
                                                    style="font-size: 1rem;margin-right:5px; color: purple;" name="type"
                                                    value="edite"
                                                    class="fa-solid fa-arrow-rotate-left edit-icon"></i>Resetar
                                                Senha</button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- salvar variavel qtdIntItens no PHP para passar para JS -->
                <div style="text-align:right">
                    <input type="hidden" id="qtd" value="<?php echo $qtdIntItens ?>">
                </div>

                <!-- paginacao que aparece abaixo da tabela -->
                <div style="display: flex;margin-top:20px">

                    <!-- Modal para abrir tela de cadastro -->
                    <div class="modal fade" id="myModal">
                        <div class="modal-dialog  modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="page-title" style="color:white;">Usuário</h4>
                                    <p class="page-description" style="color:white; margin-top:5px">Adicione
                                        informações
                                        sobre o usuário</p>
                                </div>
                                <div class="modal-body">
                                    <div id="content-php"></div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- Modal para abrir tela de cadastro -->

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
                                    onclick="loadContent('list_usuario.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print 1 ?>&bl=<?php print 0 ?>')">
                                    <i class="fa-solid fa-angles-left"></i></a>
                            </li>
                            <?php endif; ?>
                            <?php if ($current_block <= $last_block && $last_block > 1 && $current_block != 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="#"
                                    onclick="loadContent('list_usuario.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $paginaAtual - 1 ?>&bl=<?php print $blocoAtual - 5 ?>')">
                                    <i class="fa-solid fa-angle-left"></i> </a>
                            </li>
                            <?php endif; ?>

                            <?php for ($i = $first_page_in_block; $i <= $last_page_in_block; $i++): ?>
                            <li class="page-item <?php print ($_GET['pag'] ?? 1) == $i ? "active" : "" ?>">

                                <a class="page-link" href="#"
                                    onclick="loadContent('list_usuario.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $i ?>&bl=<?php print $blocoAtual ?>')">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($current_block < $last_block): ?>
                            <li class="page-item">
                                <a class="page-link" id="blocoNovo" href="#"
                                    onclick="loadContent('list_usuario.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $paginaAtual + 1 ?>&bl=<?php print $blocoAtual + 5 ?>')"><i
                                        class="fa-solid fa-angle-right"></i></a>
                            </li>
                            <?php endif; ?>
                            <?php if ($current_block < $last_block): ?>
                            <li class="page-item">
                                <a class="page-link" id="blocoNovo" href="#"
                                    onclick="loadContent('list_internacao.php?pesquisa_nome=<?php print $pesquisa_nome ?>&pesquisa_pac=<?php print $pesquisa_pac ?>&pesqInternado=<?php print $pesqInternado ?>&limite_pag=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print count($paginas) ?>&bl=<?php print ($last_block - 1) * 5 ?>')"><i
                                        class="fa-solid fa-angles-right"></i></a>
                            </li>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </div>

                    <div class="table-counter">
                        <p
                            style="margin-bottom:25px;font-size:1em; font-weight:600; font-family:var(--bs-font-sans-serif); text-align:right">
                            <?php echo "Total: " . $qtdIntItens ?>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>


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


function resetSenha(id_user) {
    // Impede o comportamento padrão ao acionar o evento, caso necessário
    event.preventDefault();

    // Verifica se o id_user é válido
    if (!id_user) {
        console.error("ID do usuário não fornecido.");
        $('#responseMessage').html('ID do usuário inválido.');
        return;
    }

    // Dados a serem enviados para o backend
    const formData = {
        id: id_user
    };

    // Faz a requisição AJAX
    $.ajax({
        url: 'process_reset_senha.php', // Substitua pelo caminho correto do backend
        type: 'POST',
        data: formData,
        success: function(response) {
            console.log("Sucesso:", response);

            $('#responseMessage').html('Senha resetada com sucesso.');
        },
        error: function(xhr, status, error) {
            console.error("Erro:", error);
            $('#responseMessage').html('Ocorreu um erro ao processar a solicitação.');
        }
    });
}


$(document).ready(function() {
    loadContent(
        'list_usuario.php?pesquisa_nome=<?php print $pesquisa_nome ?>&limite_pag=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print 1 ?>&bl=<?php print 0 ?>'
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


<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
src = "https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js";
</script>