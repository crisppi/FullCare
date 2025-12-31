<?php
if (!isset($BASE_URL)) {
    $BASE_URL = '';
}

$biSections = [
    'Resumo' => [
        ['label' => 'Navegação', 'href' => 'bi/navegacao', 'file' => 'bi_navegacao.php', 'icon' => 'bi-grid'],
        ['label' => 'Consolidado', 'href' => 'bi/consolidado', 'file' => 'ConsolidadoGestaoBI.php', 'icon' => 'bi-layers'],
        ['label' => 'Indicadores BI', 'href' => 'bi/indicadores', 'file' => 'Indicadores.php', 'icon' => 'bi-speedometer2'],
    ],
    'Clínico' => [
        ['label' => 'UTI', 'href' => 'bi/uti', 'file' => 'bi_uti.php', 'icon' => 'bi-heart-pulse'],
        ['label' => 'Patologia', 'href' => 'bi/patologia', 'file' => 'bi_patologia.php', 'icon' => 'bi-bandaid'],
        ['label' => 'Grupo Patologia', 'href' => 'bi/grupo-patologia', 'file' => 'GrupoPatologia.php', 'icon' => 'bi-diagram-3'],
        ['label' => 'Antecedente', 'href' => 'bi/antecedente', 'file' => 'Antecedente.php', 'icon' => 'bi-journal-medical'],
        ['label' => 'Longa Permanência', 'href' => 'bi/longa-permanencia', 'file' => 'LongaPermanenciaBI.php', 'icon' => 'bi-hourglass-split'],
        ['label' => 'Clínico Realizado', 'href' => 'bi/clinico-realizado', 'file' => 'ClinicoRealizadoBI.php', 'icon' => 'bi-activity'],
        ['label' => 'Estratégia Terapêutica', 'href' => 'bi/estrategia-terapeutica', 'file' => 'EstrategiaTerapeuticaBI.php', 'icon' => 'bi-compass'],
        ['label' => 'Médico Titular', 'href' => 'bi/medico-titular', 'file' => 'MedicoTitularBI.php', 'icon' => 'bi-person-badge'],
        ['label' => 'Auditor', 'href' => 'bi/auditor', 'file' => 'AuditorBI.php', 'icon' => 'bi-person-check'],
        ['label' => 'Auditor Visitas', 'href' => 'bi/auditor-visitas', 'file' => 'AuditorVisitasBI.php', 'icon' => 'bi-clipboard-check'],
        ['label' => 'Auditoria Produtividade', 'href' => 'bi/auditoria-produtividade', 'file' => 'AuditoriaProdutividadeBI.php', 'icon' => 'bi-bar-chart-line'],
    ],
    'Operacional' => [
        ['label' => 'Seguradora', 'href' => 'bi/seguradora', 'file' => 'SeguradoraBI.php', 'icon' => 'bi-shield-check'],
        ['label' => 'Seguradora Detalhado', 'href' => 'bi/seguradora-detalhado', 'file' => 'SeguradoraDetalhadoBI.php', 'icon' => 'bi-shield-plus'],
        ['label' => 'Alto Custo', 'href' => 'bi/alto-custo', 'file' => 'AltoCusto.php', 'icon' => 'bi-cash-stack'],
        ['label' => 'Internações com Risco', 'href' => 'bi/internacoes-risco', 'file' => 'InternacoesRiscoBI.php', 'icon' => 'bi-exclamation-triangle'],
        ['label' => 'Qualidade e Gestão', 'href' => 'bi/qualidade-gestao', 'file' => 'QualidadeGestaoBI.php', 'icon' => 'bi-award'],
        ['label' => 'Home Care', 'href' => 'bi/home-care', 'file' => 'HomeCare.php', 'icon' => 'bi-house-heart'],
        ['label' => 'Desospitalização', 'href' => 'bi/desospitalizacao', 'file' => 'Desospitalizacao.php', 'icon' => 'bi-box-arrow-right'],
        ['label' => 'OPME', 'href' => 'bi/opme', 'file' => 'Opme.php', 'icon' => 'bi-capsule'],
        ['label' => 'Evento Adverso', 'href' => 'bi/evento-adverso', 'file' => 'EventoAdverso.php', 'icon' => 'bi-exclamation-circle'],
    ],
    'Financeiro' => [
        ['label' => 'Sinistro', 'href' => 'bi/sinistro', 'file' => 'Sinistro.php', 'icon' => 'bi-graph-up'],
        ['label' => 'Perfil Sinistro', 'href' => 'bi/perfil-sinistro', 'file' => 'bi_perfil_sinistro.php', 'icon' => 'bi-clipboard-data'],
        ['label' => 'Sinistro YTD', 'href' => 'bi/sinistro-ytd', 'file' => 'bi_sinistro_ytd.php', 'icon' => 'bi-bar-chart'],
        ['label' => 'Financeiro Realizado', 'href' => 'bi/financeiro-realizado', 'file' => 'FinanceiroRealizadoBI.php', 'icon' => 'bi-currency-dollar'],
        ['label' => 'Produção', 'href' => 'bi/producao', 'file' => 'Producao.php', 'icon' => 'bi-graph-up-arrow'],
        ['label' => 'Produção YTD', 'href' => 'bi/producao-ytd', 'file' => 'bi_producao_ytd.php', 'icon' => 'bi-graph-up'],
        ['label' => 'Saving', 'href' => 'bi/saving', 'file' => 'bi_saving.php', 'icon' => 'bi-piggy-bank'],
        ['label' => 'Pacientes', 'href' => 'bi/pacientes', 'file' => 'bi_pacientes.php', 'icon' => 'bi-people'],
        ['label' => 'Hospitais', 'href' => 'bi/hospitais', 'file' => 'bi_hospitais.php', 'icon' => 'bi-hospital'],
        ['label' => 'Inteligência Artificial', 'href' => 'bi/inteligencia', 'file' => 'bi_inteligencia.php', 'icon' => 'bi-cpu'],
        ['label' => 'Sinistro BI', 'href' => 'bi/sinistro-bi', 'file' => 'bi_sinistro.php', 'icon' => 'bi-graph-down'],
    ],
];

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$flatPages = [];
foreach ($biSections as $items) {
    foreach ($items as $item) {
        $flatPages[] = $item['file'] ?? $item['href'];
    }
}

if (!in_array($currentPage, $flatPages, true)) {
    return;
}

$favorites = [
    'bi_navegacao.php',
    'bi_hospitais.php',
    'bi_pacientes.php',
    'bi_uti.php',
];

$flatItems = [];
foreach ($biSections as $section => $items) {
    foreach ($items as $item) {
        $flatItems[] = $item + ['section' => $section];
    }
}

$favoriteItems = array_values(array_filter($flatItems, function ($item) use ($favorites) {
    return in_array($item['file'] ?? $item['href'], $favorites, true);
}));
?>

<style>
body.bi-has-sidebar {
    padding-left: 72px;
}

.bi-sidebar {
    position: fixed;
    top: 72px;
    left: 0;
    height: calc(100vh - 72px);
    width: 64px;
    background: rgba(255, 255, 255, 0.92);
    border-right: 1px solid #e4ddee;
    box-shadow: 4px 0 12px rgba(36, 18, 70, 0.06);
    z-index: 880;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 6px;
    gap: 12px;
}

.bi-sidebar-group {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.bi-sidebar-divider {
    width: 36px;
    height: 1px;
    background: #e6e0ee;
    margin: 4px auto;
}

.bi-side-link {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    border: 1px solid #e7e1ef;
    background: #f9f7fc;
    color: #5e2363;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    position: relative;
    transition: all .15s ease;
}

.bi-side-link:hover {
    background: #f0e9f7;
    border-color: #cdb9e4;
    color: #4d1d52;
}

.bi-side-link.is-active {
    background: #5e2363;
    color: #fff;
    border-color: #5e2363;
    box-shadow: 0 6px 12px rgba(94, 35, 99, 0.25);
}

.bi-side-link::after {
    content: attr(data-tip);
    position: absolute;
    left: 54px;
    top: 50%;
    transform: translateY(-50%);
    background: #2f223d;
    color: #fff;
    font-size: 0.78rem;
    padding: 6px 10px;
    border-radius: 8px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity .15s ease;
}

.bi-side-link:hover::after {
    opacity: 1;
}

.bi-side-heading {
    font-size: 0.6rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #8a7a98;
    font-weight: 700;
    text-align: center;
}

@media (max-width: 900px) {
    body.bi-has-sidebar {
        padding-left: 0;
    }

    .bi-sidebar {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('bi-has-sidebar');
});
</script>

<nav class="bi-sidebar" aria-label="Navegação BI">
    <div class="bi-side-heading">Favoritos</div>
    <div class="bi-sidebar-group">
        <?php foreach ($favoriteItems as $item): ?>
        <?php $itemFile = $item['file'] ?? $item['href']; ?>
        <a class="bi-side-link <?= $itemFile === $currentPage ? 'is-active' : '' ?>"
            href="<?= $BASE_URL . $item['href'] ?>"
            data-tip="<?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="bi-sidebar-divider"></div>
    <div class="bi-side-heading">BI</div>
    <div class="bi-sidebar-group">
        <?php foreach ($flatItems as $item): ?>
        <?php $itemFile = $item['file'] ?? $item['href']; ?>
        <a class="bi-side-link <?= $itemFile === $currentPage ? 'is-active' : '' ?>"
            href="<?= $BASE_URL . $item['href'] ?>"
            data-tip="<?= htmlspecialchars($item['section'] . ' • ' . $item['label'], ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
        </a>
        <?php endforeach; ?>
    </div>
</nav>
