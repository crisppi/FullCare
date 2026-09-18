<?php
ob_start();

require_once("templates/header.php");
require_once("models/message.php");

include_once("models/internacao.php");
include_once("dao/internacaoDao.php");
include_once("models/paciente.php");
include_once("dao/pacienteDao.php");
include_once("models/hospital.php");
include_once("dao/hospitalDao.php");
include_once("models/pagination.php");
include_once("array_dados.php");

$internacaoDao = new internacaoDAO($conn, $BASE_URL);
$paginationObj = new pagination(0, 1, 10);

$pesquisa_hosp = trim((string)(filter_input(INPUT_GET, 'pesquisa_hosp', FILTER_SANITIZE_SPECIAL_CHARS) ?: ''));
$pesquisa_pac  = trim((string)(filter_input(INPUT_GET, 'pesquisa_pac', FILTER_SANITIZE_SPECIAL_CHARS) ?: ''));
$pesquisa_matricula = trim((string)(filter_input(INPUT_GET, 'pesquisa_matricula', FILTER_SANITIZE_SPECIAL_CHARS) ?: ''));
$limite        = filter_input(INPUT_GET, 'limite', FILTER_VALIDATE_INT) ?: 10;
$ordenar       = filter_input(INPUT_GET, 'ordenar', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'data_intern_int DESC';
$pagAtual      = filter_input(INPUT_GET, 'pag', FILTER_VALIDATE_INT) ?: 1;

$whereParams = [':internado_int' => 's'];
$condicoes = ['ac.internado_int = :internado_int'];
if ($pesquisa_hosp !== '') {
    $condicoes[] = 'ho.nome_hosp LIKE :pesquisa_hosp';
    $whereParams[':pesquisa_hosp'] = '%' . $pesquisa_hosp . '%';
}
if ($pesquisa_pac !== '') {
    $condicoes[] = 'pa.nome_pac LIKE :pesquisa_pac';
    $whereParams[':pesquisa_pac'] = '%' . $pesquisa_pac . '%';
}
if ($pesquisa_matricula !== '') {
    $condicoes[] = 'pa.matricula_pac LIKE :pesquisa_matricula';
    $whereParams[':pesquisa_matricula'] = '%' . $pesquisa_matricula . '%';
}
if (ge_enabled()) $condicoes[] = ge_internacao_sql('ac');
        $where = implode(' AND ', $condicoes);

$dadosTotais = $internacaoDao->selectAllInternacaoList($where, $ordenar, null, $whereParams);
$qtdItens = is_array($dadosTotais) ? count($dadosTotais) : 0;
$paginationObj = new pagination($qtdItens, $pagAtual, $limite);
$lista = $internacaoDao->selectAllInternacaoList($where, $ordenar, $paginationObj->getLimit(), $whereParams);
$totalPages = $qtdItens > 0 ? (int)ceil($qtdItens / $limite) : 1;

$dadosAlta = $dados_alta ?? [];
sort($dadosAlta);
?>
<link rel="stylesheet" href="<?= htmlspecialchars(rtrim($BASE_URL, '/') . '/css/listagem_padrao.css?v=' . @filemtime(__DIR__ . '/../css/listagem_padrao.css'), ENT_QUOTES, 'UTF-8') ?>">

<div class="container-fluid form_container listagem-page gerar-altas-page" id="main-container">
    <div class="listagem-hero listagem-hero--module listagem-hero--internacoes">
        <div class="listagem-hero__copy">
            <div class="listagem-kicker">Internações abertas</div>
            <h1 class="listagem-title">Gerar altas</h1>
        </div>
    </div>

    <div class="gerar-alta-filter-card">
        <div class="card-body">
            <form class="gerar-alta-filter-form">
                <div class="gerar-alta-filter-grid">
                    <div class="gerar-alta-filter-field">
                        <label class="form-label small text-muted">Hospital</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-hospital"></i></span>
                            <input type="text" class="form-control form-control-sm" name="pesquisa_hosp"
                                value="<?= htmlspecialchars($pesquisa_hosp) ?>" placeholder="Nome do hospital">
                        </div>
                    </div>
                    <div class="gerar-alta-filter-field">
                        <label class="form-label small text-muted">Paciente</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control form-control-sm" name="pesquisa_pac"
                                value="<?= htmlspecialchars($pesquisa_pac) ?>" placeholder="Nome do paciente">
                        </div>
                    </div>
                    <div class="gerar-alta-filter-field">
                        <label class="form-label small text-muted">Matrícula</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-123"></i></span>
                            <input type="text" class="form-control form-control-sm" name="pesquisa_matricula"
                                value="<?= htmlspecialchars($pesquisa_matricula) ?>" placeholder="Matrícula do paciente">
                        </div>
                    </div>
                    <div class="gerar-alta-filter-field">
                        <label class="form-label small text-muted">Registros</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-list-ol"></i></span>
                            <select name="limite" class="form-select form-select-sm">
                                <?php foreach ([10, 20, 50] as $opt): ?>
                                <option value="<?= $opt ?>" <?= $limite == $opt ? 'selected' : '' ?>>
                                    <?= $opt ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="gerar-alta-filter-actions">
                        <button class="btn btn-sm btn-primary btn-filtro-buscar btn-filtro-limpar-icon"
                            type="submit" title="Filtrar" aria-label="Filtrar">
                            <span class="material-icons" aria-hidden="true">search</span>
                        </button>
                        <a href="list_internacao_gerar_alta.php"
                            class="btn btn-sm btn-light btn-filtro-limpar btn-filtro-limpar-icon"
                            title="Limpar filtros" aria-label="Limpar filtros">
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="<?= htmlspecialchars($BASE_URL, ENT_QUOTES, 'UTF-8') ?>css/gerar_altas.css?v=<?= filemtime(__DIR__.'/../css/gerar_altas.css') ?>">

    <form action="process_gerar_altas.php" method="POST" id="form-gerar-altas">
<?php if (ge_enabled()): ?><input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8')?>"><?php endif; ?>
        <input type="hidden" name="type" value="gerar_altas">
        <?php if ($lista): ?>
        <div class="gerar-alta-actionbar">
            <span>
                Marque as internações, preencha data/hora/motivo e clique em <strong>Gerar altas</strong>.
            </span>
            <button type="submit" class="btn btn-lg text-white gerar-alta-submit">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                Gerar altas selecionadas
            </button>
        </div>
        <?php endif; ?>

        <div class="gerar-alta-list">
            <div class="gerar-alta-list-body">
                <?php if (!$lista): ?>
                <div class="text-center text-muted py-4">Nenhum paciente internado.</div>
                <?php else: ?>
                <?php foreach ($lista as $row):
                $idIntern = (int)($row['id_internacao'] ?? 0);
                $fieldPrefix = 'alta_' . $idIntern;
                $dataInternacaoFormatada = !empty($row['data_intern_int'])
                    ? date('d/m/Y', strtotime($row['data_intern_int']))
                    : '—';
                $internadoUti = strtolower((string)($row['internado_uti'] ?? 'n')) === 's';
                $idUti = (int)($row['id_uti'] ?? 0);
                $fkInternacaoUti = (int)($row['fk_internacao_uti'] ?? $idIntern);
            ?>
                <div class="gerar-alta-card">
                    <input type="hidden" name="<?= $fieldPrefix ?>_uti_flag" value="<?= $internadoUti ? 's' : 'n' ?>">
                    <?php if ($internadoUti && $idUti): ?>
                    <input type="hidden" name="<?= $fieldPrefix ?>_uti_id" value="<?= $idUti ?>">
                    <input type="hidden" name="<?= $fieldPrefix ?>_uti_fk" value="<?= $fkInternacaoUti ?>">
                    <?php endif; ?>
                <div class="gerar-alta-meta-grid">
                    <div class="gerar-alta-meta-card gerar-alta-patient">
                        <span class="gerar-alta-meta-label"><i class="bi bi-person" aria-hidden="true"></i>Paciente</span>
                        <strong><?= htmlspecialchars($row['nome_pac'] ?? '-') ?></strong>
                    </div>
                    <div class="gerar-alta-meta-card">
                        <span class="gerar-alta-meta-label"><i class="bi bi-hospital" aria-hidden="true"></i>Hospital</span>
                        <strong><?= htmlspecialchars($row['nome_hosp'] ?? '-') ?></strong>
                        <small><?= htmlspecialchars($row['acomodacao_int'] ?? '') ?></small>
                    </div>
                    <div class="gerar-alta-meta-card">
                        <span class="gerar-alta-meta-label"><i class="bi bi-calendar2-plus" aria-hidden="true"></i>Internação</span>
                        <strong><?= $dataInternacaoFormatada ?></strong>
                    </div>
                    <div class="gerar-alta-meta-card gerar-alta-meta-card-id">
                        <span class="gerar-alta-meta-label"><i class="bi bi-hash" aria-hidden="true"></i>ID</span>
                        <strong><?= $idIntern ?></strong>
                    </div>
                </div>

                <hr>

                <div class="gerar-alta-fields">
                    <?php if ($internadoUti && $idUti): ?>
                    <div class="col-12">
                        <div class="shadow-field bg-light">
                            <span class="d-block text-danger fw-semibold">Paciente na UTI</span>
                            <small class="text-muted">Informe a data da alta da UTI antes de gerar.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="tag mb-1" for="<?= $fieldPrefix ?>_uti_data">Data alta UTI</label>
                        <div class="shadow-field">
                            <input type="date" class="form-control form-control-sm border-0 bg-transparent p-0"
                                id="<?= $fieldPrefix ?>_uti_data" name="<?= $fieldPrefix ?>_uti_data">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label class="tag mb-1" for="<?= $fieldPrefix ?>_data_hora">Data/Hora da alta</label>
                        <div class="shadow-field">
                            <input type="datetime-local" class="form-control form-control-sm border-0 bg-transparent p-0"
                                id="<?= $fieldPrefix ?>_data_hora" name="<?= $fieldPrefix ?>_data_hora" step="60">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="tag mb-1" for="<?= $fieldPrefix ?>_motivo">Motivo da alta</label>
                        <div class="shadow-field">
                            <select class="form-select form-select-sm border-0 bg-transparent p-0"
                                id="<?= $fieldPrefix ?>_motivo" name="<?= $fieldPrefix ?>_motivo">
                                <option value="">Selecione...</option>
                                <?php foreach ($dadosAlta as $motivo): ?>
                                <option value="<?= htmlspecialchars($motivo) ?>"><?= htmlspecialchars($motivo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="gerar-alta-selection">
                        <div class="tag mb-1">Seleção</div>
                        <label class="shadow-field gerar-alta-check-field">
                            <input type="checkbox" class="form-check-input" name="gerar[]" value="<?= $idIntern ?>" aria-label="Selecionar alta da internação <?= $idIntern ?>">
                            <span>Selecionar alta</span>
                        </label>
                    </div>
                </div>
            </div>
                <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>

    </form>

    <div class="listagem-footer-row gerar-alta-footer">
        <div class="listagem-pagination-slot">
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Paginação">
            <ul class="pagination justify-content-center mb-0">
                <?php
                $blockStart = intdiv(max(1, $pagAtual) - 1, 5) * 5 + 1;
                $blockEnd = min($blockStart + 4, $totalPages);
                $pageUrl = static function (int $page) use ($BASE_URL, $limite, $pesquisa_hosp, $pesquisa_pac, $pesquisa_matricula, $ordenar): string {
                    return htmlspecialchars($BASE_URL . 'list_internacao_gerar_alta.php?' . http_build_query([
                        'pag'=>$page, 'limite'=>$limite, 'pesquisa_hosp'=>$pesquisa_hosp,
                        'pesquisa_pac'=>$pesquisa_pac, 'pesquisa_matricula'=>$pesquisa_matricula, 'ordenar'=>$ordenar
                    ]), ENT_QUOTES, 'UTF-8');
                };
                ?>
                <?php if ($blockStart > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= $pageUrl(max(1, $blockStart - 5)) ?>" aria-label="Cinco páginas anteriores" title="Cinco páginas anteriores">&laquo;</a></li>
                <?php endif; ?>
                <?php for ($i = $blockStart; $i <= $blockEnd; $i++): ?>
                <li class="page-item <?= $i == $pagAtual ? 'active' : '' ?>">
                    <a class="page-link" <?= $i == $pagAtual ? 'aria-current="page"' : '' ?> href="<?= $pageUrl($i) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($blockEnd < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="<?= $pageUrl($blockEnd + 1) ?>" aria-label="Próximas cinco páginas" title="Próximas cinco páginas">&raquo;</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        </div>
        <span class="text-muted gerar-alta-total">Total: <?= $qtdItens ?></span>
    </div>
</div>
