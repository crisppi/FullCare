<?php
include_once("check_logado.php");

require_once("templates/header.php");
require_once("dao/hospitalDao.php");
require_once("models/message.php");

$hospitalDao = new HospitalDAO($conn, $BASE_URL);

// Receber id do usuário
$id_hospital = filter_input(INPUT_GET, "id_hospital");

?>
<?php include_once("array_dados.php");
?>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/form_cad_internacao.css?v=<?= @filemtime(__DIR__ . '/../../css/form_cad_internacao.css') ?>">
<style>
    #main-container.internacao-page {
        margin: 2px 0 0 !important;
        padding-inline: 2px !important;
        padding-top: 0 !important;
        width: auto !important;
        max-width: 100% !important;
        overflow-x: hidden;
    }

    #main-container.internacao-page .internacao-page__hero {
        min-height: 58px !important;
        margin: 0 0 5px !important;
        padding: 14px 14px !important;
        border-radius: 18px !important;
    }

    #main-container.internacao-page .internacao-page__hero h1 {
        font-size: 1.2rem !important;
        line-height: 1.1 !important;
    }

    #main-container.internacao-page .hero-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    #main-container.internacao-page .hero-back-btn {
        border-radius: 999px;
        border: 1px solid #d9c3f4;
        color: #5e2363;
        padding: 6px 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: .78rem;
        background: #f4ecfb;
    }

    #main-container.internacao-page .hero-back-btn:hover {
        color: #4a1b4e;
        background: #eadcf8;
    }

    #main-container.internacao-page .internacao-card__eyebrow {
        font-weight: 700 !important;
    }

    #main-container.internacao-page .internacao-page__content {
        display: block !important;
    }

    #main-container.internacao-page .internacao-page__tag,
    #main-container.internacao-page .internacao-card__tag,
    #main-container.internacao-page .entity-step-badge {
        padding: 4px 8px !important;
        font-size: .6rem !important;
    }

    #main-container.internacao-page .internacao-card {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    #main-container.internacao-page .internacao-card__header {
        padding: 8px 8px 2px !important;
        border-bottom: 0 !important;
    }

    #main-container.internacao-page .internacao-card__title {
        font-size: .9rem !important;
        line-height: 1.1 !important;
    }

    #main-container.internacao-page .internacao-card__body {
        padding: 4px 8px 10px !important;
        gap: 5px !important;
        background: transparent !important;
    }

    #main-container.internacao-page .entity-step-card {
        padding: 7px 8px 8px !important;
        border-radius: 0 !important;
        border: 0 !important;
        border-top: 1px solid rgba(94, 35, 99, 0.12) !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    #main-container.internacao-page .entity-step-card::before {
        display: none !important;
    }

    #main-container.internacao-page .entity-step-card + .entity-step-card {
        margin-top: 3px !important;
    }

    #main-container.internacao-page .entity-step-header {
        align-items: center !important;
        margin-bottom: 7px !important;
    }

    #main-container.internacao-page .entity-step-kicker {
        margin-bottom: 1px !important;
        font-size: .52rem !important;
    }

    #main-container.internacao-page .entity-step-title {
        font-size: .92rem !important;
        line-height: 1.1 !important;
    }

    #main-container.internacao-page .entity-form .form-group,
    #main-container.internacao-page .entity-form [class*="col-md-"].form-group {
        margin-bottom: 8px !important;
    }

    #main-container.internacao-page .entity-form .form-group label {
        margin-bottom: 3px !important;
        font-size: .7rem !important;
        line-height: 1.1 !important;
    }

    #multi-step-form .form-control {
        min-height: 40px !important;
        height: 40px !important;
        border-radius: 9px;
        font-size: .78rem !important;
        padding-top: 5px !important;
        padding-bottom: 5px !important;
    }

    #multi-step-form select.form-control {
        height: 40px !important;
        min-height: 40px !important;
    }

    #acomodacao-inline-card {
        background: #f7f5fb;
        border: 1px solid #e8def1;
        border-radius: 10px;
        padding: 9px;
    }

    #acomodacoesTable th,
    #acomodacoesTable td {
        vertical-align: middle;
    }

    .inline-manager-card {
        background: #f7f5fb;
        border: 1px solid #e8def1;
        border-radius: 10px;
        padding: 9px;
    }

    #main-container.internacao-page.cadastro-hospital-page .internacao-card {
        padding: 5px 8px 7px !important;
        border-radius: 8px !important;
        background: #ffffff !important;
        border: 1px solid rgba(94, 35, 99, .08) !important;
        box-shadow: 0 5px 12px rgba(37, 18, 54, .045) !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .internacao-card__header {
        min-height: 0 !important;
        margin-bottom: 3px !important;
        padding: 4px 8px 2px !important;
        align-items: center !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .internacao-card__body {
        gap: 4px !important;
        padding: 2px 8px 6px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-card {
        padding: 7px 8px 8px 12px !important;
        border-radius: 8px !important;
        border: 1px solid rgba(94, 35, 99, .10) !important;
        background: linear-gradient(180deg, rgba(255, 255, 255, .98) 0%, rgba(248, 244, 253, .94) 100%) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .9),
            0 5px 12px rgba(37, 18, 54, .045) !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-card::before {
        display: block !important;
        width: 3px !important;
        border-radius: 8px 0 0 8px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-card + .entity-step-card {
        margin-top: 5px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-header {
        align-items: center !important;
        margin-bottom: 6px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-card--collapsible .entity-step-header {
        cursor: pointer;
        border-radius: 8px;
        padding: 4px 6px;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-card--collapsible .entity-step-header:hover {
        background: rgba(94, 35, 99, .06);
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-kicker {
        display: none !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-title {
        font-size: .92rem !important;
        line-height: 1.1 !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-toggle {
        border: 1px solid rgba(94, 35, 99, .22);
        background: #fff;
        color: #5e2363;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: .62rem;
        font-weight: 700;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-toggle::after {
        content: "\f078";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        font-size: .58rem;
        transition: transform .15s ease;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-card--collapsible:not(.is-collapsed) .entity-step-toggle::after {
        transform: rotate(180deg);
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-step-panel {
        padding-top: 4px;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-form .row {
        column-gap: 0 !important;
        row-gap: 4px !important;
        margin-left: -5px !important;
        margin-right: -5px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-form .row > [class*="col-"] {
        padding-left: 5px !important;
        padding-right: 5px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-form .form-group,
    #main-container.internacao-page.cadastro-hospital-page .entity-form [class*="col-md-"].form-group,
    #main-container.internacao-page.cadastro-hospital-page .entity-form [class*="col-sm-"].form-group {
        margin-bottom: 1px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-form .form-group label {
        min-height: 0 !important;
        margin-bottom: 2px !important;
        font-size: .62rem !important;
        line-height: 1.05 !important;
        font-weight: 600 !important;
        color: #3b2b4b !important;
    }

    #main-container.internacao-page.cadastro-hospital-page #multi-step-form .form-control,
    #main-container.internacao-page.cadastro-hospital-page #multi-step-form select.form-control {
        min-height: 28px !important;
        height: 28px !important;
        padding: 2px 7px !important;
        border-radius: 7px !important;
        border: 1px solid #b8c4d6 !important;
        background-color: #ffffff !important;
        font-size: .68rem !important;
        line-height: 1.1 !important;
        font-weight: 500 !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .95), 0 1px 3px rgba(15, 23, 42, .16) !important;
    }

    #main-container.internacao-page.cadastro-hospital-page #multi-step-form .form-control:hover,
    #main-container.internacao-page.cadastro-hospital-page #multi-step-form select.form-control:hover {
        border-color: #8796aa !important;
    }

    #main-container.internacao-page.cadastro-hospital-page #multi-step-form .form-control:focus,
    #main-container.internacao-page.cadastro-hospital-page #multi-step-form select.form-control:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 .14rem rgba(59, 130, 246, .16), 0 1px 3px rgba(15, 23, 42, .16) !important;
        outline: none !important;
    }

    #main-container.internacao-page.cadastro-hospital-page #multi-step-form input[type="date"]::-webkit-datetime-edit,
    #main-container.internacao-page.cadastro-hospital-page #multi-step-form input[type="date"]::-webkit-datetime-edit-text,
    #main-container.internacao-page.cadastro-hospital-page #multi-step-form input[type="date"]::-webkit-datetime-edit-day-field,
    #main-container.internacao-page.cadastro-hospital-page #multi-step-form input[type="date"]::-webkit-datetime-edit-month-field,
    #main-container.internacao-page.cadastro-hospital-page #multi-step-form input[type="date"]::-webkit-datetime-edit-year-field {
        font-size: .68rem !important;
        line-height: 18px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page #multi-step-form input[type="date"]::-webkit-calendar-picker-indicator {
        width: 13px !important;
        height: 13px !important;
        margin: 0 !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .inline-manager-card,
    #main-container.internacao-page.cadastro-hospital-page #acomodacao-inline-card {
        padding: 9px !important;
        border-radius: 10px !important;
        background: #f7f5fb !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .85) !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .table-responsive table {
        margin-bottom: 0 !important;
        border-radius: 0 !important;
        overflow: hidden;
        font-size: 10px !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .table-responsive thead {
        height: 24px !important;
        background: #2f6f9f !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .table-responsive thead th {
        height: 24px !important;
        min-height: 24px !important;
        padding: 2px 6px !important;
        background: transparent !important;
        border-bottom: 1px solid #d9e8f1 !important;
        color: #fff !important;
        font-family: var(--app-font-family, "Inter", Arial, Helvetica, sans-serif) !important;
        font-size: .66rem !important;
        font-weight: 600 !important;
        line-height: 1.02 !important;
        letter-spacing: .025em !important;
        text-align: center !important;
        text-transform: uppercase !important;
        vertical-align: middle !important;
        border-radius: 0 !important;
        white-space: nowrap;
    }

    #main-container.internacao-page.cadastro-hospital-page .table-responsive tbody td {
        height: 26px !important;
        min-height: 26px !important;
        padding: 2px 6px !important;
        border-top: 1px solid #f1ebf7 !important;
        vertical-align: middle !important;
        font-size: 10px !important;
        line-height: 1.05 !important;
        color: #56616f !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .table-responsive tbody td.text-muted {
        color: #8b95a5 !important;
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    #main-container.internacao-page.cadastro-hospital-page .th-px-80 {
        width: 80px;
    }

    #main-container.internacao-page.cadastro-hospital-page .th-px-90 {
        width: 90px;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-actions-bar {
        margin-top: 5px !important;
        padding: 7px 8px !important;
        border-radius: 8px !important;
        border: 1px solid rgba(94, 35, 99, .08) !important;
        background: #ffffff !important;
        box-shadow: 0 5px 12px rgba(37, 18, 54, .045) !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .entity-actions-bar .btn {
        min-height: 28px !important;
        height: 28px !important;
        padding: 2px 12px !important;
        border-radius: 7px !important;
        font-size: .68rem !important;
        line-height: 1 !important;
    }

    #main-container.internacao-page.cadastro-hospital-page .inline-add-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 28px !important;
        min-width: 28px !important;
        max-width: 28px !important;
        height: 28px !important;
        min-height: 28px !important;
        padding: 0 !important;
        border-radius: 7px !important;
        font-size: .82rem !important;
        font-weight: 800 !important;
        line-height: 1 !important;
        box-shadow: 0 5px 12px rgba(21, 69, 105, .18) !important;
    }
</style>

<div class="internacao-page cadastro-layout cadastro-hospital-page" id="main-container">
    <div class="internacao-page__hero">
        <div>
            <h1>Cadastrar hospital</h1>
        </div>
        <div class="hero-actions">
            <a class="hero-back-btn js-friendly-back"
                data-default-return="<?= htmlspecialchars(rtrim($BASE_URL, '/') . '/hospitais', ENT_QUOTES, 'UTF-8') ?>"
                href="<?= htmlspecialchars(rtrim($BASE_URL, '/') . '/hospitais', ENT_QUOTES, 'UTF-8') ?>">
                Voltar para lista
            </a>
        </div>
    </div>
    <div class="internacao-page__content">
        <form action="<?= $BASE_URL ?>process_hospital.php" id="multi-step-form" method="POST" enctype="multipart/form-data"
            class="needs-validation visible entity-form" novalidate>
            <div class="internacao-card internacao-card--general">
                <div class="internacao-card__header">
                    <div>
                        <p class="internacao-card__eyebrow">Etapa 1</p>
                        <h2 class="internacao-card__title">Dados do hospital</h2>
                    </div>
                    <span class="internacao-card__tag internacao-card__tag--critical">Cadastro institucional</span>
                </div>
                <div class="internacao-card__body">
        <input type="hidden" name="type" value="create">
        <input type="hidden" name="deletado_hosp" value="n">

        <!-- Step 1: Informações Básicas -->
        <div id="step-1" class="step entity-step-card">
            <div class="entity-step-header">
                <div class="entity-step-copy">
                    <h3 class="entity-step-title">Identificação do hospital</h3>
                </div>
                <span class="entity-step-badge">Dados base</span>
            </div>
            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="cnpj_hosp">CNPJ</label>
                    <input type="text" oninput="mascara(this, 'cnpj')" class="form-control" id="cnpj_hosp"
                        name="cnpj_hosp" placeholder="Ex: 00.000.000/0000-00">
                    <div class="invalid-feedback">Por favor, insira um CNPJ válido.</div>
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="nome_hosp"><span style="color:red;">*</span> Nome do Hospital</label>
                    <input type="text" class="form-control" id="nome_hosp" name="nome_hosp" required
                        placeholder="Digite o nome do hospital">
                    <div class="invalid-feedback">Por favor, insira o nome do hospital.</div>
                </div>
            </div>
            <hr>
        </div>

        <!-- Step 2: Endereço e Localização -->
        <div id="step-2" class="step entity-step-card entity-step-card--collapsible is-collapsed">
            <div class="entity-step-header" role="button" tabindex="0" aria-expanded="false" aria-controls="step-2-panel">
                <div class="entity-step-copy">
                    <h3 class="entity-step-title">Endereços</h3>
                </div>
                <span class="entity-step-toggle">Abrir</span>
            </div>
            <div class="entity-step-panel" id="step-2-panel" hidden>
            <div class="row">
                <div class="form-group col-md-4 mb-3">
                    <label for="cep_hosp">CEP</label>
                    <input type="text" onkeyup="consultarCEP(this, 'hosp')" class="form-control" id="cep_hosp"
                        name="cep_hosp" placeholder="00000-000">
                    <div class="invalid-feedback">Por favor, insira o CEP.</div>
                </div>
                <div class="form-group col-md-8 mb-3">
                    <label for="endereco_hosp">Endereço</label>
                    <input readonly type="text" class="form-control" id="endereco_hosp" name="endereco_hosp"
                        placeholder="Rua, Av, etc.">
                    <div class="invalid-feedback">Por favor, insira o endereço.</div>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="bairro_hosp">Bairro</label>
                    <input readonly type="text" class="form-control" id="bairro_hosp" name="bairro_hosp"
                        placeholder="Bairro">
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="cidade_hosp">Cidade</label>
                    <input readonly type="text" class="form-control" id="cidade_hosp" name="cidade_hosp"
                        placeholder="Cidade">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="estado_hosp">Estado</label>
                    <input readonly class="form-control" id="estado_hosp" name="estado_hosp">
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="numero_hosp">Número</label>
                    <input type="text" class="form-control" id="numero_hosp" name="numero_hosp"
                        placeholder="Número do endereço">
                </div>
            </div>

            <p class="internacao-card__eyebrow mb-3">Endereços adicionais</p>
            <div class="inline-manager-card mb-3">
                <div class="row">
                    <div class="form-group col-md-2 mb-2">
                        <label for="end_tipo_inline">Tipo</label>
                        <input type="text" class="form-control" id="end_tipo_inline" placeholder="Filial / Cobrança">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="end_cep_inline">CEP</label>
                        <input type="text" class="form-control" id="end_cep_inline" placeholder="00000-000">
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <label for="end_logradouro_inline">Endereço</label>
                        <input type="text" class="form-control" id="end_logradouro_inline" placeholder="Rua, Av, etc.">
                    </div>
                    <div class="form-group col-md-1 mb-2">
                        <label for="end_numero_inline">Nº</label>
                        <input type="text" class="form-control" id="end_numero_inline" placeholder="123">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="end_bairro_inline">Bairro</label>
                        <input type="text" class="form-control" id="end_bairro_inline" placeholder="Bairro">
                    </div>
                    <div class="form-group col-md-1 mb-2 d-flex align-items-end">
                        <button type="button" id="btnAddEnderecoInline" class="btn btn-primary inline-add-btn" aria-label="Adicionar endereço">+</button>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3 mb-2">
                        <label for="end_cidade_inline">Cidade</label>
                        <input type="text" class="form-control" id="end_cidade_inline" placeholder="Cidade">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="end_estado_inline">UF</label>
                        <input type="text" class="form-control" id="end_estado_inline" placeholder="UF">
                    </div>
                    <div class="form-group col-md-5 mb-2">
                        <label for="end_complemento_inline">Complemento</label>
                        <input type="text" class="form-control" id="end_complemento_inline" placeholder="Complemento">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="end_principal_inline">Principal</label>
                        <select class="form-control" id="end_principal_inline">
                            <option value="n">Não</option>
                            <option value="s">Sim</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Endereço</th>
                                <th>Cidade/UF</th>
                                <th>Principal</th>
                                <th class="th-px-90">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="enderecosTableBody">
                            <tr id="enderecosTableEmpty">
                                <td colspan="5" class="text-muted text-center">Nenhum endereço adicional.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="enderecosHiddenContainer"></div>
            </div>

            <hr>
            </div>
        </div>

        <!-- Step 3: Contato -->
        <div id="step-3" class="step entity-step-card entity-step-card--collapsible is-collapsed">
            <div class="entity-step-header" role="button" tabindex="0" aria-expanded="false" aria-controls="step-3-panel">
                <div class="entity-step-copy">
                    <h3 class="entity-step-title">Contato operacional</h3>
                </div>
                <span class="entity-step-toggle">Abrir</span>
            </div>
            <div class="entity-step-panel" id="step-3-panel" hidden>
            <div class="row">
                <div class="form-group col-md-3 mb-3">
                    <label for="email01_hosp">Email Principal</label>
                    <input type="email" class="form-control" id="email01_hosp" name="email01_hosp"
                        placeholder="exemplo@dominio.com">
                    <div class="invalid-feedback">Por favor, insira um email válido.</div>
                </div>
                <div class="form-group col-md-3 mb-3">
                    <label for="email02_hosp">Email Alternativo</label>
                    <input type="email" class="form-control" id="email02_hosp" name="email02_hosp"
                        placeholder="exemplo@dominio.com">
                </div>
                <div class="form-group col-md-2 mb-3">
                    <label for="telefone01_hosp">Telefone Principal</label>
                    <input type="text" onkeydown="return mascaraTelefone(event)" class="form-control"
                        id="telefone01_hosp" name="telefone01_hosp" placeholder="(00) 0000-0000">
                </div>
                <div class="form-group col-md-2 mb-3">
                    <label for="telefone02_hosp">Telefone Alternativo</label>
                    <input type="text" onkeydown="return mascaraTelefone(event)" class="form-control"
                        id="telefone02_hosp" name="telefone02_hosp" placeholder="(00) 0000-0000">
                </div>
                <div class="form-group col-md-2 mb-3">
                    <label for="ativo_hosp">Ativo</label>
                    <select class="form-control" name="ativo_hosp">
                        <option value="s">Sim</option>
                        <option value="n">Não</option>
                    </select>
                </div>
            </div>

            <p class="internacao-card__eyebrow mb-3">Telefones adicionais</p>
            <div class="inline-manager-card mb-3">
                <div class="row">
                    <div class="form-group col-md-2 mb-2">
                        <label for="tel_tipo_inline">Tipo</label>
                        <input type="text" class="form-control" id="tel_tipo_inline" placeholder="Plantão / Financeiro">
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label for="tel_numero_inline">Telefone</label>
                        <input type="text" class="form-control" id="tel_numero_inline" placeholder="(00) 00000-0000">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="tel_ramal_inline">Ramal</label>
                        <input type="text" class="form-control" id="tel_ramal_inline" placeholder="Ramal">
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label for="tel_contato_inline">Contato</label>
                        <input type="text" class="form-control" id="tel_contato_inline" placeholder="Nome do contato">
                    </div>
                    <div class="form-group col-md-1 mb-2">
                        <label for="tel_principal_inline">Principal</label>
                        <select class="form-control" id="tel_principal_inline">
                            <option value="n">Não</option>
                            <option value="s">Sim</option>
                        </select>
                    </div>
                    <div class="form-group col-md-1 mb-2 d-flex align-items-end">
                        <button type="button" id="btnAddTelefoneInline" class="btn btn-primary inline-add-btn" aria-label="Adicionar telefone">+</button>
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Número</th>
                                <th>Ramal</th>
                                <th>Contato</th>
                                <th>Principal</th>
                                <th class="th-px-90">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="telefonesTableBody">
                            <tr id="telefonesTableEmpty">
                                <td colspan="6" class="text-muted text-center">Nenhum telefone adicional.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="telefonesHiddenContainer"></div>
            </div>

            <p class="internacao-card__eyebrow mb-3">Contatos do hospital</p>
            <div class="inline-manager-card mb-3">
                <div class="row">
                    <div class="form-group col-md-2 mb-2">
                        <label for="cont_nome_inline">Nome</label>
                        <input type="text" class="form-control" id="cont_nome_inline" placeholder="Nome do contato">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="cont_cargo_inline">Cargo</label>
                        <input type="text" class="form-control" id="cont_cargo_inline" placeholder="Cargo">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="cont_setor_inline">Setor</label>
                        <input type="text" class="form-control" id="cont_setor_inline" placeholder="Setor">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="cont_email_inline">Email</label>
                        <input type="email" class="form-control" id="cont_email_inline" placeholder="email@dominio.com">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <label for="cont_telefone_inline">Telefone</label>
                        <input type="text" class="form-control" id="cont_telefone_inline" placeholder="(00) 00000-0000">
                    </div>
                    <div class="form-group col-md-1 mb-2">
                        <label for="cont_principal_inline">Principal</label>
                        <select class="form-control" id="cont_principal_inline">
                            <option value="n">Não</option>
                            <option value="s">Sim</option>
                        </select>
                    </div>
                    <div class="form-group col-md-1 mb-2 d-flex align-items-end">
                        <button type="button" id="btnAddContatoInline" class="btn btn-primary inline-add-btn" aria-label="Adicionar contato">+</button>
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Cargo/Setor</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Principal</th>
                                <th class="th-px-90">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="contatosTableBody">
                            <tr id="contatosTableEmpty">
                                <td colspan="6" class="text-muted text-center">Nenhum contato adicional.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="contatosHiddenContainer"></div>
            </div>
            <hr>
            </div>
        </div>

        <!-- Step 4: Coordenadas e Responsáveis -->
        <div id="step-4" class="step entity-step-card entity-step-card--collapsible is-collapsed">
            <div class="entity-step-header" role="button" tabindex="0" aria-expanded="false" aria-controls="step-4-panel">
                <div class="entity-step-copy">
                    <h3 class="entity-step-title">Dados complementares</h3>
                </div>
                <span class="entity-step-toggle">Abrir</span>
            </div>
            <div class="entity-step-panel" id="step-4-panel" hidden>
            <div class="row">
                <div class="form-group col-md-4 mb-3">
                    <label for="coordenador_medico_hosp">Coordenador Médico</label>
                    <input type="text" class="form-control" id="coordenador_medico_hosp" name="coordenador_medico_hosp"
                        placeholder="Nome do coordenador médico">
                </div>
                <div class="form-group col-md-4 mb-3">
                    <label for="diretor_hosp">Diretor</label>
                    <input type="text" class="form-control" id="diretor_hosp" name="diretor_hosp"
                        placeholder="Nome do diretor">
                </div>
                <div class="form-group col-md-4 mb-3">
                    <label for="coordenador_fat_hosp">Coordenador de Faturamento</label>
                    <input type="text" class="form-control" id="coordenador_fat_hosp" name="coordenador_fat_hosp"
                        placeholder="Nome do coordenador de faturamento">
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6 mb-3">
                    <label for="numero_hosp">Latitude</label>
                    <input type="text" class="form-control" id="latitude_hosp" name="latitude_hosp"
                        placeholder="Ex: -23.5505">
                </div>
                <div class="form-group col-md-6 mb-3">
                    <label for="longitude_hosp">Longitude</label>
                    <input type="text" class="form-control" id="longitude_hosp" name="longitude_hosp"
                        placeholder="Ex: -46.6333">
                </div>
            </div>

            <p class="internacao-card__eyebrow mb-3">Acomodações do hospital</p>
            <div id="acomodacao-inline-card" class="mb-3">
                <div class="row">
                    <div class="form-group col-md-4 mb-2">
                        <label for="acomodacao_nome_inline">Acomodação</label>
                        <select class="form-control" id="acomodacao_nome_inline">
                            <option value="">Selecione</option>
                            <?php
                            sort($dados_acomodacao, SORT_ASC);
                            foreach ($dados_acomodacao as $acomd): ?>
                            <option value="<?= htmlspecialchars($acomd, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($acomd, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <label for="acomodacao_valor_inline">Valor diária</label>
                        <input type="text" class="form-control" id="acomodacao_valor_inline" placeholder="R$ 0,00">
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label for="acomodacao_data_inline">Data contrato</label>
                        <input type="date" class="form-control" id="acomodacao_data_inline">
                    </div>
                    <div class="form-group col-md-1 mb-2 d-flex align-items-end">
                        <button type="button" id="btnAddAcomodacaoInline" class="btn btn-primary inline-add-btn" aria-label="Adicionar acomodação">+</button>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-striped mb-0" id="acomodacoesTable">
                        <thead>
                            <tr>
                                <th>Acomodação</th>
                                <th>Valor diária</th>
                                <th>Data contrato</th>
                                <th class="th-px-80">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="acomodacoesTableBody">
                            <tr id="acomodacoesTableEmpty">
                                <td colspan="4" class="text-muted text-center">Nenhuma acomodação adicionada.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="acomodacoesHiddenContainer"></div>
            </div>
            </div>

            <div class="entity-actions-bar">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Cadastrar
                </button>
            </div>
        </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var backLink = document.querySelector('.js-friendly-back');
        if (!backLink) return;
        var fallbackUrl = backLink.getAttribute('data-default-return') || backLink.href;
        backLink.href = fallbackUrl;
        backLink.textContent = 'Voltar para lista';
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.entity-step-card--collapsible').forEach(function(card) {
            var header = card.querySelector('.entity-step-header');
            var panelId = header ? header.getAttribute('aria-controls') : '';
            var panel = panelId ? document.getElementById(panelId) : null;
            var toggle = card.querySelector('.entity-step-toggle');
            if (!header || !panel) return;

            function setExpanded(expanded) {
                card.classList.toggle('is-collapsed', !expanded);
                panel.hidden = !expanded;
                header.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                if (toggle) toggle.textContent = expanded ? 'Recolher' : 'Abrir';
            }

            header.addEventListener('click', function() {
                setExpanded(panel.hidden);
            });

            header.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setExpanded(panel.hidden);
                }
            });

            setExpanded(false);
        });
    });

    (function () {
        const nomeEl = document.getElementById('acomodacao_nome_inline');
        const valorEl = document.getElementById('acomodacao_valor_inline');
        const dataEl = document.getElementById('acomodacao_data_inline');
        const addBtn = document.getElementById('btnAddAcomodacaoInline');
        const tbody = document.getElementById('acomodacoesTableBody');
        const hiddenContainer = document.getElementById('acomodacoesHiddenContainer');
        const emptyRow = document.getElementById('acomodacoesTableEmpty');

        if (!nomeEl || !valorEl || !dataEl || !addBtn || !tbody || !hiddenContainer || !emptyRow) {
            return;
        }

        let index = 0;

        function createHidden(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value || '';
            return input;
        }

        function onlyDigits(value) {
            return String(value || '').replace(/\D+/g, '');
        }

        function formatCurrencyBR(value) {
            const digits = onlyDigits(value);
            if (!digits) return '';
            const cents = Number(digits) / 100;
            return 'R$ ' + cents.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatDateBR(value) {
            const raw = String(value || '').trim();
            if (!raw) return '';
            const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return raw;
            return `${m[3]}/${m[2]}/${m[1]}`;
        }

        function addRow(nome, valor, data) {
            if (emptyRow) emptyRow.style.display = 'none';
            const dataView = formatDateBR(data);

            const row = document.createElement('tr');
            row.dataset.index = String(index);
            row.innerHTML = `
                <td>${nome}</td>
                <td>${valor || '-'}</td>
                <td>${dataView || '-'}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger">Remover</button></td>
            `;

            const wrap = document.createElement('div');
            wrap.dataset.index = String(index);
            wrap.appendChild(createHidden('acomodacao_nome[]', nome));
            wrap.appendChild(createHidden('acomodacao_valor[]', valor));
            wrap.appendChild(createHidden('acomodacao_data[]', data));
            hiddenContainer.appendChild(wrap);

            row.querySelector('button').addEventListener('click', function () {
                row.remove();
                wrap.remove();
                if (!tbody.querySelector('tr')) {
                    emptyRow.style.display = '';
                    tbody.appendChild(emptyRow);
                }
            });

            tbody.appendChild(row);
            index += 1;
        }

        valorEl.addEventListener('input', function () {
            const formatted = formatCurrencyBR(valorEl.value);
            valorEl.value = formatted;
        });

        addBtn.addEventListener('click', function () {
            const nome = (nomeEl.value || '').trim();
            const valor = formatCurrencyBR(valorEl.value);
            const data = (dataEl.value || '').trim();

            if (!nome) {
                alert('Selecione a acomodação.');
                nomeEl.focus();
                return;
            }

            addRow(nome, valor, data);
            nomeEl.value = '';
            valorEl.value = '';
            dataEl.value = '';
            nomeEl.focus();
        });
    })();

    (function () {
        function onlyDigits(value) {
            return String(value || '').replace(/\D+/g, '');
        }

        function formatPhoneBR(value) {
            const digits = onlyDigits(value);
            if (!digits) return '';
            if (digits.length > 10) {
                return digits.replace(/^(\d{2})(\d{5})(\d{0,4}).*$/, '($1) $2-$3').trim();
            }
            return digits.replace(/^(\d{2})(\d{4})(\d{0,4}).*$/, '($1) $2-$3').trim();
        }

        function createHidden(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value || '';
            return input;
        }

        function bindManager(config) {
            const addBtn = document.getElementById(config.addButtonId);
            const tbody = document.getElementById(config.tableBodyId);
            const emptyRow = document.getElementById(config.emptyRowId);
            const hiddenContainer = document.getElementById(config.hiddenContainerId);
            if (!addBtn || !tbody || !emptyRow || !hiddenContainer) return;

            let idx = 0;
            function addItem(item) {
                if (emptyRow.parentNode) emptyRow.remove();
                const row = document.createElement('tr');
                row.dataset.idx = String(idx);
                row.innerHTML = config.rowTemplate(item);
                const wrap = document.createElement('div');
                wrap.dataset.idx = String(idx);
                config.hiddenFields(item).forEach(({ name, value }) => wrap.appendChild(createHidden(name, value)));
                hiddenContainer.appendChild(wrap);
                const removeBtn = row.querySelector('.btn-remove-inline');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        row.remove();
                        wrap.remove();
                        if (!tbody.querySelector('tr')) {
                            tbody.appendChild(emptyRow);
                        }
                    });
                }
                tbody.appendChild(row);
                idx += 1;
            }

            addBtn.addEventListener('click', function () {
                const item = config.readItem();
                if (!item) return;
                addItem(item);
                config.clearInputs();
            });
        }

        bindManager({
            addButtonId: 'btnAddEnderecoInline',
            tableBodyId: 'enderecosTableBody',
            emptyRowId: 'enderecosTableEmpty',
            hiddenContainerId: 'enderecosHiddenContainer',
            readItem: function () {
                const item = {
                    tipo: (document.getElementById('end_tipo_inline').value || '').trim(),
                    cep: (document.getElementById('end_cep_inline').value || '').trim(),
                    logradouro: (document.getElementById('end_logradouro_inline').value || '').trim(),
                    numero: (document.getElementById('end_numero_inline').value || '').trim(),
                    bairro: (document.getElementById('end_bairro_inline').value || '').trim(),
                    cidade: (document.getElementById('end_cidade_inline').value || '').trim(),
                    estado: (document.getElementById('end_estado_inline').value || '').trim(),
                    complemento: (document.getElementById('end_complemento_inline').value || '').trim(),
                    principal: document.getElementById('end_principal_inline').value || 'n'
                };
                if (!item.logradouro) return null;
                return item;
            },
            rowTemplate: function (item) {
                return `<td>${item.tipo || '-'}</td>
                        <td>${item.logradouro}${item.numero ? ', ' + item.numero : ''}</td>
                        <td>${item.cidade || '-'}${item.estado ? '/' + item.estado : ''}</td>
                        <td>${item.principal === 's' ? 'Sim' : 'Não'}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-inline">Remover</button></td>`;
            },
            hiddenFields: function (item) {
                return [
                    { name: 'end_tipo[]', value: item.tipo },
                    { name: 'end_cep[]', value: item.cep },
                    { name: 'end_logradouro[]', value: item.logradouro },
                    { name: 'end_numero[]', value: item.numero },
                    { name: 'end_bairro[]', value: item.bairro },
                    { name: 'end_cidade[]', value: item.cidade },
                    { name: 'end_estado[]', value: item.estado },
                    { name: 'end_complemento[]', value: item.complemento },
                    { name: 'end_principal[]', value: item.principal },
                ];
            },
            clearInputs: function () {
                ['end_tipo_inline', 'end_cep_inline', 'end_logradouro_inline', 'end_numero_inline', 'end_bairro_inline', 'end_cidade_inline', 'end_estado_inline', 'end_complemento_inline'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                document.getElementById('end_principal_inline').value = 'n';
            }
        });

        bindManager({
            addButtonId: 'btnAddTelefoneInline',
            tableBodyId: 'telefonesTableBody',
            emptyRowId: 'telefonesTableEmpty',
            hiddenContainerId: 'telefonesHiddenContainer',
            readItem: function () {
                const item = {
                    tipo: (document.getElementById('tel_tipo_inline').value || '').trim(),
                    numero: formatPhoneBR(document.getElementById('tel_numero_inline').value || ''),
                    ramal: (document.getElementById('tel_ramal_inline').value || '').trim(),
                    contato: (document.getElementById('tel_contato_inline').value || '').trim(),
                    principal: document.getElementById('tel_principal_inline').value || 'n'
                };
                if (!item.numero) return null;
                return item;
            },
            rowTemplate: function (item) {
                return `<td>${item.tipo || '-'}</td>
                        <td>${item.numero}</td>
                        <td>${item.ramal || '-'}</td>
                        <td>${item.contato || '-'}</td>
                        <td>${item.principal === 's' ? 'Sim' : 'Não'}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-inline">Remover</button></td>`;
            },
            hiddenFields: function (item) {
                return [
                    { name: 'tel_tipo[]', value: item.tipo },
                    { name: 'tel_numero[]', value: item.numero },
                    { name: 'tel_ramal[]', value: item.ramal },
                    { name: 'tel_contato[]', value: item.contato },
                    { name: 'tel_principal[]', value: item.principal },
                ];
            },
            clearInputs: function () {
                ['tel_tipo_inline', 'tel_numero_inline', 'tel_ramal_inline', 'tel_contato_inline'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                document.getElementById('tel_principal_inline').value = 'n';
            }
        });

        bindManager({
            addButtonId: 'btnAddContatoInline',
            tableBodyId: 'contatosTableBody',
            emptyRowId: 'contatosTableEmpty',
            hiddenContainerId: 'contatosHiddenContainer',
            readItem: function () {
                const item = {
                    nome: (document.getElementById('cont_nome_inline').value || '').trim(),
                    cargo: (document.getElementById('cont_cargo_inline').value || '').trim(),
                    setor: (document.getElementById('cont_setor_inline').value || '').trim(),
                    email: (document.getElementById('cont_email_inline').value || '').trim(),
                    telefone: formatPhoneBR(document.getElementById('cont_telefone_inline').value || ''),
                    principal: document.getElementById('cont_principal_inline').value || 'n'
                };
                if (!item.nome) return null;
                return item;
            },
            rowTemplate: function (item) {
                return `<td>${item.nome}</td>
                        <td>${item.cargo || '-'}${item.setor ? ' / ' + item.setor : ''}</td>
                        <td>${item.email || '-'}</td>
                        <td>${item.telefone || '-'}</td>
                        <td>${item.principal === 's' ? 'Sim' : 'Não'}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-inline">Remover</button></td>`;
            },
            hiddenFields: function (item) {
                return [
                    { name: 'cont_nome[]', value: item.nome },
                    { name: 'cont_cargo[]', value: item.cargo },
                    { name: 'cont_setor[]', value: item.setor },
                    { name: 'cont_email[]', value: item.email },
                    { name: 'cont_telefone[]', value: item.telefone },
                    { name: 'cont_principal[]', value: item.principal },
                ];
            },
            clearInputs: function () {
                ['cont_nome_inline', 'cont_cargo_inline', 'cont_setor_inline', 'cont_email_inline', 'cont_telefone_inline'].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                document.getElementById('cont_principal_inline').value = 'n';
            }
        });
    })();

    // validacao de tamanho do arquivo de imagem
    const imagem = document.querySelector("#logo_hosp")
    // console.log(imagem);

    if (imagem) {
        imagem.addEventListener("change", function (e) {
            if (!imagem.files || !imagem.files[0]) return;
            if (imagem.files[0].size > (1024 * 1024 * 2)) {

                // Apresentar a mensagem de erro
                // alert("Tamanho máximo permitido do arquivo é 2mb.");
                var notifImagem = document.querySelector("#notifImagem");
                if (notifImagem) notifImagem.style.display = "block";

                // Limpar o campo arquivo
                imagem.value = '';
                //(imagem ? imagem.value = '' : null)
            }
        })
    }

    function novoArquivo() {
        var notifImagem = document.querySelector("#notifImagem");
        if (notifImagem) notifImagem.style.display = "none";

    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js">
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<?php
require_once("templates/footer.php");
?>
