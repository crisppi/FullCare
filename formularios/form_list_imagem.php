<body>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <?php
    include_once("globals.php");
    include_once("models/imagem.php");
    include_once("models/message.php");
    include_once("dao/imagemDao.php");
    include_once("templates/header.php");
    include_once("array_dados.php");

    //Instanciando a classe
    $imagem = new imagemDAO($conn, $BASE_URL);
    $QtdTotalPat = new imagemDAO($conn, $BASE_URL);

    // METODO DE BUSCA DE PAGINACAO
    $busca = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $buscaAtivo = filter_input(INPUT_GET, 'ativo_pac', FILTER_SANITIZE_SPECIAL_CHARS);
    $limite = filter_input(INPUT_GET, 'limite') ? filter_input(INPUT_GET, 'limite') : 10;
    $ordenar = filter_input(INPUT_GET, 'ordenar') ? filter_input(INPUT_GET, 'ordenar') : 1;

    // MONTAR CONDICOES //
    $condicoes = [
        strlen($busca) ? 'imagem_pat LIKE "%' . $busca . '%"' : null,
    ];
    $condicoes = array_filter($condicoes);

    // REMOVE POSICOES VAZIAS DO FILTRO //
    $where = implode(' AND ', $condicoes);

    $qtdPatItens1 = $QtdTotalPat->QtdImagem($where);
    $qtdPatItens = ($qtdPatItens1['qtd']);

    $totalcasos = ceil($qtdPatItens / $limite);

    // PAGINACAO
    $obPagination = new pagination($qtdPatItens, $_GET['pag'] ?? 1, $limite ?? 10);
    $obLimite = $obPagination->getLimit();
    $order = $ordenar;

    // PREENCHIMENTO DO FORMULARIO COM QUERY
    $query = $imagem->selectAllImagem($where, $order, $obLimite);
    ?>

    <!--tabela evento-->
    <div class="container py-2">

        <div class="row">
            <h2 class="page-title">Imagem</h2>

            <form class="formulario" id="form_pesquisa" method="GET">
                <div class="form-group row">
                    <h6 class="page-title" style="margin-top:10px">Selecione itens para efetuar Pesquisa</h6>
                    <div class="form-group col-sm-2">
                        <label>Pesquisa por imagem</label>
                        <input type="text" value="<?= $busca ?>" name="pesquisa_nome" style="border:0em"
                            id="pesquisa_nome" autofocus placeholder="Pesquisa por imagem">
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
                        <select class="form-control mb-3" id="ordenar" name="ordenar">
                            <option value="">Classificar por</option>
                            <option value="id_imagem" <?= $ordenar == 'id_imagem' ? 'selected' : null ?>>Id imagem
                            </option>
                            <option value="imagem_pat" <?= $ordenar == 'imagem_pat' ? 'selected' : null ?>>Imagem
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-sm-1" style="margin:20px 0px 10px 60px">
                        <button type="submit" class="btn btn-primary mb-1 btn-int-pesq"><span class="material-icons">
                                person_search
                            </span></button>
                    </div>
                </div>
            </form>

            <?php
            // PREENCHIMENTO DO FORMULARIO COM QUERY
            $query = $imagem->selectAllimagem($where, $order, $obLimite);

            // GETS 
            unset($_GET['pag']);
            unset($_GET['pg']);
            $gets = http_build_query($_GET);

            // PAGINACAO
            $paginacao = '';
            $paginas = $obPagination->getPages();

            foreach ($paginas as $pagina) {
                $class = $pagina['atual'] ? 'btn-primary' : 'btn-light';
                $paginacao .= '<a href="?pag=' . $pagina['pg'] . '&' . $gets . '"> 
               <button type="button" class="btn ' . $class . '">' . $pagina['pg'] . '</button>
               </a>';
            }

            ?>
        </div>
        <div>
            <h4 class="page-title">Relação de imagems</h4>
        </div>
        <table class="table table-sm table-striped  table-hover table-condensed">
            <thead>
                <tr>
                    <th scope="col">Id</th>
                    <th scope="col">imagem</th>
                    <th scope="col">Endereço</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php

                foreach ($query as $imagem) :
                    extract($imagem);

                    // header("content-type:image/png");
                    // print_r($imagem);
                    $img = file_get_contents($query['3']['imagem_img']);
                    $dataImg = base64_encode($img);

                    // Mostra o resultado
                    // echo $dataImg;
                    $imgSrc = 'data: ' . mime_content_type($img) . ';base64,' . $dataImg;

                    echo '<img src="' . $imgSrc . '">';
                ?>
                <tr>
                    <td scope="row" class="col-id"><?= $id_imagem ?></td>
                    <td scope="row" class="nome-coluna-table"><?= $fk_imagem ?></td>
                    <td scope="row" class="nome-coluna-table"><?= $imagem_img ?></td>

                    <td class="action">
                        <!-- <a href="cad_imagem.php"><i name="type" value="create" style="color:green; margin-right:10px" class="bi bi-plus-square-fill edit-icon"></i></a> -->
                        <a href="<?= $BASE_URL ?>show_imagem.php?id_imagem=<?= $id_imagem ?>"><i
                                style="color:green; margin-right:10px" class="fas fa-eye check-icon"></i></a>

                        <a href="<?= $BASE_URL ?>edit_imagem.php?id_imagem=<?= $id_imagem ?>"><i style="color:blue"
                                name="type" value="edite" class="aparecer-acoes far fa-edit edit-icon"></i></a>

                        <a href="<?= $BASE_URL ?>show_imagem.php?id_imagem=<?= $id_imagem ?>"><i
                                style="color:red; margin-left:10px" name="type" value="edite"
                                class="d-inline-block bi bi-x-square-fill delete-icon"></i></a>

                        <div id="info"></div>
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
            echo " <li class='page-item'><a class='page-link' href='list_imagem.php?pag=1&" . $gets . "''><span aria-hidden='true'>&laquo;</span></a></li>"; ?>
            <?= $paginacao ?>
            <?php echo "<li class='page-item'><a class='page-link' href='list_imagem.php?pag=$totalcasos&" . $gets . "''><span aria-hidden='true'>&raquo;</span></a></li>";
            echo " </ul>";
            echo "</nav>";
            echo "</div>"; ?>
            <hr>
        </div>
        <div id="id-confirmacao" class="btn_acoes oculto">
            <p>Deseja deletar pate hospital: <?= $hospital_ant ?>?</p>
            <button class="btn btn-success styled" onclick=cancelar() type="button" id="cancelar"
                name="cancelar">Cancelar</button>
            <button class="btn btn-danger styled" onclick=deletar() value="default" type="button" id="deletar-btn"
                name="deletar">Deletar</button>
        </div>
    </div>

    <div>
        <hr>
        <a class="btn btn-success styled" style="margin-left:120px" href="cad_imagem.php">Nova imagem</a>
    </div>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/load/form_list_imagem.js"></script>