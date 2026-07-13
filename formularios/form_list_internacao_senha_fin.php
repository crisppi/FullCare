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

$internacao_geral = new internacaoDAO($conn, $BASE_URL);
$internacaos = $internacao_geral->findGeral();

$pacienteDao = new pacienteDAO($conn, $BASE_URL);
$pacientes = $pacienteDao->findGeral($limite, $inicio);

$capeante_geral = new capeanteDAO($conn, $BASE_URL);
$capeante = $capeante_geral->findGeral($limite, $inicio);

$hospital_geral = new HospitalDAO($conn, $BASE_URL);
$hospitals = $hospital_geral->findGeral($limite, $inicio);

$patologiaDao = new patologiaDAO($conn, $BASE_URL);
$patologias = $patologiaDao->findGeral();

$internacao = new internacaoDAO($conn, $BASE_URL);

$pesqInternado = null;

$pesquisa_nome = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$senha_fin = 's';
$med_check = filter_input(INPUT_GET, 'med_check', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$enf_check = filter_input(INPUT_GET, 'enf_check', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$adm_check = filter_input(INPUT_GET, 'adm_check', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$limite = filter_input(INPUT_GET, 'limite') ? filter_input(INPUT_GET, 'limite') : 10;
$pesquisa_pac = filter_input(INPUT_GET, 'pesquisa_pac', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$senha_int = filter_input(INPUT_GET, 'senha_int', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$lote = filter_input(INPUT_GET, 'lote', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$ordenar = filter_input(INPUT_GET, 'ordenar') ? filter_input(INPUT_GET, 'ordenar') : '';
$data_intern_int = filter_input(INPUT_GET, 'data_intern_int') ?: null;
$data_intern_int_max = filter_input(INPUT_GET, 'data_intern_int_max') ?: null;

?>
<?php
// validacao de lista de hospital por usuario (o nivel sera o filtro)
if ($_SESSION['nivel'] == 3 or $_SESSION['nivel'] == 1) {
    $auditor = ($_SESSION['id_usuario']);
} else {
    $auditor = null;
};

//Instanciando a classe
$QtdTotalInt = new internacaoDAO($conn, $BASE_URL);
// METODO DE BUSCA DE PAGINACAO 
$pesquisa_nome = filter_input(INPUT_GET, 'pesquisa_nome', FILTER_SANITIZE_SPECIAL_CHARS);
$senha_fin = 's';
$med_check = filter_input(INPUT_GET, 'med_check', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$enf_check = filter_input(INPUT_GET, 'enf_check', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$adm_check = filter_input(INPUT_GET, 'adm_check', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$limite = filter_input(INPUT_GET, 'limite') ? filter_input(INPUT_GET, 'limite') : 10;
$pesquisa_pac = filter_input(INPUT_GET, 'pesquisa_pac', FILTER_SANITIZE_SPECIAL_CHARS);
$senha_int = filter_input(INPUT_GET, 'senha_int', FILTER_SANITIZE_SPECIAL_CHARS);
$lote = filter_input(INPUT_GET, 'lote', FILTER_SANITIZE_SPECIAL_CHARS);
$ordenar = filter_input(INPUT_GET, 'ordenar') ? filter_input(INPUT_GET, 'ordenar') : '';
$data_intern_int = filter_input(INPUT_GET, 'data_intern_int') ?: null;
$data_intern_int_max = filter_input(INPUT_GET, 'data_intern_int_max');
if (empty($data_intern_int_max)) {
    $data_intern_int_max = date('Y-m-d'); // Formato de data compatível com SQL
}
// $buscaAtivo = in_array($buscaAtivo, ['s', 'n']) ?: "";

$paginaAtualExport = max(1, (int)($_GET['pag'] ?? 1));
$exportBaseParams = [
    'pesquisa_nome' => $pesquisa_nome,
    'pesquisa_pac' => $pesquisa_pac,
    'senha_int' => $senha_int,
    'lote' => $lote,
    'med_check' => $med_check,
    'enf_check' => $enf_check,
    'adm_check' => $adm_check,
    'data_intern_int' => $data_intern_int,
    'data_intern_int_max' => $data_intern_int_max,
    'ordenar' => $ordenar,
    'limite' => $limite,
];
$exportBaseParams = array_filter($exportBaseParams, static function ($value) {
    return $value !== null && $value !== '';
});
$exportFilteredUrl = rtrim($BASE_URL, '/') . '/exportar_excel_senhas_finalizadas.php?' . http_build_query(array_merge($exportBaseParams, [
    'export_scope' => 'filtered',
]));
$exportCurrentPageUrl = rtrim($BASE_URL, '/') . '/exportar_excel_senhas_finalizadas.php?' . http_build_query(array_merge($exportBaseParams, [
    'export_scope' => 'current_page',
    'pag' => $paginaAtualExport,
]));

?>
<link rel="stylesheet" href="<?= htmlspecialchars(rtrim($BASE_URL, '/') . '/css/listagem_padrao.css?v=' . @filemtime(__DIR__ . '/../css/listagem_padrao.css'), ENT_QUOTES, 'UTF-8') ?>">
<style>
    body {
        background: #e5e7eb !important;
        min-height: 100vh;
    }
    .listagem-page { padding: 4px 4px 14px; }
    .senhas-list-page {
        min-height: calc(100vh - 126px);
        padding: 10px 8px 18px !important;
        background: #e5e7eb !important;
    }
    .listagem-title { font-size: .96rem; line-height: 1.05; }
    .listagem-panel { padding: 8px 8px 6px; }
    .senhas-list-page .listagem-panel {
        border: 1px solid #eef2f7 !important;
        border-radius: 12px !important;
        background: #fff !important;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .08) !important;
    }
    .senhas-list-page .table-filters {
        padding: 0 !important;
        background: transparent !important;
    }
    #table-content { margin-top: 0; }
    #table-content tbody td, #table-content tbody th { padding:6px 10px; font-size:.7rem; vertical-align:middle; }
    .senhas-list-page .senhas-filter-row {
        gap: 6px !important;
        margin: 0 !important;
        row-gap: 4px;
        border: 0 !important;
        background: transparent !important;
        padding: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    .senhas-list-page .senhas-filter-row + .senhas-filter-row {
        margin-top: 6px !important;
    }
    .senhas-list-page .senhas-filter-row > [class*="col-"],
    .senhas-list-page .senhas-filter-row > .form-group {
        display: flex;
        align-items: center;
        padding: 0 !important;
    }
    .senhas-list-page .senhas-filter-row .form-control,
    .senhas-list-page .senhas-filter-row .form-control-sm,
    .senhas-list-page .senhas-filter-row .btn {
        min-height: 36px !important;
        height: 36px !important;
        margin: 0 !important;
        border-radius: 8px;
        font-size: .74rem !important;
        line-height: 1.25;
    }
    .senhas-list-page .senhas-filter-row .form-control,
    .senhas-list-page .senhas-filter-row .form-control-sm {
        border: 1px solid #cbd5e1 !important;
        background-color: #f8fbff !important;
        background-image: none !important;
        color: #344054 !important;
        font-weight: 500 !important;
        padding: 0 28px 0 10px !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .95), 0 1px 2px rgba(15, 23, 42, .08) !important;
    }
    .senhas-list-page .senhas-filter-row .form-control::placeholder,
    .senhas-list-page .senhas-filter-row .form-control-sm::placeholder {
        color: #8a94a6 !important;
        font-size: .74rem !important;
        font-weight: 500 !important;
        opacity: 1 !important;
    }
    .senhas-list-page .senhas-filter-row .form-control:focus,
    .senhas-list-page .senhas-filter-row .form-control-sm:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 .14rem rgba(59, 130, 246, .16) !important;
    }
    .senhas-list-page .senhas-filter-row select.form-control,
    .senhas-list-page .senhas-filter-row select.form-control-sm {
        background-color: #f8fbff !important;
        color: #344054 !important;
    }
    .senhas-list-page .senhas-filter-actions {
        gap: 8px;
    }
    .senhas-list-page .senhas-filter-row--top {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        column-gap: 6px !important;
        row-gap: 0 !important;
    }
    .senhas-list-page .senhas-filter-row--top > [class*="col-"],
    .senhas-list-page .senhas-filter-row--top > .form-group {
        flex: 1 1 0 !important;
        max-width: none !important;
        min-width: 0 !important;
        padding: 0 !important;
    }
    .senhas-list-page .senhas-filter-row--top > :nth-child(1),
    .senhas-list-page .senhas-filter-row--top > :nth-child(2) {
        flex-grow: 1.55 !important;
    }
    .senhas-list-page .senhas-filter-row--top > :nth-child(3),
    .senhas-list-page .senhas-filter-row--top > :nth-child(6) {
        flex-grow: 1.02 !important;
    }
    .senhas-list-page .senhas-filter-row--top > :nth-child(4),
    .senhas-list-page .senhas-filter-row--top > :nth-child(5) {
        flex-grow: .62 !important;
    }
</style>
<!-- FORMULARIO DE PESQUISAS -->
<div class="container-fluid listagem-page senhas-list-page" id="main-container">
    <div class="listagem-hero listagem-hero--module listagem-hero--contas">
        <div class="listagem-hero__copy">
            <div class="listagem-kicker">Contas finalizadas</div>
            <h1 class="listagem-title">Capeantes com senha finalizada</h1>
        </div>
        <div class="listagem-hero__actions">
            <div class="dropdown fc-export-dropdown">
                <button type="button" class="btn listagem-btn-top listagem-btn-top--green dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-file-excel listagem-btn-top__icon" aria-hidden="true"></i>
                    Exportar Excel
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="<?= htmlspecialchars($exportFilteredUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="dropdown-item js-senhas-export-link" data-export-scope="filtered">
                        <span class="fc-export-dropdown__title">Exportar todos os resultados filtrados</span>
                        <span class="fc-export-dropdown__help">Inclui todos os registros encontrados pelos filtros atuais.</span>
                    </a>
                    <a href="<?= htmlspecialchars($exportCurrentPageUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="dropdown-item js-senhas-export-link" data-export-scope="current_page">
                        <span class="fc-export-dropdown__title">Exportar apenas esta página</span>
                        <span class="fc-export-dropdown__help">Inclui somente os registros visíveis agora.</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="complete-table listagem-panel">
        <div id="navbarToggleExternalContent" class="table-filters">
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            <script src="./js/ajaxNav.js"></script>
            <script src="js/scriptPdf.js" defer> </script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
                integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg=="
                crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <form action="" id="select-internacao-form" method="GET">

                <div class="row filter-inline-row senhas-filter-row senhas-filter-row--top">
                    <div class="form-group col-sm-3">
                        <input class="form-control form-control-sm" style="margin-top:7px;font-size:.8em; color:#878787"
                            type="text" name="pesquisa_nome" placeholder="Selecione o Hospital"
                            value="<?= $pesquisa_nome ?>">
                    </div>
                    <div class="form-group col-sm-3">
                        <input class="form-control form-control-sm" style="margin-top:7px;font-size:.8em; color:#878787"
                            type="text" name="pesquisa_pac" placeholder="Selecione o Paciente" value="<?= $pesquisa_pac ?>">
                    </div>
                    <div class="form-group col-sm-2">
                        <input class="form-control form-control-sm" style="margin-top:7px; font-size:.8em; color:#878787"
                            type="text" name="senha_int" placeholder="Digite a Senha" value="<?= $senha_int ?>">
                    </div>
                    <div class="form-group col-sm-1" style="padding:2px !important">
                        <input class="form-control form-control-sm" style="margin-top:7px; font-size:.8em; color:#878787"
                            type="text" name="lote" placeholder="Digite o lote" value="<?= $lote ?>">
                    </div>
                    <div class="col-sm-1" style="padding:2px !important">
                        <select class="form-control mb-3 form-control-sm" style="margin-top:7px;" id="limite" name="limite">
                            <option value="">Reg/pág</option>
                            <option value="5" <?= $limite == '5' ? 'selected' : null ?>>5
                            </option>
                            <option value="10" <?= $limite == '10' ? 'selected' : null ?>>10
                            </option>
                            <option value="20" <?= $limite == '20' ? 'selected' : null ?>>20
                            </option>
                            <option value="50" <?= $limite == '50' ? 'selected' : null ?>>50
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-sm-2">
                        <select class="form-control mb-3 form-control-sm"
                            style="margin-top:7px;font-size:.8em; color:#878787" id="ordenar" name="ordenar">
                            <option value="">Classificar por</option>
                            <option value="id_internacao" <?= $ordenar == 'id_internacao' ? 'selected' : null ?>>Internação
                            </option>
                            <option value="nome_pac" <?= $ordenar == 'nome_pac' ? 'selected' : null ?>>Paciente</option>
                            <option value="nome_hosp" <?= $ordenar == 'nome_hosp' ? 'selected' : null ?>>Hospital</option>
                            <option value="data_intern_int" <?= $ordenar == 'data_intern_int' ? 'selected' : null ?>>Data
                                Internação</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row filter-inline-row senhas-filter-row">
                    <div class="form-group col-sm-1">
                        <select class="form-control mb-3 form-control-sm"
                            style="margin-top:7px;font-size:.8em; color:#878787" id="med_check" name="med_check">
                            <option value="">Médico</option>
                            <option value="s" <?= $med_check == 's' ? 'selected' : null ?>>Sim</option>
                            <option value="n" <?= $med_check == 'n' ? 'selected' : null ?>>Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <select class="form-control mb-3 form-control-sm"
                            style="margin-top:7px;font-size:.8em; color:#878787" id="enf_check" name="enf_check">
                            <option value="">Enferm</option>
                            <option value="s" <?= $enf_check == 's' ? 'selected' : null ?>>Sim</option>
                            <option value="n" <?= $enf_check == 'n' ? 'selected' : null ?>>Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-1">
                        <select class="form-control mb-3 form-control-sm"
                            style="margin-top:7px;font-size:.8em; color:#878787" id="adm_check" name="adm_check">
                            <option value="">Adm</option>
                            <option value="s" <?= $adm_check == 's' ? 'selected' : null ?>>Sim</option>
                            <option value="n" <?= $adm_check == 'n' ? 'selected' : null ?>>Não</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-2">
                        <input class="form-control form-control-sm" type="date"
                            style="margin-top:7px;font-size:.8em; color:#878787" name="data_intern_int"
                            placeholder="Data Internação Min" value="<?= $data_intern_int ?>">
                    </div>
                    <div class="form-group col-sm-2">
                        <input class="form-control form-control-sm" type="date"
                            style="margin-top:7px;font-size:.8em; color:#878787" name="data_intern_int_max"
                            placeholder="Data Internação Max" value="<?= $data_intern_int_max ?>">
                    </div>
                    <div class="form-group col-sm-1 d-flex align-items-center senhas-filter-actions">
                        <button type="submit" class="btn btn-primary btn-filtro-buscar btn-filtro-limpar-icon"
                            style="background-color:#5e2363;width:42px;border-color:#5e2363"><span
                                class="material-icons" style="margin-left:-3px;margin-top:-2px;">
                                search
                            </span></button>
                        <a href="<?= htmlspecialchars(rtrim($BASE_URL, '/') . '/list_internacao_senha_fin.php', ENT_QUOTES, 'UTF-8') ?>"
                            class="btn btn-light btn-sm btn-filtro-limpar btn-filtro-limpar-icon"
                            title="Limpar filtros" aria-label="Limpar filtros">
                            <i class="bi bi-trash3"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
<!-- BASE DAS PESQUISAS -->
<?php
// legacy block removed below
$condicoes = [
    strlen($pesquisa_nome) ? 'ho.nome_hosp LIKE "%' . $pesquisa_nome . '%"' : NULL,
    strlen($pesquisa_pac) ? 'pa.nome_pac LIKE "%' . $pesquisa_pac . '%"' : NULL,
    strlen($senha_int) ? 'senha_int LIKE "%' . $senha_int . '%"' : NULL,
    strlen($senha_fin) ? 'senha_finalizada = "' . $senha_fin . '"' : NULL,
    strlen($med_check) ? 'med_check = "' . $med_check . '"' : NULL,
    strlen($enf_check) ? 'enfer_check = "' . $enf_check . '"' : NULL,
    strlen($adm_check) ? 'adm_check = "' . $adm_check . '"' : NULL,
    strlen($data_intern_int) ? 'data_intern_int BETWEEN "' . $data_intern_int . '" AND "' . $data_intern_int_max . '"' : NULL,
    strlen($auditor) ? 'hos.fk_usuario_hosp = "' . $auditor . '"' : NULL,
    strlen($lote) ? 'ca.lote_cap = "' . $lote . '"' : NULL,

];
$condicoes = array_filter($condicoes);
// REMOVE POSICOES VAZIAS DO FILTRO
$where = implode(' AND ', $condicoes);

// QUANTIDADE InternacaoS
$qtdIntItens1 = $QtdTotalInt->QtdInternacaoCapList($where);

$qtdIntItens = ($qtdIntItens1['qtd']) ?? 0;

// PAGINACAO
$order = $ordenar ?: 'id_internacao DESC';

$obPagination = new pagination($qtdIntItens, $_GET['pag'] ?? 1, $limite ?? 10);

$obLimite = $obPagination->getLimit();

// PREENCHIMENTO DO FORMULARIO COM QUERY
$query = $internacao_geral->selectAllInternacaoCapList($where, $order, $obLimite);
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
<div>
    <div id="table-content" class="listagem-table-wrap contas-table-wrap">
        <div class="fc-bulk-print-bar" data-bulk-print-root>
            <span class="fc-bulk-print-count" data-bulk-print-count>0 selecionados</span>
            <button type="button" class="fc-bulk-print-btn" data-bulk-print-modelo="resumido">
                <i class="bi bi-printer"></i> Imprimir selecionados
            </button>
            <button type="button" class="fc-bulk-print-btn fc-bulk-print-btn--primary" data-bulk-print-modelo="completo">
                <i class="bi bi-file-earmark-spreadsheet"></i> Imprimir completos
            </button>
        </div>
        <table class="table table-sm table-striped  table-hover table-condensed">
            <thead>
                <tr>
                    <th scope="col" class="th-w-4">
                        <input type="checkbox" class="fc-bulk-print-check js-capeante-select-all"
                            aria-label="Selecionar todos os capeantes desta página">
                    </th>
                    <th scope="col" class="th-w-4">Reg</th>
                    <th scope="col" class="th-w-6">Conta No.</th>
                    <th scope="col" class="th-w-23">Hospital</th>
                    <th scope="col" class="th-w-23">Paciente</th>
                    <th scope="col" class="th-w-23">Senha</th>
                    <th scope="col" class="th-w-12">Data internação</th>
                    <th scope="col" class="th-w-4">Final</th>
                    <th scope="col" class="th-w-13">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($query as $intern) :
                    extract($intern);

                ?>
                        <tr>
                    <td scope="row" class="col-id">
                        <input type="checkbox" class="fc-bulk-print-check js-capeante-print-check"
                            value="<?= $intern['id_capeante'] ?>"
                            aria-label="Selecionar capeante <?= $intern['id_capeante'] ?>">
                    </td>
                    <td scope="row" class="col-id">
                        <?= $intern["id_internacao"]; ?>
                    </td>
                    <td scope="row" class="col-id">
                        <?= $intern["id_capeante"]; ?>
                    </td>
                    <td scope="row" class="nome-coluna-table"><em><b>
                                <?= $intern["nome_hosp"] ?>
                            </b></em></td>
                    <td scope="row">
                        <?= $intern["nome_pac"] ?>
                    </td>
                    <td scope="row">
                        <?= $intern["senha_int"] ?>
                    </td>
                    <td scope="row">
                        <?= date('d/m/Y', strtotime($intern["data_intern_int"])) ?>
                    </td>

                    <td scope="row">
                        <?php if ($intern["senha_finalizada"] == "s") { ?>
                        <span id="boot-icon" class="bi bi-briefcase"
                            style="font-size: 1.1rem; font-weight:800; color: rgb(255, 25, 55);"></span>
                        <?php }; ?>
                    </td>
                    <td class="fc-list-action">
                        <div class="dropdown">
                            <button class="btn btn-default dropdown-toggle" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" style="color:#5e2363" aria-expanded="false">
                                <i class="bi bi-stack"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                <li>
                                    <button class="dropdown-item"
                                        onclick="edit('<?= $BASE_URL ?>contas/ver/<?= $intern['id_capeante'] ?>')">
                                        <i style="color:green; margin-right:10px" class="bi bi-eye"></i> Visualização
                                        Detalhes
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item"
                                        onclick="window.location.href='<?= $BASE_URL ?>contas/prontuario/<?= $intern['id_capeante'] ?>'">
                                        <i style="color:brown; margin-right:10px" class="bi bi-printer"></i> Imprimir resumido
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item"
                                        onclick="window.location.href='<?= $BASE_URL ?>contas/prontuario/<?= $intern['id_capeante'] ?>?modelo=completo'">
                                        <i style="color:brown; margin-right:10px" class="bi bi-file-earmark-spreadsheet"></i> Imprimir completo
                                    </button>
                                </li>
                        </div>
                    </td>


                </tr>
                <?php endforeach; ?>
                <?php if ($qtdIntItens == 0) : ?>
                <tr>
                    <td colspan="12" scope="row" class="col-id">
                        Não foram encontrados registros
                    </td>
                </tr>

                <?php endif ?>
            </tbody>
        </table>
        <!-- salvar variavel qtdIntItens no PHP para passar para JS -->
        <div style="text-align:right;margin-top:20px">
            <input type="hidden" id="qtd" value="<?php echo $qtdIntItens ?>">
        </div>

        <div class="pagination" style="margin: 0 auto;">
            <?php if ($total_pages ?? 1 > 1) : ?>
            <ul class="pagination">
                <?php
                    $blocoAtual = isset($_GET['bl']) ? $_GET['bl'] : 0;
                    $paginaAtual = isset($_GET['pag']) ? $_GET['pag'] : 1;
                    ?>
                <?php if ($current_block > $first_block) : ?>
                <li class="page-item">
                    <a class="page-link" id="blocoNovo" href="#"
                        onclick="loadContent('list_internacao_cap_new.php?pesquisa_nome=<?php print $pesquisa_nome ?>&pesquisa_pac=<?php print $pesquisa_pac ?>&data_intern_int=<?php print $data_intern_int ?>&senha_int=<?php print $senha_int ?>&lote=<?php print $lote ?>&pesqInternado=<?php print $pesqInternado ?>&limite_pag=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&pag=<?php print 1 ?>&bl=<?php print 0 ?>')">
                        <i class="fas fa-angle-double-left"></i></a>
                </li>
                <?php endif; ?>
                <?php if ($current_block <= $last_block && $last_block > 1 && $current_block != 1) : ?>
                <li class="page-item">
                    <a class="page-link" href="#"
                        onclick="loadContent('list_internacao_cap_new.php?pesquisa_nome=<?php print $pesquisa_nome ?>&pesquisa_pac=<?php print $pesquisa_pac ?>&data_intern_int=<?php print $data_intern_int ?>&senha_int=<?php print $senha_int ?>&lote=<?php print $lote ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&med_check=<?php print $med_check ?>&enf_check=<?php print $enf_check ?>&adm_check=<?php print $adm_check ?>&senha_fin=<?php print $senha_fin ?>&pag=<?php print print $paginaAtual - 1 ?>&bl=<?php print print $blocoAtual - 5 ?>')">
                        <i class="fas fa-angle-left"></i> </a>
                </li>
                <?php endif; ?>

                <?php for ($i = $first_page_in_block; $i <= $last_page_in_block; $i++) : ?>
                <li class="page-item <?php print ($_GET['pag'] ?? 1) == $i ? "active" : "" ?>">

                    <a class="page-link" href="#"
                        onclick="loadContent('list_internacao_cap_new.php?pesquisa_nome=<?php print $pesquisa_nome ?>&pesquisa_pac=<?php print $pesquisa_pac ?>&data_intern_int=<?php print $data_intern_int ?>&senha_int=<?php print $senha_int ?>&lote=<?php print $lote ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&med_check=<?php print $med_check ?>&enf_check=<?php print $enf_check ?>&adm_check=<?php print $adm_check ?>&senha_fin=<?php print $senha_fin ?>&pag=<?php print $i ?>&bl=<?php print $blocoAtual ?>')">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>

                <?php if ($current_block < $last_block) : ?>
                <li class="page-item">
                    <a class="page-link" id="blocoNovo" href="#"
                        onclick="loadContent('list_internacao_cap_new.php?pesquisa_nome=<?php print $pesquisa_nome ?>&pesquisa_pac=<?php print $pesquisa_pac ?>&data_intern_int=<?php print $data_intern_int ?>&senha_int=<?php print $senha_int ?>&lote=<?php print $lote ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&med_check=<?php print $med_check ?>&enf_check=<?php print $enf_check ?>&adm_check=<?php print $adm_check ?>&senha_fin=<?php print $senha_fin ?>&pag=<?php print $paginaAtual + 1 ?>&bl=<?php print $blocoAtual + 5 ?>')"><i
                            class="fas fa-angle-right"></i></a>
                </li>
                <?php endif; ?>
                <?php if ($current_block < $last_block) : ?>
                <li class="page-item">
                    <a class="page-link" id="blocoNovo" href="#"
                        onclick="loadContent('list_internacao_cap_new.php?pesquisa_nome=<?php print $pesquisa_nome ?>&pesquisa_pac=<?php print $pesquisa_pac ?>&data_intern_int=<?php print $data_intern_int ?>&senha_int=<?php print $senha_int ?>&lote=<?php print $lote ?>&limite=<?php print $limite ?>&ordenar=<?php print $ordenar ?>&med_check=<?php print $med_check ?>&enf_check=<?php print $enf_check ?>&adm_check=<?php print $adm_check ?>&senha_fin=<?php print $senha_fin ?>&pag=<?php print print count($paginas) ?>&bl=<?php print print ($last_block - 1) * 5 ?>')"><i
                            class="fas fa-angle-double-right"></i></a>
                </li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
        </div>

    </div>
</div>
</div>
<script>
if (!window.__fcBulkPrintBound) {
    window.__fcBulkPrintBound = true;
    window.__fcBulkPrintBaseUrl = <?= json_encode(rtrim($BASE_URL, '/') . '/show_capeantePrt.php') ?>;

    function fcBulkPrintTableFrom(element) {
        return element ? element.closest('#table-content') : null;
    }

    function fcBulkPrintChecks(tableContent) {
        return Array.from(tableContent ? tableContent.querySelectorAll('.js-capeante-print-check') : []);
    }

    function fcBulkPrintSelectedIds(tableContent) {
        return fcBulkPrintChecks(tableContent).filter((check) => check.checked).map((check) => check.value);
    }

    function fcBulkPrintUpdate(tableContent) {
        const countEl = tableContent ? tableContent.querySelector('[data-bulk-print-count]') : null;
        const selectAll = tableContent ? tableContent.querySelector('.js-capeante-select-all') : null;
        const checks = fcBulkPrintChecks(tableContent);
        const selected = fcBulkPrintSelectedIds(tableContent).length;
        if (countEl) countEl.textContent = selected + (selected === 1 ? ' selecionado' : ' selecionados');
        if (selectAll) {
            selectAll.checked = checks.length > 0 && checks.every((check) => check.checked);
            selectAll.indeterminate = selected > 0 && selected < checks.length;
        }
    }

    document.addEventListener('change', function(event) {
        if (event.target.matches('.js-capeante-select-all')) {
            const tableContent = fcBulkPrintTableFrom(event.target);
            fcBulkPrintChecks(tableContent).forEach((check) => {
                check.checked = event.target.checked;
            });
            fcBulkPrintUpdate(tableContent);
        }
        if (event.target.matches('.js-capeante-print-check')) {
            fcBulkPrintUpdate(fcBulkPrintTableFrom(event.target));
        }
    });

    document.addEventListener('click', function(event) {
        const button = event.target.closest('[data-bulk-print-modelo]');
        if (!button) return;
        const tableContent = fcBulkPrintTableFrom(button);
        const ids = fcBulkPrintSelectedIds(tableContent);
        if (!ids.length) {
            alert('Selecione pelo menos um capeante para imprimir.');
            return;
        }
        const modelo = button.dataset.bulkPrintModelo || 'resumido';
        window.location.href = window.__fcBulkPrintBaseUrl + '?modelo=' + encodeURIComponent(modelo) + '&ids=' + encodeURIComponent(ids.join(','));
    });
}
// ajax para submit do formulario de pesquisa
$(document).ready(function() {
    function getCurrentPageNumber() {
        var activeText = $('.pagination .page-item.active .page-link').first().text();
        var activePage = parseInt(activeText, 10);
        if (activePage > 0) {
            return activePage;
        }
        var urlPage = parseInt(new URLSearchParams(window.location.search).get('pag') || '1', 10);
        return urlPage > 0 ? urlPage : 1;
    }

    function buildSenhasExportUrl(scope) {
        var params = new URLSearchParams($('#select-internacao-form').serialize());
        params.set('export_scope', scope === 'current_page' ? 'current_page' : 'filtered');
        if (scope === 'current_page') {
            params.set('pag', getCurrentPageNumber());
        } else {
            params.delete('pag');
        }
        return '<?= rtrim($BASE_URL, '/') ?>/exportar_excel_senhas_finalizadas.php?' + params.toString();
    }

    $(document).on('click', '.js-senhas-export-link', function() {
        this.href = buildSenhasExportUrl($(this).data('export-scope'));
    });

    $('#select-internacao-form').submit(function(e) {
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
// ajax para navegacao 
function loadContent(url) {
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'html',
        success: function(data) {
            // Crie um elemento temporário para armazenar a resposta HTML
            var tempElement = document.createElement('div');
            tempElement.innerHTML = data;

            // Encontre o elemento com o ID "table-content" dentro do elemento temporário
            var tableContent = tempElement.querySelector('#table-content');
            $('#table-content').html(tableContent);
        },
        error: function() {
        }
    });
}
$(document).ready(function() {
    loadContent('list_internacao_senha_fin.php?&pag=<?php print 1 ?>&bl=<?php print 0 ?>');
});
</script>

<script src="./js/input-estilo.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js">
</script>
<?php
require_once("templates/footer.php");
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js">
