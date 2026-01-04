<?php
include_once("check_logado.php");
require_once("dao/solicitacaoCustomizacaoDao.php");

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function showVal($v): string
{
    $val = trim((string)$v);
    return $val === '' ? '-' : e($val);
}

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

if (!$isDiretoria) {
    echo "<div class='alert alert-danger'>Acesso restrito à diretoria.</div>";
    exit;
}

$id = (int)(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
if ($id <= 0) {
    echo "<div class='alert alert-warning'>Solicitação não encontrada.</div>";
    exit;
}

$dao = new SolicitacaoCustomizacaoDAO($conn, $BASE_URL);
$record = $dao->findById($id);
if (!$record) {
    echo "<div class='alert alert-warning'>Solicitação não encontrada.</div>";
    exit;
}

$s = $record['solicitacao'];
$modulos = $record['modulos'] ?? [];
$tipos = $record['tipos'] ?? [];
$anexos = $record['anexos'] ?? [];
$modulosLabel = [];
foreach ($modulos as $mod) {
    $nome = $mod['modulo'] ?? '';
    if ($nome === 'Outro' && !empty($mod['modulo_outro'])) {
        $nome .= ' (' . $mod['modulo_outro'] . ')';
    }
    if ($nome !== '') {
        $modulosLabel[] = $nome;
    }
}
$tiposLabel = array_filter(array_map(fn($t) => $t['tipo'] ?? '', $tipos));
$modulosResumo = $modulosLabel ? implode(', ', $modulosLabel) : '-';
$tiposResumo = $tiposLabel ? implode(', ', $tiposLabel) : '-';
$moduloOutro = '-';
foreach ($modulos as $mod) {
    if (($mod['modulo'] ?? '') === 'Outro' && !empty($mod['modulo_outro'])) {
        $moduloOutro = $mod['modulo_outro'];
        break;
    }
}

?>

<div class="sc-view-block">
    <div class="sc-view-block-title">Bloco Conex</div>

    <div class="sc-view-section">
        <div class="sc-view-content">
            <div class="sc-view-grid">
                <div class="sc-view-item"><small>Solicitante</small><?= showVal($s->nome ?? '') ?></div>
                <div class="sc-view-item"><small>Resumo</small>
                    <div>Data: <?= showVal($s->data_solicitacao ?? '') ?></div>
                    <div>Prioridade: <?= showVal($s->prioridade ?? '') ?></div>
                    <div>Status: <?= showVal($s->status ?? '') ?></div>
                    <div>Módulos: <?= e($modulosResumo) ?></div>
                    <div>Tipos: <?= e($tiposResumo) ?></div>
                    <div>Outro: <?= showVal($moduloOutro) ?></div>
                </div>
                <div class="sc-view-item"><small>Contato</small>
                    <div><?= showVal($s->empresa ?? '') ?></div>
                    <div><?= showVal($s->cargo ?? '') ?></div>
                    <div><?= showVal($s->email ?? '') ?></div>
                    <div><?= showVal($s->telefone ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="sc-view-section">
        <div class="sc-view-content">
            <div class="sc-view-grid">
                <div class="sc-view-item"><small>Impacto</small>
                    <div>Impacto: <?= showVal($s->impacto_nivel ?? '') ?></div>
                    <div><?= showVal($s->descricao_impacto ?? '') ?></div>
                    <div>Prazo desejado: <?= showVal($s->prazo_desejado ?? '') ?></div>
                </div>
                <div class="sc-view-item"><small>Aprovação inicial</small>
                    <div>Responsável: <?= showVal($s->responsavel ?? '') ?></div>
                    <div>Assinatura: <?= showVal($s->assinatura ?? '') ?></div>
                    <div>Data: <?= showVal($s->data_aprovacao ?? '') ?></div>
                </div>
                <div class="sc-view-item"><small>Aprovação Conex</small>
                    <div>Aprovado: <?= showVal($s->aprovacao_conex ?? '') ?></div>
                    <div>Data: <?= showVal($s->data_aprovacao ?? '') ?></div>
                    <div>Responsável: <?= showVal($s->responsavel ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="sc-view-section">
        <div class="sc-view-content">
            <div class="sc-view-grid">
                <div class="sc-view-item"><small>Descrição</small><?= nl2br(showVal($s->descricao ?? '')) ?></div>
                <div class="sc-view-item"><small>Problema atual</small><?= nl2br(showVal($s->problema_atual ?? '')) ?></div>
                <div class="sc-view-item"><small>Resultado esperado</small><?= nl2br(showVal($s->resultado_esperado ?? '')) ?></div>
            </div>
        </div>
    </div>

    <div class="sc-view-section">
        <div class="sc-view-content">
            <div class="sc-view-item"><small>Anexos</small>
                <?php if (!$anexos): ?>
                    <span>-</span>
                <?php else: ?>
                    <ul class="mb-0">
                        <?php foreach ($anexos as $anexo): ?>
                            <li>
                                <a href="<?= $BASE_URL . e($anexo['arquivo'] ?? '') ?>" target="_blank">
                                    <?= e($anexo['nome_original'] ?? 'Arquivo') ?>
                                </a>
                                <?php if (!empty($anexo['tipo'])): ?>
                                    <small class="text-muted">(<?= e($anexo['tipo']) ?>)</small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="sc-view-block sc-view-block--fullcare">
    <div class="sc-view-block-title">Bloco FullCare</div>

    <div class="sc-view-section sc-view-fullcare">
        <div class="sc-view-content">
            <div class="sc-view-grid">
                <div class="sc-view-item"><small>Resposta FullCare</small>
                    <div>Prazo: <?= showVal($s->prazo_resposta ?? '') ?></div>
                    <div>Precificação: <?= showVal($s->precificacao ?? '') ?></div>
                    <div>Status: <?= showVal($s->status ?? '') ?></div>
                </div>
                <div class="sc-view-item"><small>Aprovação FullCare</small>
                    <div>Aprovação: <?= showVal($s->aprovacao_resposta ?? '') ?></div>
                    <div>Data: <?= showVal($s->data_resposta ?? '') ?></div>
                    <div>Resolvido em: <?= showVal($s->resolvido_em ?? '') ?></div>
                </div>
                <div class="sc-view-item"><small>Sistema</small>
                    <div>Versão: <?= showVal($s->versao_sistema ?? '') ?></div>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted d-block">Observações</small>
                <?= nl2br(showVal($s->observacoes_resposta ?? '')) ?>
            </div>
        </div>
    </div>
</div>
