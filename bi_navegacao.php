<?php
include_once("check_logado.php");
require_once("templates/header.php");

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$links = [
    ['label' => 'Comparativa Rede Hospitalar', 'href' => 'bi/rede-comparativa'],
    ['label' => 'UTI', 'href' => 'bi/uti'],
    ['label' => 'Patologia', 'href' => 'bi/patologia'],
    ['label' => 'Grupo Patologia', 'href' => 'bi/grupo-patologia'],
    ['label' => 'Antecedente', 'href' => 'bi/antecedente'],
    ['label' => 'Longa Permanência', 'href' => 'bi/longa-permanencia'],
    ['label' => 'Estratégia Terapêutica', 'href' => 'bi/estrategia-terapeutica'],
    ['label' => 'Médico Titular', 'href' => 'bi/medico-titular'],
    ['label' => 'Auditor', 'href' => 'bi/auditor'],
    ['label' => 'Auditor Visitas', 'href' => 'bi/auditor-visitas'],
    ['label' => 'Internações com Risco', 'href' => 'bi/internacoes-risco'],
    ['label' => 'Seguradora', 'href' => 'bi/seguradora'],
    ['label' => 'Seguradora Detalhado', 'href' => 'bi/seguradora-detalhado'],
    ['label' => 'Qualidade e Gestão', 'href' => 'bi/qualidade-gestao'],
    ['label' => 'Financeiro Realizado', 'href' => 'bi/financeiro-realizado'],
    ['label' => 'Clínico Realizado', 'href' => 'bi/clinico-realizado'],
    ['label' => 'Auditoria Produtividade', 'href' => 'bi/auditoria-produtividade'],
    ['label' => 'Consolidado Gestão', 'href' => 'bi/consolidado'],
    ['label' => 'Consolidado Gestão Cards', 'href' => 'bi/consolidado-cards'],
    ['label' => 'Alto Custo', 'href' => 'bi/alto-custo'],
    ['label' => 'Home Care', 'href' => 'bi/home-care'],
    ['label' => 'Desospitalizacao', 'href' => 'bi/desospitalizacao'],
    ['label' => 'OPME', 'href' => 'bi/opme'],
    ['label' => 'Evento Adverso', 'href' => 'bi/evento-adverso'],
    ['label' => 'Evolucao', 'href' => 'bi/evolucao'],
    ['label' => 'Ranking Patologia', 'href' => 'bi/ranking-patologia'],
    ['label' => 'Custo Medio Diarias', 'href' => 'bi/custo-medio-diarias'],
    ['label' => 'Ranking Hospitais', 'href' => 'bi/ranking-hospitais'],
    ['label' => 'Visita Inicial', 'href' => 'bi/visita-inicial'],
    ['label' => 'Ranking Pacientes', 'href' => 'bi/ranking-pacientes'],
    ['label' => 'Sinistro BI', 'href' => 'bi/sinistro'],
    ['label' => 'Producao BI', 'href' => 'bi/producao'],
    ['label' => 'Indicadores BI', 'href' => 'bi/indicadores'],
    ['label' => 'Sinistro YTD', 'href' => 'bi/sinistro-ytd'],
    ['label' => 'Producao YTD', 'href' => 'bi/producao-ytd'],
    ['label' => 'Saving', 'href' => 'bi/saving'],
    ['label' => 'Pacientes', 'href' => 'bi/pacientes'],
    ['label' => 'Hospitais', 'href' => 'bi/hospitais'],
    ['label' => 'Sinistro', 'href' => 'bi/sinistro-bi'],
    ['label' => 'Perfil Sinistro', 'href' => 'bi/perfil-sinistro'],
    ['label' => 'Sinistralidade por patologia', 'href' => 'bi/sinistro-patologia'],
    ['label' => 'Sinistralidade por hospital', 'href' => 'bi/sinistro-hospital'],
    ['label' => 'Tendencia de custo', 'href' => 'bi/sinistro-tendencia'],
    ['label' => 'Analise de alto custo', 'href' => 'bi/sinistro-alto-custo'],
    ['label' => 'Custo evitavel', 'href' => 'bi/sinistro-custo-evitavel'],
    ['label' => 'Concentracao de risco', 'href' => 'bi/sinistro-concentracao'],
    ['label' => 'Provisao vs. realizado', 'href' => 'bi/sinistro-provisao-realizado'],
    ['label' => 'Inteligencia Artificial', 'href' => 'bi/inteligencia'],
    ['label' => 'Tempo Médio Permanência', 'href' => 'inteligencia/tmp'],
    ['label' => 'Prorrogacao x Alta', 'href' => 'inteligencia/prorrogacao-vs-alta'],
    ['label' => 'Motivos Prorrogacao', 'href' => 'inteligencia/motivos-prorrogacao'],
    ['label' => 'Backlog Autorizacoes', 'href' => 'inteligencia/backlog-autorizacoes'],
    ['label' => 'Outliers de Permanência', 'href' => 'bi/anomalias-permanencia'],
    ['label' => 'Negociação Suspeita', 'href' => 'bi/anomalias-negociacao'],
    ['label' => 'Alto Custo sem Justificativa', 'href' => 'bi/anomalias-alto-custo'],
    ['label' => 'Readmissão Precoce', 'href' => 'bi/anomalias-readmissao'],
    ['label' => 'Variação de OPME', 'href' => 'bi/anomalias-opme-variacao'],
    ['label' => 'Cumprimento de Protocolos', 'href' => 'bi/conformidade-protocolos'],
    ['label' => 'Documentação Completa', 'href' => 'bi/conformidade-documentacao'],
    ['label' => 'Tempo de Resposta', 'href' => 'bi/conformidade-tempo-resposta'],
    ['label' => 'Adequação de Internação', 'href' => 'bi/conformidade-adequacao'],
    ['label' => 'Conformidade de Faturamento', 'href' => 'bi/conformidade-faturamento'],
    ['label' => 'Pacientes Crônicos', 'href' => 'bi/risco-cronicos'],
    ['label' => 'Alto Risco de Readmissão', 'href' => 'bi/risco-readmissao'],
    ['label' => 'Desospitalização Precoce', 'href' => 'bi/risco-desospitalizacao'],
    ['label' => 'Risco de Longa Permanência', 'href' => 'bi/risco-longa-permanencia'],
    ['label' => 'Casos Caros Previsíveis', 'href' => 'bi/risco-casos-previsiveis'],
    ['label' => 'Volume vs Custo', 'href' => 'bi/negociacao-volume-custo'],
    ['label' => 'Mix de Casos', 'href' => 'bi/negociacao-mix-casos'],
    ['label' => 'Taxa de Utilização', 'href' => 'bi/negociacao-utilizacao'],
    ['label' => 'Elasticidade de Preço', 'href' => 'bi/negociacao-elasticidade'],
    ['label' => 'Custo-Benefício da Rede', 'href' => 'bi/negociacao-custo-beneficio'],
    ['label' => 'Taxa de Complicação', 'href' => 'bi/qualidade-complicacao'],
    ['label' => 'Taxa de Óbito', 'href' => 'bi/qualidade-obito'],
    ['label' => 'Infecção Hospitalar', 'href' => 'bi/qualidade-infeccao'],
    ['label' => 'Eventos Adversos', 'href' => 'bi/qualidade-eventos'],
];
?>

<link rel="stylesheet" href="<?= $BASE_URL ?>css/bi.css?v=20260110">
<script src="<?= $BASE_URL ?>js/bi.js?v=20260110"></script>
<script>document.addEventListener('DOMContentLoaded', () => document.body.classList.add('bi-theme'));</script>

<div class="bi-wrapper bi-theme">
    <div class="bi-header">
        <h1 class="bi-title">Navegação BI</h1>
        <div class="bi-header-actions">
            <div class="text-end text-muted"></div>
            <a class="bi-nav-icon" href="<?= $BASE_URL ?>bi/navegacao" title="Navegação">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        </div>
    </div>

    <div class="bi-panel">
        <div class="bi-nav-title">Painel de Navegação</div>
        <div class="bi-nav-grid">
            <?php foreach ($links as $link): ?>
                <a class="bi-nav-card" href="<?= $BASE_URL . e($link['href']) ?>">
                    <?= e($link['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once("templates/footer.php"); ?>
