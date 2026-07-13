<?php

include_once("models/hospitalUser.php");
include_once("dao/hospitalUserDao.php");

include_once("models/message.php");

include_once("array_dados.php");

include_once("models/pagination.php");

// Debug simples por querystring (?debug=1)
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
$__t0 = microtime(true);

//Instanciando a classe 
$hospitalUser = new hospitalUserDAO($conn, $BASE_URL);
$QtdTotalpac = new hospitalUserDAO($conn, $BASE_URL);
// $QtdTotalHosp = new hospitalUserDAO($conn, $BASE_URL);
$obLimite = null;
// // METODO DE BUSCA DE PAGINACAO
$busca = filter_input(INPUT_GET, 'pesquisa_nome') ? filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS) : "";
$busca_user = filter_input(INPUT_GET, 'pesquisa_user') ? filter_input(INPUT_GET, 'pesquisa_user', FILTER_SANITIZE_SPECIAL_CHARS) : "";
// $buscaAtivo = filter_input(INPUT_GET, 'ativo_user');
$limite = filter_input(INPUT_GET, 'limite') ? filter_input(INPUT_GET, 'limite') : 10;
$ordenar = filter_input(INPUT_GET, 'ordenar') ? filter_input(INPUT_GET, 'ordenar') : 1;
$QtdTotalhosp = new hospitalUserDAO($conn, $BASE_URL);
// $buscaAtivo = in_array($buscaAtivo, ['s', 'n']) ?: "";
$condicoes = [
    strlen($busca) ? '(nome_hosp LIKE "%' . $busca . '%" OR cnpj_hosp LIKE "%' . $busca . '%")' : null,
    strlen($busca_user) ? '(usuario_user LIKE "%' . $busca_user . '%" OR email_user LIKE "%' . $busca_user . '%")' : null,
    'ativo_user = "s"'
];

$condicoes = array_filter($condicoes);
$order = $ordenar;
// REMOVE POSICOES VAZIAS DO FILTRO
$where = implode(' AND ', $condicoes);

$qtdRow = $QtdTotalpac->QtdhospitalUser($where);
$qtdIntItens = (int)($qtdRow['qtd'] ?? 0); // total de registros
$order = $ordenar;

// PAGINACAO
$obPagination = new pagination($qtdIntItens, $_GET['pag'] ?? 1, $limite ?? 10);
$obLimite = $obPagination->getLimit();

// PREENCHIMENTO DO FORMULARIO COM QUERY
$query = $hospitalUser->selectAllhospitalUser($where, $order, $obLimite);

$__t1 = microtime(true);

if (!function_exists('formatCargoLabel')) {
    function formatCargoLabel(?string $cargo): string
    {
        $cargo = trim((string)$cargo);
        $map = [
            'Med_auditor' => 'Médico Auditor',
            'Enf_auditor' => 'Enfermeiro Auditor',
            'Enf_Auditor' => 'Enfermeiro Auditor',
        ];

        return $map[$cargo] ?? $cargo;
    }
}

// GETS 
// unset($_GET['pag']);
// $gets = http_build_query($_GET['pag']);


// PAGINACAO
$paginacao = '';
$paginas = $obPagination->getPages();

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
};
?>

<link rel="stylesheet" href="<?= htmlspecialchars(rtrim($BASE_URL, '/') . '/css/listagem_padrao.css?v=' . @filemtime(__DIR__ . '/../css/listagem_padrao.css'), ENT_QUOTES, 'UTF-8') ?>">

<div class="container-fluid form_container listagem-page hospital-user-list-page" style="margin-top:18px;">
    <?php if ($debug): ?>
        <div class="alert alert-warning" style="font-size:0.9rem;">
            <strong>DEBUG list_hospitalUser</strong><br>
            where: <?= htmlspecialchars($where, ENT_QUOTES, 'UTF-8') ?><br>
            order: <?= htmlspecialchars((string)$order, ENT_QUOTES, 'UTF-8') ?><br>
            limit: <?= htmlspecialchars((string)$obLimite, ENT_QUOTES, 'UTF-8') ?><br>
            total: <?= (int)$qtdIntItens ?><br>
            query_count: <?= is_array($query) ? count($query) : 0 ?><br>
            tempo: <?= number_format((($__t1 ?? microtime(true)) - $__t0), 4, '.', '') ?>s
        </div>
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <div class="listagem-hero listagem-hero--module listagem-hero--cadastros">
        <div class="listagem-hero__copy">
            <div class="listagem-kicker">Cadastros</div>
            <h1 class="listagem-title">Usuários por hospital</h1>
        </div>
        <div class="listagem-hero__actions">
            <a href="exportar_excel_list_hosp_user.php" class="btn listagem-btn-top listagem-btn-top--green">
                <i class="bi bi-file-earmark-excel listagem-btn-top__icon" aria-hidden="true"></i>
                <span>Exportar Excel</span>
            </a>

            <button onclick="openModal('cad_hospitalUser.php')" data-bs-toggle="modal" data-bs-target="#myModal"
                class="btn listagem-btn-top listagem-btn-top--blue">
                <i class="bi bi-plus-circle listagem-btn-top__icon" aria-hidden="true"></i>
                <span>Novo vínculo</span>
            </button>
        </div>
    </div>
    <div class="complete-table listagem-panel">
        <div id="navbarToggleExternalContent" class="table-filters">
            <form id="form_pesquisa" method="GET">
                <div class="filter-inline-row hospital-user-filter-row">

                    <div class="filter-inline-field hospital-user-filter--hospital">
                        <input class="form-control form-control-sm" type="text"
                            name="pesquisa_nome" placeholder="Selecione o Hospital (nome ou CNPJ)"
                            value="<?= $busca ?>">
                        <?php isset($_get['pesquisa_nome']) ? $_get['pesquisa_nome'] : ""; ?>
                    </div>
                    <div class="filter-inline-field hospital-user-filter--user">
                        <input class="form-control form-control-sm" type="text"
                            name="pesquisa_user" placeholder="Selecione o Usuário (nome ou email)"
                            value="<?= $busca_user ?>">
                    </div>
                    <div class="filter-inline-field hospital-user-filter--limit">
                        <select class="form-control form-control-sm" id="limite" name="limite">
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
                    <div class="filter-inline-field hospital-user-filter--sort">
                        <select class="form-control form-control-sm" id="ordenar" name="ordenar">
                            <option value="">Classificar por</option>
                            <option value="usuario_user" <?= $ordenar == 'usuario_user' ? 'selected' : null ?>>Usuário
                            </option>
                            <option value="nome_hosp" <?= $ordenar == 'nome_hosp' ? 'selected' : null ?>>Hospital
                            </option>
                        </select>
                    </div>
                    <div class="filter-inline-field hospital-user-filter--action">
                        <button type="submit" class="btn btn-primary btn-filtro-buscar btn-filtro-limpar-icon"
                            style="background-color:#5e2363;border-color:#5e2363"><span class="material-icons">
                                search
                            </span></button>
                    </div>
                </div>
            </form>

        </div>
        <div>
            <div id="table-content">
                <?php
                $groupedHospitais = [];
                foreach ($query as $hospitalUserSel) {
                    $groupKey = (string)($hospitalUserSel['nome_hosp'] ?? 'Sem hospital');
                    if (!isset($groupedHospitais[$groupKey])) {
                        $groupedHospitais[$groupKey] = [];
                    }
                    $groupedHospitais[$groupKey][] = $hospitalUserSel;
                }
                ?>

                <div class="hospital-user-groups">
                    <?php foreach ($groupedHospitais as $hospitalNome => $usuariosHospital): ?>
                        <section class="hospital-user-group">
                            <div class="hospital-user-group__header">
                                <div>
                                    <div class="hospital-user-group__title"><?= htmlspecialchars((string)$hospitalNome, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="hospital-user-group__meta">
                                        <?= count($usuariosHospital) ?> usuário<?= count($usuariosHospital) !== 1 ? 's' : '' ?> vinculado<?= count($usuariosHospital) !== 1 ? 's' : '' ?>
                                    </div>
                                </div>
                            </div>

                            <div class="hospital-user-group__rows">
                                <?php foreach ($usuariosHospital as $hospitalUserSel):
                                    extract($hospitalUserSel);
                                    ?>
                                    <article class="hospital-user-card">
                                        <div class="hospital-user-card__main">
                                            <div class="hospital-user-card__name"><?= htmlspecialchars((string)$usuario_user, ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="hospital-user-card__email"><?= htmlspecialchars((string)$email_user, ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>

                                        <div class="hospital-user-card__chips">
                                            <span class="hospital-user-chip">Usuário #<?= (int)$fk_usuario_hosp ?></span>
                                            <span class="hospital-user-chip"><?= htmlspecialchars(formatCargoLabel((string)$cargo_user), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="hospital-user-chip">Nível <?= htmlspecialchars((string)$nivel_user, ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>

                                        <div class="hospital-user-card__actions fc-list-action">
                                            <div class="dropdown">
                                                <button class="btn btn-default dropdown-toggle" id="acoesHospitalUserDropdown<?= (int)$id_hospitalUser ?>"
                                                    role="button" data-bs-toggle="dropdown" style="color:#2f6f9f"
                                                    aria-expanded="false">
                                                    <i class="bi bi-stack"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="acoesHospitalUserDropdown<?= (int)$id_hospitalUser ?>">
                                                    <li>
                                                        <button class="btn btn-default"
                                                            onclick="window.location.href='<?= htmlspecialchars(rtrim($BASE_URL, '/') . '/usuarios/editar/' . (int) $fk_usuario_hosp, ENT_QUOTES, 'UTF-8') ?>'">
                                                            <i class="bi bi-person-gear" style="color:#2f6f9f"></i>Editar usuário
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button data-bs-toggle="modal" data-bs-target="#myModal"
                                                            class="btn btn-default"
                                                            onclick="openModal('<?= $BASE_URL ?>edit_hospitalUser.php?id_hospitalUser=<?= $id_hospitalUser ?>')">
                                                            <i class="bi bi-pencil-square" style="color:#2f7d58"></i>Editar vínculo</button>
                                                    </li>
                                                    <li>
                                                        <form class="d-inline-block delete-form" action="del_hosp_user.php"
                                                            method="post">
                                                            <input type="hidden" name="type" value="delete">
                                                            <input type="hidden" name="id_hospitalUser"
                                                                value="<?= $id_hospitalUser ?>">
                                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                                            <button class="btn btn-default"><i
                                                                    style="font-size: 1rem;margin-right:5px; color: red;"
                                                                    class="bi bi-x-circle-fill"></i>Deletar</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>

                    <?php if ($qtdIntItens == 0): ?>
                        <div class="hospital-user-empty">
                            Não foram encontrados registros
                        </div>
                    <?php endif ?>
                </div>
                <!-- Modal para abrir tela de cadastro -->
                <div class="modal fade" id="myModal">
                    <div class="modal-dialog  modal-dialog-centered modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="page-title" style="color:white;">Hospital</h4>
                                <p class="page-description" style="color:white; margin-top:5px">Informações
                                    sobre o Hospital</p>
                            </div>
                            <div class="modal-body">
                                <div id="content-php"></div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- salvar variavel qtdIntItens no PHP para passar para JS -->
                <div style="text-align:right">
                    <input type="hidden" id="qtd" value="<?php echo $qtdIntItens ?>">
                </div>

                <!-- paginacao que aparece abaixo da tabela -->
                <div style="display: flex;margin-top:20px">

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
                                    onclick="loadContent('list_hospitalUser.php?pesquisa_nome=<?php print $pesquisa_nome ?>&pesquisa_user=<?php print $busca_user ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print 1 ?>&bl=<?php print 0 ?>')">
                                    <i class="fas fa-angle-double-left"></i></a>
                            </li>
                            <?php endif; ?>
                            <?php if ($current_block <= $last_block && $last_block > 1 && $current_block != 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="#"
                                    onclick="loadContent('list_hospitalUser.php?pesquisa_nome=<?php print $busca ?>&pesquisa_user=<?php print $busca_user ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $paginaAtual - 1 ?>&bl=<?php print $blocoAtual - 5 ?>')">
                                    <i class="fas fa-angle-left"></i> </a>
                            </li>
                            <?php endif; ?>

                            <?php for ($i = $first_page_in_block; $i <= $last_page_in_block; $i++): ?>
                            <li class="page-item <?php print ($_GET['pag'] ?? 1) == $i ? "active" : "" ?>">

                                <a class="page-link" href="#"
                                    onclick="loadContent('list_hospitalUser.php?pesquisa_nome=<?php print $busca ?>&pesquisa_user=<?php print $busca_user ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $i ?>&bl=<?php print $blocoAtual ?>')">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($current_block < $last_block): ?>
                            <li class="page-item">
                                <a class="page-link" id="blocoNovo" href="#"
                                    onclick="loadContent('list_hospitalUser.php?pesquisa_nome=<?php print $busca ?>&pesquisa_user=<?php print $busca_user ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print $paginaAtual + 1 ?>&bl=<?php print $blocoAtual + 5 ?>')"><i
                                        class="fas fa-angle-right"></i></a>
                            </li>
                            <?php endif; ?>
                            <?php if ($current_block < $last_block): ?>
                            <li class="page-item">
                                <a class="page-link" id="blocoNovo" href="#"
                                    onclick="loadContent('list_hospitalUser.php?pesquisa_nome=<?php print $busca ?>&pesquisa_user=<?php print $busca_user ?>&pesquisa_pac=<?php print $pesquisa_pac ?>&pesqInternado=<?php print $pesqInternado ?>&limite_pag=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print count($paginas) ?>&bl=<?php print ($last_block - 1) * 5 ?>')"><i
                                        class="fas fa-angle-double-right"></i></a>
                            </li>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </div>


                    <div class="table-counter">
                        <p
                            style="margin-bottom:25px; font-size:1em; font-weight:600; font-family:var(--bs-font-sans-serif); text-align:right">
                            <?php echo "Total: " . $qtdIntItens ?>
                        </p>
                    </div>

                </div>
            </div>

        </div>
        <div id="id-confirmacao" class="btn_acoes oculto">
            <p>Deseja deletar este Relacionamento?</p>
            <button class="btn btn-success styled" onclick=cancelar() type="button" id="cancelar"
                name="cancelar">Cancelar</button>
            <button class="btn btn-danger styled" onclick=deletar() value="default" type="button" id="deletar-btn"
                name="deletar">Deletar</button>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
<script src="<?= $BASE_URL ?>scripts/cadastro/general.js"></script>
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

</script>
<style>
.hospital-user-list-page {
    padding-inline: 4px;
}

.hospital-user-list-page .listagem-hero--module {
    margin-bottom: 10px;
}

.hospital-user-list-page .listagem-panel,
.hospital-user-list-page .complete-table,
.hospital-user-list-page #table-content {
    overflow: visible !important;
}

.hospital-user-filter-row {
    margin-bottom: 8px;
}

.hospital-user-filter-row .filter-inline-field {
    min-width: 0;
}

.hospital-user-filter--hospital,
.hospital-user-filter--user {
    flex: 1 1 260px;
}

.hospital-user-filter--limit {
    flex: 0 0 150px;
}

.hospital-user-filter--sort {
    flex: 0 0 230px;
}

.hospital-user-filter--action {
    flex: 0 0 36px;
}

.hospital-user-groups {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.hospital-user-group {
    border: 1px solid rgba(94, 35, 99, 0.16);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 7px 16px rgba(37, 18, 54, .065);
    overflow: visible;
}

.hospital-user-group + .hospital-user-group {
    margin-top: 2px;
}

.hospital-user-group__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding: 8px 12px 8px 14px;
    background: linear-gradient(180deg, #f3edf8 0%, #ebe2f2 100%);
    color: #3b2941;
    border-bottom: 1px solid rgba(94, 35, 99, 0.16);
    border-left: 4px solid #7b4d8a;
}

.hospital-user-group__title {
    font-size: .76rem;
    font-weight: 800;
    letter-spacing: 0;
    line-height: 1.2;
}

.hospital-user-group__meta {
    margin-top: 2px;
    color: #8a7f93;
    font-size: .64rem;
    font-weight: 700;
    line-height: 1.15;
}

.hospital-user-group__rows {
    display: flex;
    flex-direction: column;
}

.hospital-user-card {
    position: relative;
    display: grid;
    grid-template-columns: minmax(240px, 1.35fr) minmax(360px, 1.1fr) auto;
    gap: 10px;
    align-items: center;
    min-height: 44px;
    padding: 8px 12px;
    border-top: 1px solid rgba(94, 35, 99, 0.07);
}

.hospital-user-card:first-child {
    border-top: 0;
}

.hospital-user-card:nth-child(odd) {
    background: #fff;
}

.hospital-user-card:nth-child(even) {
    background: #fbf9fd;
}

.hospital-user-card__name {
    color: #2b2230;
    font-size: .78rem;
    font-weight: 800;
    line-height: 1.15;
}

.hospital-user-card__email {
    margin-top: 2px;
    color: #6f6478;
    font-size: .70rem;
    font-weight: 500;
    line-height: 1.15;
    word-break: break-word;
}

.hospital-user-card__chips {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 6px;
}

.hospital-user-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 92px;
    min-height: 24px;
    padding: 3px 9px;
    border-radius: 999px;
    border: 1px solid rgba(94, 35, 99, 0.12);
    background: #f4eef7;
    color: #5e2363;
    font-size: .66rem;
    font-weight: 800;
    line-height: 1;
    text-align: center;
}

.hospital-user-card__actions {
    position: relative;
    z-index: 5;
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.hospital-user-list-page .hospital-user-card__actions .dropdown-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-width: 32px;
    min-height: 28px;
    padding: 0 8px !important;
    border: 1px solid #c8deeb;
    border-radius: 10px !important;
    background: #fff;
    color: #2f6f9f !important;
    font-size: .68rem;
    line-height: 1;
}

.hospital-user-list-page .hospital-user-card__actions .dropdown-toggle i {
    margin: 0;
    font-size: .9rem;
    line-height: 1;
}

.hospital-user-list-page .hospital-user-card__actions .dropdown-toggle::after {
    margin-left: 2px;
    font-size: .62rem;
    color: #2f6f9f;
}

.hospital-user-list-page .hospital-user-card__actions .dropdown-menu {
    z-index: 3060;
    min-width: 170px;
    max-width: 190px;
    padding: 5px 0;
    border-radius: 10px;
}

.hospital-user-list-page .hospital-user-card__actions .dropdown-menu .btn-default,
.hospital-user-list-page .hospital-user-card__actions .dropdown-menu .dropdown-item {
    min-height: 28px !important;
    padding: 5px 10px !important;
    margin: 1px 5px !important;
    gap: 8px !important;
    font-size: .72rem !important;
    line-height: 1.1 !important;
    font-weight: 500 !important;
}

.hospital-user-list-page .hospital-user-card__actions .dropdown-menu .btn-default i,
.hospital-user-list-page .hospital-user-card__actions .dropdown-menu .dropdown-item i {
    min-width: 16px !important;
    margin-right: 0 !important;
    font-size: .86rem !important;
    line-height: 1 !important;
}

.hospital-user-empty {
    padding: 14px;
    text-align: center;
    border: 1px dashed rgba(94, 35, 99, 0.18);
    border-radius: 8px;
    color: #6f617a;
    background: #fff;
    font-size: .72rem;
    font-weight: 700;
}

.hospital-user-list-page .table-counter p {
    margin-bottom: 10px !important;
    color: #3b2941;
    font-size: .72rem !important;
}

@media (max-width: 991.98px) {
    .hospital-user-card {
        grid-template-columns: 1fr;
        align-items: flex-start;
    }

    .hospital-user-card__actions {
        justify-content: flex-start;
    }
}

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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
