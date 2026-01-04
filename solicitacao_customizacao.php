<?php
include_once("check_logado.php");
require_once("dao/solicitacaoCustomizacaoDao.php");

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$dao = new SolicitacaoCustomizacaoDAO($conn, $BASE_URL);
$id = (int)(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);

$norm = function ($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $c = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = $c !== false ? $c : $s;
    return preg_replace('/[^a-z]/', '', $s);
};
$cargo = (string)($_SESSION['cargo'] ?? '');
$nivel = (string)($_SESSION['nivel'] ?? '');
$isDiretoria = in_array($norm($cargo), ['diretoria', 'diretor', 'administrador', 'admin', 'board'], true)
    || in_array($norm($nivel), ['diretoria', 'diretor', 'administrador', 'admin', 'board'], true)
    || ((int)$nivel === -1);
$sessionEmail = (string)($_SESSION['email_user'] ?? '');
$emailNorm = strtolower(trim($sessionEmail));
$isFullcare = str_ends_with($emailNorm, '@fullcare.com.br');
$isCliente = $emailNorm !== '' && !$isFullcare;

$record = null;
$solicitacao = new SolicitacaoCustomizacao();
$modulos = [];
$tipos = [];
$anexos = [];
$moduloOutro = '';

if ($id > 0) {
    $record = $dao->findById($id);
    if ($record) {
        $solicitacao = $record['solicitacao'];
        $modulos = $record['modulos'] ?? [];
        $tipos = $record['tipos'] ?? [];
        $anexos = $record['anexos'] ?? [];
        foreach ($modulos as $mod) {
            if (($mod['modulo'] ?? '') === 'Outro') {
                $moduloOutro = $mod['modulo_outro'] ?? '';
            }
        }
    }
}

if ($id === 0 && $sessionEmail && $isCliente) {
    if (empty($solicitacao->nome)) {
        $solicitacao->nome = (string)($_SESSION['usuario_user'] ?? '');
    }
    if (empty($solicitacao->email)) {
        $solicitacao->email = $sessionEmail;
    }
    if (empty($solicitacao->cargo)) {
        $solicitacao->cargo = (string)($_SESSION['cargo'] ?? '');
    }
    if (empty($solicitacao->empresa)) {
        $sessionEmpresa = (string)($_SESSION['empresa'] ?? $_SESSION['empresa_user'] ?? '');
        if ($sessionEmpresa !== '') {
            $solicitacao->empresa = $sessionEmpresa;
        }
    }
}

$selectedModulos = array_map(fn($m) => $m['modulo'] ?? '', $modulos);
$selectedTipos = array_map(fn($t) => $t['tipo'] ?? '', $tipos);

$moduleOptions = [
    'Internacao' => 'Internação',
    'Paciente' => 'Paciente',
    'Hospital' => 'Hospital',
    'Auditoria' => 'Auditoria',
    'Financeiro' => 'Financeiro',
    'Relatorios' => 'Relatórios',
    'Outro' => 'Outro',
];

$tipoOptions = [
    'Novo recurso' => 'Novo recurso',
    'Alteracao de recurso existente' => 'Alteração de recurso existente',
    'Correcao de erro' => 'Correção de erro',
    'Integracao com outro sistema' => 'Integração com outro sistema',
    'Layout/Visual' => 'Layout/Visual',
    'Relatorio/Exportacao' => 'Relatório/Exportação',
];

$impactoOptions = ['Baixo' => 'Baixo', 'Medio' => 'Médio', 'Alto' => 'Alto'];
$prioridadeOptions = ['Urgente' => 'Urgente', 'Alta' => 'Alta', 'Media' => 'Média', 'Baixa' => 'Baixa'];
$statusOptions = ['Aberto' => 'Aberto', 'Em analise' => 'Em análise', 'Resolvido' => 'Resolvido', 'Cancelado' => 'Cancelado'];
$anexoTipos = ['Prints', 'Arquivos', 'Exemplos externos', 'Documentos'];
$pageModeTitle = $id > 0 ? "Editar Solicitação #{$id}" : "Nova Solicitação";
$pageModeSubtitle = $id > 0
    ? 'Atualize a solicitação e finalize quando estiver resolvida.'
    : 'Registre sua solicitação com detalhes claros.';
?>

<style>
    :root {
        --sc-card: #f1f2f4;
        --sc-border: #d3d8df;
        --sc-accent: #4b4f57;
        --sc-bg: #f4f5f7;
        --sc-muted: #6b7280;
        --sc-fullcare: #5e2363;
        --sc-fullcare-soft: #fff6ec;
    }

    body {
        background: var(--sc-bg);
    }

    .sc-wrap {
        max-width: 100%;
        padding: 12px 14px 48px;
    }

    .sc-hero {
        background: #ffffff;
        padding: 6px 0 12px;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 10px;
    }

    .sc-title {
        margin: 0;
        font-weight: 700;
        color: #111827;
    }

    .sc-subtitle {
        margin: 6px 0 0;
        color: var(--sc-muted);
        font-size: 0.92rem;
    }

    .sc-card {
        border-radius: 6px;
        border: 1px solid var(--sc-border);
        background: var(--sc-card);
        padding: 0;
        margin-bottom: 14px;
    }

    .sc-section-title {
        background: var(--sc-accent);
        color: #ffffff;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 8px 12px;
        border-radius: 6px 6px 0 0;
        margin: 0;
    }

    .sc-section-title--fullcare {
        background: var(--sc-fullcare);
        color: #ffffff;
    }

    .sc-content {
        padding: 14px 16px 16px;
    }

    .sc-check-grid {
        row-gap: 12px;
    }

    .sc-check-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        margin: 0;
    }

    .sc-check-item .form-check-input {
        margin: 0;
    }

    .sc-grid {
        display: grid;
        gap: 16px;
    }

    .sc-grid.cols-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .sc-grid.cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sc-grid.cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .sc-form-actions {
        display: flex;
        justify-content: flex-end;
        padding-top: 8px;
    }

    .sc-card--fullcare .sc-content {
        background: var(--sc-fullcare-soft);
        border-radius: 0 0 6px 6px;
    }

    .sc-anexos li {
        margin-bottom: 6px;
    }

    @media (max-width: 1200px) {
        .sc-grid.cols-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 992px) {
        .sc-grid.cols-3,
        .sc-grid.cols-2 {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid sc-wrap">
    <div class="sc-hero">
        <div>
            <h4 class="sc-title"><?= e($pageModeTitle) ?></h4>
            <p class="sc-subtitle"><?= e($pageModeSubtitle) ?></p>
        </div>
        <?php if ($isDiretoria): ?>
            <a class="btn btn-outline-secondary" href="list_solicitacao_customizacao.php">Voltar à lista</a>
        <?php endif; ?>
    </div>

    <form method="post" action="process_solicitacao_customizacao.php" enctype="multipart/form-data">
        <input type="hidden" name="type" value="<?= $id > 0 ? 'update' : 'create' ?>">
        <input type="hidden" name="id_solicitacao" value="<?= (int)$id ?>">

        <div class="sc-card">
            <div class="sc-section-title">1. Identificação do Solicitante</div>
            <div class="sc-content">
            <div class="sc-grid cols-3">
                <div>
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" name="nome" value="<?= e($solicitacao->nome ?? '') ?>" required <?= $isCliente ? '' : 'readonly' ?>>
                </div>
                <div>
                    <label class="form-label">Empresa</label>
                    <input type="text" class="form-control" name="empresa" value="<?= e($solicitacao->empresa ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                </div>
                <div>
                    <label class="form-label">Cargo</label>
                    <input type="text" class="form-control" name="cargo" value="<?= e($solicitacao->cargo ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                </div>
                <div>
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" name="email" value="<?= e($solicitacao->email ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                </div>
                <div>
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" name="telefone" value="<?= e($solicitacao->telefone ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                </div>
                <div>
                    <label class="form-label">Data da solicitação</label>
                    <input type="date" class="form-control" name="data_solicitacao" value="<?= e($solicitacao->data_solicitacao ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                </div>
            </div>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">2. Módulo a ser customizado</div>
            <div class="sc-content">
            <div class="sc-grid cols-4 sc-check-grid">
                <?php foreach ($moduleOptions as $value => $label): ?>
                    <?php $checked = in_array($value, $selectedModulos, true); ?>
                    <?php $fieldId = 'mod_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($value)); ?>
                    <div class="form-check sc-check-item">
                        <input class="form-check-input" type="checkbox" id="<?= e($fieldId) ?>" name="modulos[]" value="<?= e($value) ?>" <?= $checked ? 'checked' : '' ?> <?= $isCliente ? '' : 'disabled' ?>>
                        <label class="form-check-label" for="<?= e($fieldId) ?>"><?= e($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3">
                <label class="form-label">Se marcou Outro, descreva</label>
                <input type="text" class="form-control" name="modulo_outro" value="<?= e($moduloOutro) ?>" <?= $isCliente ? '' : 'readonly' ?>>
            </div>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">3. Tipo de Solicitação</div>
            <div class="sc-content">
            <div class="sc-grid cols-2 sc-check-grid">
                <?php foreach ($tipoOptions as $value => $label): ?>
                    <?php $checked = in_array($value, $selectedTipos, true); ?>
                    <?php $fieldId = 'tipo_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($value)); ?>
                    <div class="form-check sc-check-item">
                        <input class="form-check-input" type="checkbox" id="<?= e($fieldId) ?>" name="tipos[]" value="<?= e($value) ?>" <?= $checked ? 'checked' : '' ?> <?= $isCliente ? '' : 'disabled' ?>>
                        <label class="form-check-label" for="<?= e($fieldId) ?>"><?= e($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">4. Descrição objetiva da necessidade</div>
            <div class="sc-content">
                <textarea class="form-control" name="descricao" rows="3" <?= $isCliente ? '' : 'readonly' ?>><?= e($solicitacao->descricao ?? '') ?></textarea>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">5. Como funciona hoje (problema atual)</div>
            <div class="sc-content">
                <textarea class="form-control" name="problema_atual" rows="3" <?= $isCliente ? '' : 'readonly' ?>><?= e($solicitacao->problema_atual ?? '') ?></textarea>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">6. Como deve funcionar (resultado esperado)</div>
            <div class="sc-content">
                <textarea class="form-control" name="resultado_esperado" rows="3" <?= $isCliente ? '' : 'readonly' ?>><?= e($solicitacao->resultado_esperado ?? '') ?></textarea>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">7. Impacto se não for feito</div>
            <div class="sc-content">
            <div class="sc-grid cols-2">
                <div>
                    <select class="form-select" name="impacto_nivel" <?= $isCliente ? '' : 'disabled' ?>>
                        <option value="">Selecione</option>
                        <?php foreach ($impactoOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($solicitacao->impacto_nivel ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <input type="text" class="form-control" name="descricao_impacto" placeholder="Descrição do impacto" value="<?= e($solicitacao->descricao_impacto ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                </div>
            </div>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">8. Prioridade</div>
            <div class="sc-content">
                <div class="sc-grid cols-4">
                    <select class="form-select" name="prioridade" <?= $isCliente ? '' : 'disabled' ?>>
                        <option value="">Selecione</option>
                        <?php foreach ($prioridadeOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($solicitacao->prioridade ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">9. Prazo desejado</div>
            <div class="sc-content">
                <div class="sc-grid cols-3">
                    <input type="date" class="form-control" name="prazo_desejado" value="<?= e($solicitacao->prazo_desejado ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                </div>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">10. Anexos</div>
            <div class="sc-content">
                <div class="sc-grid cols-2">
                    <div>
                        <label class="form-label">Tipo do anexo</label>
                        <select class="form-select" name="anexo_tipo" <?= $isCliente ? '' : 'disabled' ?>>
                            <option value="">Selecione</option>
                            <?php foreach ($anexoTipos as $tipo): ?>
                                <option value="<?= e($tipo) ?>"><?= e($tipo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Arquivos (jpg/png/pdf/doc/docx)</label>
                        <input type="file" class="form-control" name="anexos[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" <?= $isCliente ? '' : 'disabled' ?>>
                    </div>
                </div>
                <?php if ($anexos): ?>
                    <div class="mt-3 sc-anexos">
                        <strong>Anexos enviados</strong>
                        <ul class="mt-2">
                            <?php foreach ($anexos as $anexo): ?>
                                <li>
                                    <a href="<?= $BASE_URL . e($anexo['arquivo'] ?? '') ?>" target="_blank">
                                        <?= e($anexo['nome_original'] ?? 'Arquivo') ?>
                                    </a>
                                    <?php if ($isDiretoria && $isCliente): ?>
                                        <form method="post" action="process_solicitacao_customizacao_anexo.php" class="d-inline ms-2">
                                            <input type="hidden" name="id_solicitacao" value="<?= (int)$id ?>">
                                            <input type="hidden" name="id_anexo" value="<?= (int)($anexo['id_anexo'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="sc-card">
            <div class="sc-section-title">11. Aprovação</div>
            <div class="sc-content">
                <div class="sc-grid cols-3">
                    <div>
                        <label class="form-label">Responsável</label>
                        <input type="text" class="form-control" name="responsavel" value="<?= e($solicitacao->responsavel ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                    </div>
                    <div>
                        <label class="form-label">Assinatura</label>
                        <input type="text" class="form-control" name="assinatura" value="<?= e($solicitacao->assinatura ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                    </div>
                    <div>
                        <label class="form-label">Data</label>
                        <input type="date" class="form-control" name="data_aprovacao" value="<?= e($solicitacao->data_aprovacao ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                    </div>
                    <div>
                        <label class="form-label">Aprovação Conex (empresa)</label>
                        <input type="text" class="form-control" name="aprovacao_conex" value="<?= e($solicitacao->aprovacao_conex ?? '') ?>" <?= $isCliente ? '' : 'readonly' ?>>
                    </div>
                </div>
            </div>
        </div>

        <div class="sc-card sc-card--fullcare">
            <div class="sc-section-title sc-section-title--fullcare">12. Resposta FullCare/ConexAud</div>
            <?php
            $readonly = $isFullcare ? '' : 'readonly';
            $disabledSelect = $isFullcare ? '' : 'disabled';
            ?>
            <div class="sc-content">
            <div class="sc-grid cols-3">
                <div>
                    <label class="form-label">Prazo estimado</label>
                    <input type="date" class="form-control" name="prazo_resposta" value="<?= e($solicitacao->prazo_resposta ?? '') ?>" <?= $readonly ?>>
                </div>
                <div>
                    <label class="form-label">Precificação/Estimativa de custo</label>
                    <input type="text" class="form-control" name="precificacao" value="<?= e($solicitacao->precificacao ?? '') ?>" <?= $readonly ?>>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" <?= $disabledSelect ?>>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($solicitacao->status ?? 'Aberto') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Aprovação FullCare</label>
                    <input type="text" class="form-control" name="aprovacao_resposta" value="<?= e($solicitacao->aprovacao_resposta ?? '') ?>" <?= $readonly ?>>
                </div>
                <div>
                    <label class="form-label">Data</label>
                    <input type="date" class="form-control" name="data_resposta" value="<?= e($solicitacao->data_resposta ?? '') ?>" <?= $readonly ?>>
                </div>
                <div>
                    <label class="form-label">Resolvido em</label>
                    <input type="datetime-local" class="form-control" name="resolvido_em" value="<?= e(str_replace(' ', 'T', $solicitacao->resolvido_em ?? '')) ?>" <?= $readonly ?>>
                </div>
                <div>
                    <label class="form-label">Versão do sistema</label>
                    <input type="text" class="form-control" name="versao_sistema" value="<?= e($solicitacao->versao_sistema ?? '') ?>" <?= $readonly ?>>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label">Observações / Ajustes propostos</label>
                <textarea class="form-control" name="observacoes_resposta" rows="3" <?= $readonly ?>><?= e($solicitacao->observacoes_resposta ?? '') ?></textarea>
            </div>
            </div>
        </div>

        <div class="sc-form-actions">
            <button type="submit" class="btn btn-primary btn-lg px-4">Salvar</button>
        </div>
    </form>
</div>

<?php require_once("templates/footer.php"); ?>
