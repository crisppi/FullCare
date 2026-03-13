<?php
include_once("check_logado.php");
include_once("db.php");
require_once("app/services/UtiAuditAiService.php");

$sampleReport = "Paciente 68 anos, sepse de foco pulmonar, em UTI, em uso de noradrenalina 0,18 mcg/kg/min, VM invasiva, PaO2/FiO2 140, lactato 3,8, creatinina 2,1 com oliguria, Glasgow 10, necessidade de monitorizacao continua e ajuste frequente de suporte hemodinamico e ventilatorio.";
$report = trim((string)(filter_input(INPUT_POST, 'relatorio_uti') ?? $sampleReport));
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $service = new UtiAuditAiService();
        $result = $service->analyzeReport($report);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

include_once("templates/header.php");

function eUtiTest($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fmtClassificacao(string $value): string
{
    if ($value === 'JUSTIFICADO') {
        return 'Justificado';
    }
    if ($value === 'NAO_JUSTIFICADO') {
        return 'Nao justificado';
    }
    return 'Dados insuficientes';
}
?>

<style>
.uti-ai-card {
    background: #fff;
    border: 1px solid #e3e7ee;
    border-radius: 16px;
    box-shadow: 0 10px 28px rgba(32, 56, 85, 0.08);
    padding: 1.5rem;
}
.uti-ai-label {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #6c7a89;
    font-weight: 700;
}
.uti-ai-value {
    font-size: 1.05rem;
    color: #243447;
}
.uti-ai-badge {
    display: inline-block;
    border-radius: 999px;
    padding: 0.35rem 0.8rem;
    font-weight: 700;
    font-size: 0.85rem;
}
.uti-ai-badge--ok {
    background: #e6f7ee;
    color: #157347;
}
.uti-ai-badge--warn {
    background: #fff4e5;
    color: #b26a00;
}
.uti-ai-badge--no {
    background: #fdebec;
    color: #b42318;
}
.uti-ai-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}
.uti-ai-report {
    min-height: 260px;
    resize: vertical;
}
</style>

<main class="container-fluid mt-5 pt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <strong>Teste de viabilidade:</strong> esta tela envia um relatório clínico de UTI para a API já usada no projeto e retorna classificação estruturada para auditoria. O resultado é apoio técnico e não substitui validação médica.
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="uti-ai-card">
                <h4 class="mb-3">Relatório clínico</h4>
                <form method="post">
                    <div class="mb-3">
                        <label for="relatorio_uti" class="form-label">Cole o relatório a ser analisado</label>
                        <textarea class="form-control uti-ai-report" id="relatorio_uti" name="relatorio_uti" required><?= eUtiTest($report) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Analisar com IA</button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="uti-ai-card">
                <h4 class="mb-3">Resultado</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger mb-0"><?= eUtiTest($error) ?></div>
                <?php elseif ($result): ?>
                    <?php
                    $badgeClass = 'uti-ai-badge--warn';
                    if (($result['classificacao'] ?? '') === 'JUSTIFICADO') {
                        $badgeClass = 'uti-ai-badge--ok';
                    } elseif (($result['classificacao'] ?? '') === 'NAO_JUSTIFICADO') {
                        $badgeClass = 'uti-ai-badge--no';
                    }
                    ?>
                    <div class="mb-3">
                        <span class="uti-ai-badge <?= $badgeClass ?>"><?= eUtiTest(fmtClassificacao((string)$result['classificacao'])) ?></span>
                    </div>

                    <div class="mb-3">
                        <div class="uti-ai-label">Resumo clínico</div>
                        <div class="uti-ai-value"><?= nl2br(eUtiTest($result['resumo_clinico'] ?? '')) ?></div>
                    </div>

                    <div class="uti-ai-grid mb-3">
                        <?php foreach (($result['criterios'] ?? []) as $label => $value): ?>
                            <div>
                                <div class="uti-ai-label"><?= eUtiTest(str_replace('_', ' ', $label)) ?></div>
                                <div class="uti-ai-value"><?= eUtiTest($value) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3">
                        <div class="uti-ai-label">Justificativa técnica</div>
                        <div class="uti-ai-value"><?= nl2br(eUtiTest($result['justificativa_tecnica'] ?? '')) ?></div>
                    </div>

                    <div class="mb-3">
                        <div class="uti-ai-label">Pendências documentais</div>
                        <?php if (!empty($result['pendencias_documentais'])): ?>
                            <ul class="mb-0">
                                <?php foreach ($result['pendencias_documentais'] as $item): ?>
                                    <li><?= eUtiTest($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="uti-ai-value">Sem pendências relevantes apontadas.</div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="uti-ai-label">Diagnóstico do teste</div>
                        <div class="uti-ai-value">
                            Modelo <?= eUtiTest($result['meta']['model'] ?? '') ?>, payload com <?= eUtiTest((string)($result['meta']['report_chars'] ?? 0)) ?> caracteres.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-muted">Nenhuma análise executada ainda.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include_once("templates/footer.php"); ?>
