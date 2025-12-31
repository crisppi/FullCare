<?php
if (!isset($BASE_URL)) {
    $BASE_URL = '';
}

$biGroups = [
    [
        'title' => 'Clínico',
        'desc' => 'Indicadores assistenciais e perfis clínicos.',
        'links' => [
            ['label' => 'UTI', 'href' => 'bi_uti.php'],
            ['label' => 'Patologia', 'href' => 'bi_patologia.php'],
            ['label' => 'Longa Permanência', 'href' => 'LongaPermanenciaBI.php'],
            ['label' => 'Clínico Realizado', 'href' => 'ClinicoRealizadoBI.php'],
        ],
    ],
    [
        'title' => 'Operacional',
        'desc' => 'Risco, qualidade e ações operacionais.',
        'links' => [
            ['label' => 'Seguradora', 'href' => 'SeguradoraBI.php'],
            ['label' => 'Alto Custo', 'href' => 'AltoCusto.php'],
            ['label' => 'Internações com Risco', 'href' => 'InternacoesRiscoBI.php'],
            ['label' => 'Qualidade e Gestão', 'href' => 'QualidadeGestaoBI.php'],
        ],
    ],
    [
        'title' => 'Financeiro',
        'desc' => 'Visão de resultado e produção financeira.',
        'links' => [
            ['label' => 'Sinistro', 'href' => 'Sinistro.php'],
            ['label' => 'Financeiro Realizado', 'href' => 'FinanceiroRealizadoBI.php'],
            ['label' => 'Produção', 'href' => 'Producao.php'],
            ['label' => 'Saving', 'href' => 'bi_saving.php'],
        ],
    ],
];

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
if (!preg_match('/^(bi_.*\\.php|.*BI\\.php)$/i', $currentPage)) {
    return;
}
?>

<style>
.bi-cards-wrap {
    margin-top: 72px;
    padding: 18px 22px 0;
}

.bi-cards-title {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #6b5f79;
    font-weight: 700;
    margin-bottom: 12px;
}

.bi-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
}

.bi-card {
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid #e5ddef;
    box-shadow: 0 8px 18px rgba(40, 16, 72, 0.06);
    padding: 14px 16px;
}

.bi-card h4 {
    font-size: 1rem;
    margin: 0 0 6px;
    color: #3b2a4a;
}

.bi-card p {
    margin: 0 0 10px;
    color: #7b6b8a;
    font-size: 0.85rem;
}

.bi-card-links {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.bi-card-links a {
    text-decoration: none;
    font-size: 0.82rem;
    color: #4a3658;
    border: 1px solid #e1d7ee;
    background: #f7f4fb;
    border-radius: 999px;
    padding: 5px 10px;
    transition: all .15s ease;
}

.bi-card-links a:hover {
    border-color: #bca9d6;
    color: #5e2363;
}

@media (max-width: 900px) {
    .bi-cards-wrap {
        margin-top: 64px;
        padding: 14px 16px 0;
    }
}
</style>

<div class="bi-cards-wrap">
    <div class="bi-cards-title">Atalhos BI</div>
    <div class="bi-cards-grid">
        <?php foreach ($biGroups as $group): ?>
        <div class="bi-card">
            <h4><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?></h4>
            <p><?= htmlspecialchars($group['desc'], ENT_QUOTES, 'UTF-8') ?></p>
            <div class="bi-card-links">
                <?php foreach ($group['links'] as $item): ?>
                <a href="<?= $BASE_URL . $item['href'] ?>">
                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
