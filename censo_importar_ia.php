<?php
include_once("check_logado.php");
include_once("globals.php");
require_once("app/services/CensoImportAiService.php");

$service = new CensoImportAiService($conn);
$preview = null;
$importResult = null;
$error = null;

if (!function_exists('censo_import_build_preview')) {
    function censo_import_build_preview(array $candidates, array $meta = []): array
    {
        return [
            'source' => $meta['source'] ?? 'previa',
            'file_name' => $meta['file_name'] ?? 'prévia corrigida',
            'total' => count($candidates),
            'valid' => count(array_filter($candidates, fn($item) => !empty($item['valid']))),
            'candidates' => array_values($candidates),
        ];
    }
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if (!function_exists('censo_import_e')) {
    function censo_import_e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('censo_import_csrf_ok')) {
    function censo_import_csrf_ok(): bool
    {
        return hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''));
    }
}

if (!function_exists('censo_import_date_br')) {
    function censo_import_date_br($value): string
    {
        $value = trim((string)$value);
        if ($value === '') return '';
        $time = strtotime($value);
        return $time ? date('d/m/Y', $time) : $value;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!censo_import_csrf_ok()) {
        $error = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'parse') {
                $preview = $service->parseUpload(
                    $_FILES['censo_file'] ?? [],
                    (string)($_POST['pdf_text'] ?? ''),
                    (string)($_POST['pdf_images'] ?? '')
                );
                $_SESSION['censo_import_candidates'] = $preview['candidates'];
                $_SESSION['censo_import_meta'] = [
                    'source' => $preview['source'],
                    'file_name' => $preview['file_name'],
                ];
            } elseif ($action === 'correct') {
                $candidates = $_SESSION['censo_import_candidates'] ?? [];
                $candidates = $service->applyManualEdits($candidates, $_POST['edit'] ?? []);
                $candidates = $service->applyInstruction($candidates, (string)($_POST['correction_prompt'] ?? ''));
                $_SESSION['censo_import_candidates'] = $candidates;
                $preview = censo_import_build_preview($candidates, array_merge($_SESSION['censo_import_meta'] ?? [], ['source' => 'previsao_corrigida']));
            } elseif ($action === 'import') {
                $candidates = $_SESSION['censo_import_candidates'] ?? [];
                $selected = $_POST['selected'] ?? [];
                if (!$selected) {
                    throw new RuntimeException('Selecione pelo menos uma linha para lançar no censo.');
                }
                $candidates = $service->applyManualEdits($candidates, $_POST['edit'] ?? []);
                $importResult = $service->importCandidates($candidates, $selected);
                unset($_SESSION['censo_import_candidates']);
                unset($_SESSION['censo_import_meta']);
                if ((int)($importResult['created'] ?? 0) > 0) {
                    header('Location: ' . $BASE_URL . 'censo/lista?importados_ia=' . (int)$importResult['created']);
                    exit;
                }
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

include_once("templates/header.php");
?>

<style>
    .censo-import-page {
        padding: 10px 16px 28px;
    }

    .censo-import-hero {
        align-items: center;
        background: linear-gradient(135deg, #236693 0%, #4b90bd 55%, #58ab74 100%);
        border-radius: 14px;
        color: #fff;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin-bottom: 14px;
        padding: 14px 16px;
    }

    .censo-import-hero h1 {
        font-size: 1.2rem;
        font-weight: 800;
        margin: 0;
    }

    .censo-import-hero p {
        color: rgba(255, 255, 255, .86);
        font-size: .78rem;
        margin: 4px 0 0;
    }

    .censo-import-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .censo-import-actions .btn {
        border-radius: 10px;
        font-size: .72rem;
        font-weight: 800;
        min-height: 34px;
    }

    .censo-import-panel {
        background: #fff;
        border: 1px solid rgba(76, 142, 187, .14);
        border-radius: 14px;
        box-shadow: 0 12px 24px rgba(38, 50, 56, .06);
        margin-bottom: 14px;
        padding: 14px;
    }

    .censo-import-upload {
        display: grid;
        gap: 10px;
        grid-template-columns: minmax(220px, 1fr) auto;
    }

    .censo-import-upload label {
        color: #2f6f9f;
        font-size: .72rem;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .censo-import-upload .form-control {
        min-height: 38px;
    }

    .censo-import-help {
        color: #64748b;
        font-size: .76rem;
        line-height: 1.45;
        margin: 10px 0 0;
    }

    .censo-import-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .censo-import-chip {
        background: #eef7fb;
        border: 1px solid rgba(76, 142, 187, .16);
        border-radius: 999px;
        color: #236693;
        font-size: .72rem;
        font-weight: 800;
        padding: 6px 10px;
    }

    .censo-import-table {
        font-size: .72rem;
        margin-bottom: 0;
        min-width: 1580px;
    }

    .censo-import-table th {
        background: #eef7fb !important;
        border-bottom: 1px solid rgba(76, 142, 187, .24) !important;
        color: #203246 !important;
        font-size: .66rem;
        font-weight: 900;
        letter-spacing: 0;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .censo-import-table td {
        color: #344054;
        vertical-align: middle;
    }

    .censo-import-table th:first-child,
    .censo-import-table td:first-child {
        min-width: 68px;
        text-align: center;
    }

    .censo-import-input {
        border: 1px solid rgba(76, 142, 187, .18);
        border-radius: 8px;
        color: #203246;
        font-size: .72rem;
        min-height: 32px;
        padding: 5px 7px;
        width: 100%;
    }

    .censo-import-input--date {
        min-width: 104px;
    }

    .censo-import-chat {
        background: #f8fbfe;
        border: 1px solid rgba(76, 142, 187, .16);
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 12px;
    }

    .censo-import-chat label {
        color: #236693;
        font-size: .72rem;
        font-weight: 900;
        margin-bottom: 6px;
    }

    .censo-import-chat textarea {
        font-size: .82rem;
        min-height: 74px;
        resize: vertical;
    }

    .censo-import-status {
        border-radius: 999px;
        display: inline-flex;
        font-size: .66rem;
        font-weight: 800;
        padding: 4px 8px;
        white-space: nowrap;
    }

    .censo-import-status--ok {
        background: #e7f8ed;
        color: #1d7f47;
    }

    .censo-import-status--error {
        background: #fff1f1;
        color: #b42318;
    }

    .censo-import-result {
        border-radius: 12px;
        margin-bottom: 14px;
        padding: 12px 14px;
    }

    .censo-import-result--ok {
        background: #ecfdf3;
        border: 1px solid #bde9cc;
        color: #166534;
    }

    .censo-import-result--error {
        background: #fff1f1;
        border: 1px solid #ffd1d1;
        color: #9f1239;
    }

    .censo-import-footer-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        margin-top: 12px;
    }

    @media (max-width: 760px) {
        .censo-import-hero,
        .censo-import-upload {
            align-items: stretch;
            display: flex;
            flex-direction: column;
        }
    }
</style>

<main class="container-fluid censo-import-page">
    <section class="censo-import-hero">
        <div>
            <h1>Importar censos com IA</h1>
            <p>Leia PDF, Excel ou CSV, confira a prévia e lance somente os registros válidos na lista de censos.</p>
        </div>
        <div class="censo-import-actions">
            <a class="btn btn-light" href="<?= censo_import_e($BASE_URL . 'censo/lista') ?>">
                <i class="bi bi-arrow-left"></i> Voltar para censos
            </a>
        </div>
    </section>

    <?php if ($error) { ?>
        <div class="censo-import-result censo-import-result--error">
            <strong>Não foi possível continuar.</strong><br>
            <?= censo_import_e($error) ?>
        </div>
    <?php } ?>

    <?php if ($importResult) { ?>
        <div class="censo-import-result censo-import-result--ok">
            <strong>Importação concluída.</strong><br>
            Lançados: <?= (int)$importResult['created'] ?>. Ignorados: <?= (int)$importResult['skipped'] ?>.
            <?php if (!empty($importResult['errors'])) { ?>
                <ul class="mb-0 mt-2">
                    <?php foreach ($importResult['errors'] as $itemError) { ?>
                        <li><?= censo_import_e($itemError) ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    <?php } ?>

    <section class="censo-import-panel">
        <form method="POST" enctype="multipart/form-data" id="censoImportParseForm">
            <input type="hidden" name="csrf" value="<?= censo_import_e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="parse">
            <textarea name="pdf_text" id="censoPdfText" hidden></textarea>
            <textarea name="pdf_images" id="censoPdfImages" hidden></textarea>

            <div class="censo-import-upload">
                <div>
                    <label for="censoFile">Arquivo de censo</label>
                    <input class="form-control" type="file" id="censoFile" name="censo_file" accept=".pdf,.xlsx,.xls,.csv,application/pdf,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </div>
                <button class="btn btn-primary align-self-end" type="submit" id="censoImportReadBtn">
                    <i class="bi bi-file-earmark-arrow-up"></i> Ler arquivo
                </button>
            </div>
            <p class="censo-import-help">
                A importação não cria paciente ou hospital automaticamente. Linhas sem correspondência ficam pendentes na prévia para correção no cadastro antes de lançar.
            </p>
        </form>
    </section>

    <?php if ($preview) { ?>
        <section class="censo-import-panel">
            <div class="censo-import-summary">
                <span class="censo-import-chip">Arquivo: <?= censo_import_e($preview['file_name']) ?></span>
                <span class="censo-import-chip">Origem: <?= censo_import_e($preview['source']) ?></span>
                <span class="censo-import-chip">Lidas: <?= (int)$preview['total'] ?></span>
                <span class="censo-import-chip">Válidas: <?= (int)$preview['valid'] ?></span>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?= censo_import_e($_SESSION['csrf']) ?>">
                <div class="censo-import-chat">
                    <label for="correctionPrompt">Instruções para corrigir a leitura</label>
                    <textarea class="form-control" id="correctionPrompt" name="correction_prompt" placeholder="Ex.: Hospital correto é Santa Isabel. A data correta do censo é 13/06/2026. Onde aparecer S. Isabel, considerar Santa Isabel."></textarea>
                    <p class="censo-import-help mb-0">Você também pode corrigir diretamente os campos da tabela antes de lançar.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover censo-import-table">
                        <thead>
                            <tr>
                                <th>Lançar</th>
                                <th>Status</th>
                                <th>Linha</th>
                                <th>Hospital lido</th>
                                <th>Hospital encontrado</th>
                                <th>Paciente lido</th>
                                <th>Paciente encontrado</th>
                                <th>Data</th>
                                <th>Senha</th>
                                <th>Acomodação</th>
                                <th>Médico</th>
                                <th>Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview['candidates'] as $index => $item) {
                                $isValid = !empty($item['valid']);
                            ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="selected[]" value="<?= (int)$index ?>" checked>
                                    </td>
                                    <td>
                                        <span class="censo-import-status <?= $isValid ? 'censo-import-status--ok' : 'censo-import-status--error' ?>">
                                            <?= $isValid ? 'Válido' : 'Pendente' ?>
                                        </span>
                                    </td>
                                    <td><?= (int)($item['row_number'] ?? 0) ?></td>
                                    <td>
                                        <input class="censo-import-input" name="edit[<?= (int)$index ?>][hospital]" value="<?= censo_import_e($item['hospital'] ?? '') ?>">
                                    </td>
                                    <td><?= censo_import_e($item['hospital_match']['nome_hosp'] ?? '') ?></td>
                                    <td>
                                        <input class="censo-import-input" name="edit[<?= (int)$index ?>][paciente]" value="<?= censo_import_e($item['paciente'] ?? '') ?>">
                                    </td>
                                    <td><?= censo_import_e($item['patient_match']['nome_pac'] ?? '') ?></td>
                                    <td>
                                        <input class="censo-import-input censo-import-input--date" name="edit[<?= (int)$index ?>][data_censo]" value="<?= censo_import_e(censo_import_date_br($item['data_censo'] ?? '')) ?>" placeholder="dd/mm/aaaa">
                                    </td>
                                    <td>
                                        <input class="censo-import-input" name="edit[<?= (int)$index ?>][senha]" value="<?= censo_import_e($item['senha'] ?? '') ?>">
                                    </td>
                                    <td>
                                        <input class="censo-import-input" name="edit[<?= (int)$index ?>][acomodacao]" value="<?= censo_import_e($item['acomodacao'] ?? '') ?>">
                                    </td>
                                    <td>
                                        <input class="censo-import-input" name="edit[<?= (int)$index ?>][medico]" value="<?= censo_import_e($item['medico'] ?? '') ?>">
                                    </td>
                                    <td><?= censo_import_e(implode('; ', $item['errors'] ?? [])) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="censo-import-footer-actions">
                    <a class="btn btn-outline-secondary" href="<?= censo_import_e($BASE_URL . 'censo/importar-ia') ?>">Limpar</a>
                    <button class="btn btn-outline-primary" type="submit" name="action" value="correct">
                        <i class="bi bi-chat-dots"></i> Aplicar correções
                    </button>
                    <button class="btn btn-success" type="submit" name="action" value="import">
                        <i class="bi bi-check2-circle"></i> Lançar selecionados no censo
                    </button>
                </div>
            </form>
        </section>
    <?php } ?>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    (function () {
        const form = document.getElementById('censoImportParseForm');
        const fileInput = document.getElementById('censoFile');
        const textInput = document.getElementById('censoPdfText');
        const imageInput = document.getElementById('censoPdfImages');
        const submitButton = document.getElementById('censoImportReadBtn');

        if (!form || !fileInput || !textInput || !imageInput || !submitButton) return;
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }

        async function extractPdfText(file) {
            const buffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
            const parts = [];
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const content = await page.getTextContent();
                parts.push(content.items.map((item) => item.str || '').join(' '));
            }
            return parts.join('\n').replace(/\s+\n/g, '\n').trim();
        }

        async function renderPdfImages(file) {
            const buffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
            const images = [];
            const maxPages = Math.min(pdf.numPages, 8);
            for (let pageNum = 1; pageNum <= maxPages; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const viewport = page.getViewport({ scale: 1.5 });
                const canvas = document.createElement('canvas');
                canvas.width = Math.floor(viewport.width);
                canvas.height = Math.floor(viewport.height);
                const context = canvas.getContext('2d', { alpha: false });
                await page.render({ canvasContext: context, viewport }).promise;
                images.push(canvas.toDataURL('image/jpeg', 0.72));
            }
            return images;
        }

        form.addEventListener('submit', async function (event) {
            const file = fileInput.files && fileInput.files[0];
            if (!file || !/\.pdf$/i.test(file.name) || textInput.value.trim() !== '') return;

            event.preventDefault();
            if (!window.pdfjsLib) {
                alert('Leitor de PDF não carregou. Recarregue a página e tente novamente.');
                return;
            }

            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Lendo PDF...';
            try {
                textInput.value = await extractPdfText(file);
                if (textInput.value.trim().length < 20) {
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Lendo imagem com IA...';
                    imageInput.value = JSON.stringify(await renderPdfImages(file));
                }
                form.submit();
            } catch (error) {
                alert('Não consegui ler o PDF. Tente salvar o arquivo como Excel ou CSV.');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    })();
</script>

<?php include_once("templates/footer.php"); ?>
