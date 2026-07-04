<?php
include_once("check_logado.php");

require_once("templates/header.php");
require_once("dao/hospitalDao.php");
require_once("dao/hospitalUserDao.php");
require_once("dao/usuarioDao.php");

$hospitalDao = new hospitalDAO($conn, $BASE_URL);
$hospitalUserDao = new hospitalUserDAO($conn, $BASE_URL);
$usuarioDao = new UserDAO($conn, $BASE_URL);

$id_hospital = filter_input(INPUT_GET, "id_hospital", FILTER_VALIDATE_INT);
$hospital = $id_hospital ? $hospitalDao->findById($id_hospital) : null;

if (!$hospital) {
    header("Location: " . rtrim($BASE_URL, '/') . "/hospitais", true, 303);
    exit;
}

$vinculos = $hospitalUserDao->listarPorHospital((int) $id_hospital);
$usuariosAtivos = $usuarioDao->selectAllUsuario('ativo_user IN ("s","S","1","true","TRUE","ATIVO","ativo")', 'usuario_user ASC', null);

$jaVinculados = [];
foreach ($vinculos as $v) {
    $uid = (int) ($v['fk_usuario_hosp'] ?? 0);
    if ($uid > 0) {
        $jaVinculados[$uid] = true;
    }
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/form_cad_internacao.css?v=<?= @filemtime(__DIR__ . '/css/form_cad_internacao.css') ?>">
<link rel="stylesheet" href="<?= h(rtrim($BASE_URL, '/') . '/css/listagem_padrao.css?v=' . @filemtime(__DIR__ . '/css/listagem_padrao.css')) ?>">
<style>
    .hospital-users-page {
        padding: 0 4px 18px;
        margin-top: 8px !important;
    }

    .hospital-users-hero {
        --module-start: #2f6f9f;
        --module-mid: #3f93bd;
        --module-end: #5eb4d8;
        --module-shadow: rgba(47, 111, 159, .18);
    }

    .hospital-users-badge {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 5px 10px;
        border: 1px solid rgba(255, 255, 255, .32);
        border-radius: 999px;
        background: rgba(255, 255, 255, .14);
        color: #fff;
        font-size: .72rem;
        font-weight: 800;
        line-height: 1;
    }

    .hospital-users-panel {
        margin-bottom: 10px;
        border-radius: 8px;
        border: 1px solid #dbe4ef;
        background: #fff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
        overflow: visible;
    }

    .hospital-users-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 31px;
        padding: 7px 10px;
        border-bottom: 1px solid #e8eef6;
        background: #fbfdff;
    }

    .hospital-users-panel__header h2 {
        margin: 0;
        color: #2f2240;
        font-size: .82rem;
        font-weight: 800;
        line-height: 1.15;
    }

    .hospital-users-panel__kicker {
        margin: 0 0 2px;
        color: #7b5a9a;
        font-size: .58rem;
        font-weight: 800;
        letter-spacing: .08em;
        line-height: 1;
        text-transform: uppercase;
    }

    .hospital-users-panel__body {
        padding: 10px;
        overflow: visible;
    }

    .hospital-users-form {
        display: grid;
        grid-template-columns: minmax(240px, 480px) auto;
        gap: 8px;
        align-items: end;
        max-width: 720px;
        overflow: visible;
    }

    .hospital-users-form .form-group {
        margin: 0;
        overflow: visible;
    }

    .hospital-users-form label {
        margin-bottom: 3px;
        color: #5b6472;
        font-size: .68rem;
        font-weight: 800;
        line-height: 1;
    }

    .hospital-users-form .form-control {
        min-height: 32px;
        height: 32px;
        border-radius: 8px;
        border-color: #cbd8e6;
        background-color: #f8fafc;
        color: #1f2937;
        font-size: .78rem;
        font-weight: 600;
    }

    .hospital-users-form .bootstrap-select {
        width: 100% !important;
    }

    .hospital-users-form .bootstrap-select > .dropdown-toggle {
        min-height: 32px;
        height: 32px;
        padding: 5px 30px 5px 12px;
        border: 1px solid #cbd8e6 !important;
        border-radius: 8px;
        background: #f8fafc !important;
        color: #1f2937 !important;
        font-size: .78rem;
        font-weight: 600;
        line-height: 1.15;
        box-shadow: none !important;
    }

    .hospital-users-form .bootstrap-select > .dropdown-toggle .filter-option,
    .hospital-users-form .bootstrap-select > .dropdown-toggle .filter-option-inner,
    .hospital-users-form .bootstrap-select > .dropdown-toggle .filter-option-inner-inner {
        height: 20px;
        color: inherit;
        font-size: .78rem;
        font-weight: 600;
        line-height: 20px;
    }

    .hospital-users-form .bootstrap-select > .dropdown-toggle.bs-placeholder,
    .hospital-users-form .bootstrap-select > .dropdown-toggle.bs-placeholder .filter-option,
    .hospital-users-form .bootstrap-select > .dropdown-toggle.bs-placeholder .filter-option-inner-inner {
        color: #aeb7c4 !important;
        font-weight: 600;
    }

    .hospital-users-form .bootstrap-select > .dropdown-toggle::after {
        margin-top: 0;
        color: #1f2937;
    }

    .hospital-users-form .bootstrap-select.show > .dropdown-toggle,
    .hospital-users-form .bootstrap-select > .dropdown-toggle:focus,
    .hospital-users-form .bootstrap-select > .dropdown-toggle:hover {
        border-color: #8bb8f7 !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    .hospital-users-form .bootstrap-select.show > .dropdown-toggle {
        border-color: #cbd8e6 !important;
        border-radius: 8px 8px 0 0;
    }

    .hospital-users-form .bootstrap-select .dropdown-menu {
        width: 100%;
        min-width: 100%;
        max-width: none;
        margin-top: -1px;
        padding: 0;
        border: 1px solid #cbd8e6;
        border-top: 0;
        border-radius: 0 0 8px 8px;
        background: #fff;
        font-size: .78rem;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
        overflow: hidden;
        z-index: 2050;
    }

    .hospital-users-form .bootstrap-select .bs-searchbox {
        padding: 6px 8px;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
    }

    .hospital-users-form .bootstrap-select .bs-searchbox input {
        height: 30px;
        min-height: 30px;
        padding: 4px 9px;
        border: 1px solid #cbd8e6;
        border-radius: 7px;
        color: #1f2937;
        font-size: .78rem;
        line-height: 1.15;
        box-shadow: none !important;
        outline: none;
    }

    .hospital-users-form .bootstrap-select .bs-searchbox input:focus {
        border-color: #9fb7d3;
        box-shadow: none !important;
        outline: none;
    }

    .hospital-users-form .bootstrap-select .bs-searchbox input::placeholder {
        color: #aeb7c4;
    }

    .hospital-users-form .bootstrap-select .inner {
        max-height: 210px !important;
    }

    .hospital-users-form .bootstrap-select .dropdown-menu li a,
    .hospital-users-form .bootstrap-select .dropdown-item {
        min-height: 28px;
        padding: 5px 12px;
        color: #1f2937;
        font-size: .78rem;
        line-height: 1.15;
    }

    .hospital-users-form .bootstrap-select .dropdown-menu li a span.text {
        color: inherit;
        font-size: inherit;
        line-height: inherit;
    }

    .hospital-users-form .bootstrap-select .dropdown-menu li.selected a,
    .hospital-users-form .bootstrap-select .dropdown-menu li a:active,
    .hospital-users-form .bootstrap-select .dropdown-item.active,
    .hospital-users-form .bootstrap-select .dropdown-item:active {
        background: #e8f1ff;
        color: #174ea6;
    }

    .hospital-users-form .btn {
        min-height: 32px;
        border-radius: 8px;
        padding: 5px 12px;
        font-size: .78rem;
        font-weight: 800;
        line-height: 1;
    }

    .hospital-users-table-wrap {
        overflow-x: auto;
        background: #f8f9fa;
    }

    .hospital-users-table {
        margin: 0;
        min-width: 820px;
        font-size: 10px !important;
    }

    .hospital-users-table thead {
        height: 24px !important;
        background: #2f6f9f;
        color: #fff;
    }

    .hospital-users-table thead th {
        height: 24px !important;
        min-height: 24px !important;
        padding: 2px 6px !important;
        border: 0;
        color: inherit;
        font-size: .66rem !important;
        font-weight: 600 !important;
        line-height: 1.02 !important;
        letter-spacing: .025em !important;
        text-align: center;
        text-transform: uppercase !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    .hospital-users-table tbody td {
        height: 26px !important;
        min-height: 26px !important;
        padding: 2px 6px !important;
        color: #334155;
        font-size: 10px !important;
        line-height: 1.05 !important;
        vertical-align: middle !important;
    }

    .hospital-users-table .btn {
        min-height: 24px;
        padding: 3px 8px;
        border-radius: 7px;
        font-size: 10px;
        line-height: 1;
    }

    @media (max-width: 768px) {
        .hospital-users-form {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid form_container listagem-page hospital-users-page" id="main-container">
    <div class="listagem-hero listagem-hero--module listagem-hero--cadastros hospital-users-hero">
        <div class="listagem-hero__copy">
            <div class="listagem-kicker">Hospitais</div>
            <h1 class="listagem-title">Usuários do hospital</h1>
        </div>
        <div class="listagem-hero__actions">
            <a class="btn listagem-btn-top" href="<?= h(rtrim($BASE_URL, '/') . '/hospitais') ?>">Voltar para hospitais</a>
            <a class="btn listagem-btn-top" href="<?= h(rtrim($BASE_URL, '/') . '/hospital_acomodacoes.php?id_hospital=' . (int) $id_hospital) ?>">Acomodações</a>
            <span class="hospital-users-badge"><?= h($hospital->nome_hosp) ?></span>
        </div>
    </div>

    <div class="hospital-users-panel">
        <div class="hospital-users-panel__header">
            <div>
                <p class="hospital-users-panel__kicker">Novo vínculo</p>
                <h2>Vincular usuário</h2>
            </div>
        </div>
        <div class="hospital-users-panel__body">
            <form class="hospital-users-form" action="<?= h(rtrim($BASE_URL, '/') . '/process_hospitalUser.php') ?>" method="POST">
                <input type="hidden" name="type" value="create">
                <input type="hidden" name="fk_hospital_user" value="<?= (int) $id_hospital ?>">
                <input type="hidden" name="redirect_hospital_id" value="<?= (int) $id_hospital ?>">

                <div class="form-group">
                    <label for="fk_usuario_hosp">Usuário</label>
                    <select class="form-control selectpicker show-tick hospital-users-select"
                        id="fk_usuario_hosp"
                        name="fk_usuario_hosp"
                        required
                        data-live-search="true"
                        data-size="8"
                        data-width="100%"
                        data-style="hospital-users-picker-btn"
                        data-dropdown-align-right="false"
                        data-live-search-placeholder="Digite para pesquisar..."
                        title="Selecione o usuário">
                        <option value="">Selecione o usuário</option>
                        <?php foreach ($usuariosAtivos as $u): ?>
                            <?php
                            $uid = (int) ($u['id_usuario'] ?? 0);
                            $nome = (string) ($u['usuario_user'] ?? '');
                            $cargo = (string) ($u['cargo_user'] ?? '');
                            if ($uid <= 0 || $nome === '') {
                                continue;
                            }
                            ?>
                            <option value="<?= $uid ?>" <?= isset($jaVinculados[$uid]) ? 'disabled' : '' ?>>
                                <?= h($nome) ?><?= $cargo ? ' - ' . h($cargo) : '' ?><?= isset($jaVinculados[$uid]) ? ' (já vinculado)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fa-solid fa-plus edit-icon"></i> Vincular usuário
                </button>
            </form>
        </div>
    </div>

    <div class="hospital-users-panel">
        <div class="hospital-users-panel__header">
            <div>
                <p class="hospital-users-panel__kicker">Usuários vinculados</p>
                <h2>Vínculos ativos</h2>
            </div>
        </div>
        <div class="hospital-users-panel__body">
            <div class="hospital-users-table-wrap">
                <table class="table table-sm table-striped table-hover table-condensed hospital-users-table">
                    <thead>
                        <tr>
                            <th>ID vínculo</th>
                            <th>ID usuário</th>
                            <th>Usuário</th>
                            <th>E-mail</th>
                            <th>Cargo</th>
                            <th>Nível</th>
                            <th class="th-px-120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vinculos)): ?>
                            <?php foreach ($vinculos as $v): ?>
                                <tr>
                                    <td><?= (int) ($v['id_hospitalUser'] ?? 0) ?></td>
                                    <td><?= (int) ($v['id_usuario'] ?? $v['fk_usuario_hosp'] ?? 0) ?></td>
                                    <td><?= h($v['usuario_user'] ?? '-') ?></td>
                                    <td><?= h($v['email_user'] ?? '-') ?></td>
                                    <td><?= h($v['cargo_user'] ?? '-') ?></td>
                                    <td><?= h($v['nivel_user'] ?? '-') ?></td>
                                    <td>
                                        <form method="POST" action="<?= h(rtrim($BASE_URL, '/') . '/del_hosp_user.php') ?>" class="d-inline"
                                            onsubmit="return confirm('Confirma excluir este vínculo?');">
                                            <input type="hidden" name="type" value="delete">
                                            <input type="hidden" name="id_hospitalUser" value="<?= (int) ($v['id_hospitalUser'] ?? 0) ?>">
                                            <input type="hidden" name="redirect_hospital_id" value="<?= (int) $id_hospital ?>">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Nenhum usuário vinculado para este hospital.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        function initHospitalUserSelectPicker() {
            if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.selectpicker !== 'function') {
                return;
            }

            var $select = jQuery('#fk_usuario_hosp');
            if (!$select.length) {
                return;
            }

            if (!$select.data('selectpicker')) {
                $select.selectpicker();
            }
            $select.selectpicker('refresh');
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHospitalUserSelectPicker);
            return;
        }

        initHospitalUserSelectPicker();
    })();
</script>

<?php require_once("templates/footer.php"); ?>
