<?php
if (!isset($BASE_URL)) {
    $BASE_URL = '';
}

$biTopGroups = [
    'Resumo' => [
        ['label' => 'Navegacao', 'href' => 'bi/navegacao', 'file' => 'bi_navegacao.php'],
        ['label' => 'Consolidado', 'href' => 'bi/consolidado', 'file' => 'ConsolidadoGestaoBI.php'],
        ['label' => 'Consolidado Cards', 'href' => 'bi/consolidado-cards', 'file' => 'ConsolidadoGestaoCardsBI.php'],
        ['label' => 'Indicadores BI', 'href' => 'bi/indicadores', 'file' => 'Indicadores.php'],
    ],
    'Clínico' => [
        ['label' => 'UTI', 'href' => 'bi/uti', 'file' => 'bi_uti.php'],
        ['label' => 'Patologia', 'href' => 'bi/patologia', 'file' => 'bi_patologia.php'],
        ['label' => 'Grupo Patologia', 'href' => 'bi/grupo-patologia', 'file' => 'GrupoPatologia.php'],
        ['label' => 'Antecedente', 'href' => 'bi/antecedente', 'file' => 'Antecedente.php'],
        ['label' => 'Longa Permanência', 'href' => 'bi/longa-permanencia', 'file' => 'LongaPermanenciaBI.php'],
        ['label' => 'Clínico Realizado', 'href' => 'bi/clinico-realizado', 'file' => 'ClinicoRealizadoBI.php'],
        ['label' => 'Estratégia Terapêutica', 'href' => 'bi/estrategia-terapeutica', 'file' => 'EstrategiaTerapeuticaBI.php'],
        ['label' => 'Médico Titular', 'href' => 'bi/medico-titular', 'file' => 'MedicoTitularBI.php'],
        ['label' => 'Auditor', 'href' => 'bi/auditor', 'file' => 'AuditorBI.php'],
        ['label' => 'Auditor Visitas', 'href' => 'bi/auditor-visitas', 'file' => 'AuditorVisitasBI.php'],
        ['label' => 'Auditoria Produtividade', 'href' => 'bi/auditoria-produtividade', 'file' => 'AuditoriaProdutividadeBI.php'],
    ],
    'Operacional' => [
        ['label' => 'Seguradora', 'href' => 'bi/seguradora', 'file' => 'SeguradoraBI.php'],
        ['label' => 'Seguradora Detalhado', 'href' => 'bi/seguradora-detalhado', 'file' => 'SeguradoraDetalhadoBI.php'],
        ['label' => 'Performance Rede Hospitalar', 'href' => 'bi/rede-comparativa', 'file' => 'bi_rede_comparativa.php'],
        ['label' => 'Alto Custo', 'href' => 'bi/alto-custo', 'file' => 'AltoCusto.php'],
        ['label' => 'Internações com Risco', 'href' => 'bi/internacoes-risco', 'file' => 'InternacoesRiscoBI.php'],
        ['label' => 'Qualidade e Gestão', 'href' => 'bi/qualidade-gestao', 'file' => 'QualidadeGestaoBI.php'],
        ['label' => 'Home Care', 'href' => 'bi/home-care', 'file' => 'HomeCare.php'],
        ['label' => 'Desospitalização', 'href' => 'bi/desospitalizacao', 'file' => 'Desospitalizacao.php'],
        ['label' => 'OPME', 'href' => 'bi/opme', 'file' => 'Opme.php'],
        ['label' => 'Evento Adverso', 'href' => 'bi/evento-adverso', 'file' => 'EventoAdverso.php'],
    ],
    'Comparativa Rede' => [
        ['label' => 'Comparativa', 'href' => 'bi/rede-comparativa', 'file' => 'bi_rede_comparativa.php'],
        ['label' => 'Custo por hospital', 'href' => 'bi/rede-custo', 'file' => 'bi_rede_custo.php'],
        ['label' => 'Glosa por hospital', 'href' => 'bi/rede-glosa', 'file' => 'bi_rede_glosa.php'],
        ['label' => 'Contas paradas', 'href' => 'bi/rede-paradas-capeante', 'file' => 'bi_rede_paradas_capeante.php'],
        ['label' => 'Permanência média', 'href' => 'bi/rede-permanencia', 'file' => 'bi_rede_permanencia.php'],
        ['label' => 'Eventos adversos', 'href' => 'bi/rede-eventos-adversos', 'file' => 'bi_rede_eventos_adversos.php'],
        ['label' => 'Readmissão 30d', 'href' => 'bi/rede-readmissao', 'file' => 'bi_rede_readmissao.php'],
        ['label' => 'Ranking', 'href' => 'bi/rede-ranking', 'file' => 'bi_rede_ranking.php'],
    ],
    'Financeiro' => [
        ['label' => 'Sinistro', 'href' => 'bi/sinistro', 'file' => 'Sinistro.php'],
        ['label' => 'Perfil Sinistro', 'href' => 'bi/perfil-sinistro', 'file' => 'bi_perfil_sinistro.php'],
        ['label' => 'Sinistro YTD', 'href' => 'bi/sinistro-ytd', 'file' => 'bi_sinistro_ytd.php'],
        ['label' => 'Financeiro Realizado', 'href' => 'bi/financeiro-realizado', 'file' => 'FinanceiroRealizadoBI.php'],
        ['label' => 'Produção', 'href' => 'bi/producao', 'file' => 'Producao.php'],
        ['label' => 'Produção YTD', 'href' => 'bi/producao-ytd', 'file' => 'bi_producao_ytd.php'],
        ['label' => 'Saving', 'href' => 'bi/saving', 'file' => 'bi_saving.php'],
        ['label' => 'Pacientes', 'href' => 'bi/pacientes', 'file' => 'bi_pacientes.php'],
        ['label' => 'Hospitais', 'href' => 'bi/hospitais', 'file' => 'bi_hospitais.php'],
        ['label' => 'Inteligencia Artificial', 'href' => 'bi/inteligencia', 'file' => 'bi_inteligencia.php'],
        ['label' => 'Sinistro BI', 'href' => 'bi/sinistro-bi', 'file' => 'bi_sinistro.php'],
    ],
    'Controle de Gastos' => [
        ['label' => 'Sinistralidade por Patologia', 'href' => 'bi/sinistro-patologia', 'file' => 'bi_sinistro_patologia.php'],
        ['label' => 'Sinistralidade por Hospital', 'href' => 'bi/sinistro-hospital', 'file' => 'bi_sinistro_hospital.php'],
        ['label' => 'Tendência de Custo', 'href' => 'bi/sinistro-tendencia', 'file' => 'bi_sinistro_tendencia.php'],
        ['label' => 'Análise de Alto Custo', 'href' => 'bi/sinistro-alto-custo', 'file' => 'bi_sinistro_alto_custo.php'],
        ['label' => 'Custo Evitável', 'href' => 'bi/sinistro-custo-evitavel', 'file' => 'bi_sinistro_custo_evitavel.php'],
        ['label' => 'Concentração de Risco', 'href' => 'bi/sinistro-concentracao', 'file' => 'bi_sinistro_concentracao.php'],
        ['label' => 'Provisão vs Realizado', 'href' => 'bi/sinistro-provisao-realizado', 'file' => 'bi_sinistro_provisao_realizado.php'],
        ['label' => 'Custo Médio Diárias', 'href' => 'bi/custo-medio-diarias', 'file' => 'bi_custo_medio_diarias.php'],
        ['label' => 'Ranking Patologia', 'href' => 'bi/ranking-patologia', 'file' => 'bi_ranking_patologia.php'],
        ['label' => 'Ranking Hospitais', 'href' => 'bi/ranking-hospitais', 'file' => 'bi_ranking_hospitais.php'],
        ['label' => 'Ranking Pacientes', 'href' => 'bi/ranking-pacientes', 'file' => 'bi_ranking_pacientes.php'],
    ],
    'Anomalias & Fraude' => [
        ['label' => 'Outliers de Permanência', 'href' => 'bi/anomalias-permanencia', 'file' => 'bi_anomalias_permanencia.php'],
        ['label' => 'Negociação Suspeita', 'href' => 'bi/anomalias-negociacao', 'file' => 'bi_anomalias_negociacao.php'],
        ['label' => 'Alto Custo sem Justificativa', 'href' => 'bi/anomalias-alto-custo', 'file' => 'bi_anomalias_alto_custo.php'],
        ['label' => 'Readmissão Precoce', 'href' => 'bi/anomalias-readmissao', 'file' => 'bi_anomalias_readmissao.php'],
        ['label' => 'Variação de OPME', 'href' => 'bi/anomalias-opme-variacao', 'file' => 'bi_anomalias_opme_variacao.php'],
    ],
    'Conformidade & Auditoria' => [
        ['label' => 'Cumprimento de Protocolos', 'href' => 'bi/conformidade-protocolos', 'file' => 'bi_conformidade_protocolos.php'],
        ['label' => 'Documentação Completa', 'href' => 'bi/conformidade-documentacao', 'file' => 'bi_conformidade_documentacao.php'],
        ['label' => 'Tempo de Resposta', 'href' => 'bi/conformidade-tempo-resposta', 'file' => 'bi_conformidade_tempo_resposta.php'],
        ['label' => 'Adequação de Internação', 'href' => 'bi/conformidade-adequacao', 'file' => 'bi_conformidade_adequacao_internacao.php'],
        ['label' => 'Conformidade de Faturamento', 'href' => 'bi/conformidade-faturamento', 'file' => 'bi_conformidade_faturamento.php'],
    ],
    'Segmentação de Risco' => [
        ['label' => 'Pacientes Crônicos', 'href' => 'bi/risco-cronicos', 'file' => 'bi_risco_cronicos.php'],
        ['label' => 'Alto Risco de Readmissão', 'href' => 'bi/risco-readmissao', 'file' => 'bi_risco_readmissao.php'],
        ['label' => 'Desospitalização Precoce', 'href' => 'bi/risco-desospitalizacao', 'file' => 'bi_risco_desospitalizacao.php'],
        ['label' => 'Risco de Longa Permanência', 'href' => 'bi/risco-longa-permanencia', 'file' => 'bi_risco_longa_permanencia.php'],
        ['label' => 'Casos Caros Previsíveis', 'href' => 'bi/risco-casos-previsiveis', 'file' => 'bi_risco_casos_previsiveis.php'],
    ],
    'Negociação & Rede' => [
        ['label' => 'Volume vs Custo', 'href' => 'bi/negociacao-volume-custo', 'file' => 'bi_negociacao_volume_custo.php'],
        ['label' => 'Mix de Casos', 'href' => 'bi/negociacao-mix-casos', 'file' => 'bi_negociacao_mix_casos.php'],
        ['label' => 'Taxa de Utilização', 'href' => 'bi/negociacao-utilizacao', 'file' => 'bi_negociacao_utilizacao_contratada.php'],
        ['label' => 'Elasticidade de Preço', 'href' => 'bi/negociacao-elasticidade', 'file' => 'bi_negociacao_elasticidade.php'],
        ['label' => 'Custo-Benefício da Rede', 'href' => 'bi/negociacao-custo-beneficio', 'file' => 'bi_negociacao_custo_beneficio.php'],
    ],
    'Qualidade & Desfecho' => [
        ['label' => 'Taxa de Complicação', 'href' => 'bi/qualidade-complicacao', 'file' => 'bi_qualidade_complicacao.php'],
        ['label' => 'Taxa de Óbito', 'href' => 'bi/qualidade-obito', 'file' => 'bi_qualidade_obito.php'],
        ['label' => 'Infecção Hospitalar', 'href' => 'bi/qualidade-infeccao', 'file' => 'bi_qualidade_infeccao.php'],
        ['label' => 'Eventos Adversos', 'href' => 'bi/qualidade-eventos', 'file' => 'bi_qualidade_eventos.php'],
    ],
];

$sectionKeys = [
    'Resumo' => 'resumo',
    'Clínico' => 'clinico',
    'Operacional' => 'operacional',
    'Comparativa Rede' => 'comparativa-rede',
    'Financeiro' => 'financeiro',
    'Controle de Gastos' => 'controle-gastos',
    'Anomalias & Fraude' => 'anomalias-fraude',
    'Conformidade & Auditoria' => 'conformidade-auditoria',
    'Segmentação de Risco' => 'segmentacao-risco',
    'Negociação & Rede' => 'negociacao-rede',
    'Qualidade & Desfecho' => 'qualidade-desfecho',
];

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$currentSection = '';
$currentLabel = '';
$flatPages = [];

foreach ($biTopGroups as $section => $items) {
    foreach ($items as $item) {
        $file = $item['file'] ?? $item['href'];
        $flatPages[] = $file;
        if ($file === $currentPage) {
            $currentSection = $section;
            $currentLabel = $item['label'];
        }
    }
}

if (!in_array($currentPage, $flatPages, true)) {
    return;
}
?>

<style>
.bi-topbar {
    position: sticky;
    top: 40px;
    z-index: 900;
    background: rgba(255, 255, 255, 0.62);
    border-bottom: 1px solid rgba(230, 224, 238, 0.45);
    box-shadow: 0 4px 12px rgba(40, 16, 72, 0.04);
    backdrop-filter: blur(8px);
}

.bi-topbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 18px;
}

.bi-topbar-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.78rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #6b5f79;
    font-weight: 700;
}

.bi-crumb {
    color: #3b2a4a;
    font-weight: 600;
    font-size: 0.95rem;
}

.bi-crumb span {
    color: #8a7a98;
    font-weight: 600;
    margin: 0 6px;
}

.bi-topbar-select {
    min-width: 220px;
    border-radius: 10px;
    padding: 6px 10px;
    border: 1px solid #d9d2e3;
    font-size: 0.9rem;
    color: #3b2a4a;
    background: #f7f5fa;
}

.bi-tabbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 6px 18px 0;
}

.bi-tab {
    border: 1px solid #d6cfe2;
    background: #f6f3fb;
    color: #4a3658;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 999px;
    cursor: pointer;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    transition: all .15s ease;
}

.bi-tab:hover {
    border-color: #5e2363;
    color: #5e2363;
}

.bi-tab.is-active {
    background: #e8def2;
    color: #3b2a4a;
    border-color: #bca9d6;
}

.bi-chipbar-wrap {
    display: none;
    padding: 8px 14px 12px;
    margin: 0 12px 6px;
    border-radius: 14px;
    background: rgba(164, 176, 216, 0.55);
    border: 1px solid rgba(136, 150, 204, 0.45);
}

.bi-chipbar-wrap.is-active {
    display: block;
}

.bi-chipbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.bi-chipbar-rail {
    display: none;
}

.bi-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    border: 1px solid #e3dcea;
    color: #4a3658;
    font-size: 0.85rem;
    text-decoration: none;
    background: #f6f3fb;
    white-space: nowrap;
    transition: all .15s ease;
}

.bi-chip:hover {
    border-color: #5e2363;
    color: #5e2363;
}

.bi-chip.is-active {
    background: #5e2363;
    color: #ffffff;
    border-color: #5e2363;
    box-shadow: 0 6px 14px rgba(94, 35, 99, 0.25);
}

.bi-chip--resumo {
    background: #f3f7ff;
    border-color: #d6e4ff;
    color: #2f4b7c;
}

.bi-chip--comparativa-rede {
    background: #f3f0ff;
    border-color: #d7cdfa;
    color: #4a2f7c;
}

.bi-chip--clinico {
    background: #fff2f5;
    border-color: #ffd2df;
    color: #7c2f4a;
}

.bi-chip--operacional {
    background: #f0f8ff;
    border-color: #cfe7ff;
    color: #245b84;
}

.bi-chip--financeiro {
    background: #fff7e6;
    border-color: #ffe0a6;
    color: #7a4a00;
}

.bi-chip--controle-gastos {
    background: #ffe7ec;
    border-color: #f5b3c2;
    color: #7a1f3a;
}

.bi-chip--anomalias-fraude {
    background: #fff2e6;
    border-color: #ffd4a8;
    color: #7a3d00;
}

.bi-chip--conformidade-auditoria {
    background: #e9f7f4;
    border-color: #b6e7dc;
    color: #1f5a4b;
}

.bi-chip--segmentacao-risco {
    background: #eef7ff;
    border-color: #cfe6ff;
    color: #2d4f7a;
}

.bi-chip--negociacao-rede {
    background: #f7f1ff;
    border-color: #dccdff;
    color: #4c2c7a;
}

.bi-chip--qualidade-desfecho {
    background: #fef6e8;
    border-color: #f6ddb1;
    color: #7a4b13;
}

.bi-chip--resumo:hover,
.bi-chip--comparativa-rede:hover,
.bi-chip--clinico:hover,
.bi-chip--operacional:hover,
.bi-chip--financeiro:hover,
.bi-chip--controle-gastos:hover,
.bi-chip--anomalias-fraude:hover,
.bi-chip--conformidade-auditoria:hover,
.bi-chip--segmentacao-risco:hover,
.bi-chip--negociacao-rede:hover,
.bi-chip--qualidade-desfecho:hover {
    border-color: #5e2363;
    color: #5e2363;
}

.bi-topbar-spacer {
    height: 28px;
}

    @media (max-width: 900px) {
        .bi-topbar-inner {
            flex-direction: column;
            align-items: flex-start;
        }

        .bi-topbar {
            top: 36px;
        }
    }

    @media (max-width: 600px) {
        .bi-tabbar {
            padding: 6px 12px 0;
        }

        .bi-chipbar {
            padding: 8px 12px 12px;
        }

        .bi-topbar-select {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<div class="bi-topbar">
    <div class="bi-topbar-inner">
        <div class="d-flex flex-column gap-1">
            <div class="bi-topbar-title">Navegação BI</div>
            <div class="bi-crumb">
                <?= htmlspecialchars($currentSection ?: 'Resumo', ENT_QUOTES, 'UTF-8') ?>
                <span>/</span>
                <?= htmlspecialchars($currentLabel ?: 'Painel', ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>
        <select class="bi-topbar-select" onchange="if (this.value) window.location.href=this.value;">
            <option value="">Ir para relatório...</option>
            <?php foreach ($biTopGroups as $section => $items): ?>
            <optgroup label="<?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($items as $item): ?>
                <?php $itemFile = $item['file'] ?? $item['href']; ?>
                <option value="<?= $BASE_URL . $item['href'] ?>" <?= $itemFile === $currentPage ? 'selected' : '' ?>>
                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="bi-tabbar" role="tablist" aria-label="Navegacao por categoria">
        <?php foreach ($biTopGroups as $section => $items): ?>
        <?php $sectionKey = $sectionKeys[$section] ?? 'grupo'; ?>
        <button class="bi-tab" type="button" data-section="<?= htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php foreach ($biTopGroups as $section => $items): ?>
    <?php $sectionKey = $sectionKeys[$section] ?? 'grupo'; ?>
    <div class="bi-chipbar-wrap" data-section="<?= htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') ?>">
        <div class="bi-chipbar">
            <?php foreach ($items as $item): ?>
            <?php $itemFile = $item['file'] ?? $item['href']; ?>
            <a class="bi-chip bi-chip--<?= htmlspecialchars($sectionKey, ENT_QUOTES, 'UTF-8') ?> <?= $itemFile === $currentPage ? 'is-active' : '' ?>"
                href="<?= $BASE_URL . $item['href'] ?>"
                title="<?= htmlspecialchars($section . ' • ' . $item['label'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="bi-chipbar-rail"></div>
    </div>
    <?php endforeach; ?>
</div>
    <div class="bi-topbar-spacer"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabs = document.querySelectorAll('.bi-tab');
    var bars = document.querySelectorAll('.bi-chipbar-wrap');
    if (!tabs.length || !bars.length) return;

    var activeSection = <?= json_encode($sectionKeys[$currentSection] ?? '') ?>;
    if (!activeSection) {
        activeSection = tabs[0].getAttribute('data-section');
    }

    function setActive(sectionKey) {
        tabs.forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-section') === sectionKey);
        });
        bars.forEach(function (bar) {
            bar.classList.toggle('is-active', bar.getAttribute('data-section') === sectionKey);
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            setActive(tab.getAttribute('data-section'));
        });
    });

    setActive(activeSection);
});
</script>
