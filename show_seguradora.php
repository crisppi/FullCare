<?php
include_once("check_logado.php");
include_once("globals.php");
include_once("models/seguradora.php");
include_once("dao/seguradoraDao.php");
include_once("templates/header.php");

$id_seguradora = filter_input(INPUT_GET, "id_seguradora", FILTER_VALIDATE_INT);
$seguradoraDao = new seguradoraDAO($conn, $BASE_URL);
$seguradora = $id_seguradora ? $seguradoraDao->findById($id_seguradora) : null;

if (!$seguradora) {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Seguradora não encontrada.</div></div>";
    include_once("templates/footer.php");
    exit;
}

function seguradoraShowEsc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function seguradoraShowValue($value): string
{
    $value = trim((string)$value);
    return $value !== '' ? seguradoraShowEsc($value) : '-';
}

function seguradoraShowPhone($value): string
{
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') return '-';
    if (strlen($digits) === 10) return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6);
    if (strlen($digits) === 11) return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
    return seguradoraShowEsc((string)$value);
}

function seguradoraShowCnpj($value): string
{
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') return '-';
    if (strlen($digits) === 14) {
        return substr($digits, 0, 2) . '.' . substr($digits, 2, 3) . '.' . substr($digits, 5, 3) . '/' . substr($digits, 8, 4) . '-' . substr($digits, 12, 2);
    }
    return seguradoraShowEsc((string)$value);
}

function seguradoraShowDate($value): string
{
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') return '-';
    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y', $timestamp) : seguradoraShowEsc($value);
}

function seguradoraShowLogoUrl($logo, string $baseUrl): ?string
{
    $logo = trim((string)$logo);
    if ($logo === '') return null;
    if (preg_match('#^https?://#i', $logo)) return $logo;
    $logoPath = ltrim($logo, '/');
    $relativePath = stripos($logoPath, 'uploads/') === 0 ? $logoPath : 'uploads/' . $logoPath;
    $localPath = __DIR__ . '/' . $relativePath;
    return is_file($localPath) ? $baseUrl . $relativePath : null;
}

$statusAtivo = strtolower((string)($seguradora->ativo_seg ?? '')) === 's';
$statusLabel = $statusAtivo ? 'Ativa' : 'Inativa';
$statusClass = $statusAtivo ? 'is-active' : 'is-inactive';
$logoUrl = seguradoraShowLogoUrl($seguradora->logo_seg ?? '', $BASE_URL);
$endereco = trim(implode(' ', array_filter([
    trim((string)($seguradora->endereco_seg ?? '')),
    trim((string)($seguradora->numero_seg ?? '')) !== '' ? ', ' . trim((string)$seguradora->numero_seg) : '',
])));
?>
<script src="js/timeout.js"></script>
<link rel="stylesheet" href="css/form_cad_internacao.css?v=<?= @filemtime(__DIR__ . '/css/form_cad_internacao.css') ?>">

<style>
.entity-show-page { padding: 0 16px 96px; }
.entity-show-page .internacao-page__hero { margin-bottom: 14px; }
.entity-profile-card { display: grid; grid-template-columns: minmax(220px, 300px) minmax(0, 1fr); gap: 16px; align-items: stretch; }
.entity-profile-summary, .entity-info-card, .entity-danger-card { background: #fff; border: 1px solid rgba(47, 111, 159, 0.12); border-radius: 14px; box-shadow: 0 12px 30px rgba(47, 60, 85, 0.08); }
.entity-profile-summary { padding: 18px; display: flex; flex-direction: column; align-items: center; text-align: center; min-height: 100%; }
.entity-logo { width: 112px; height: 112px; border-radius: 28px; display: grid; place-items: center; object-fit: contain; background: #eef6fb; border: 4px solid #eef6fb; box-shadow: 0 10px 24px rgba(47, 111, 159, 0.16); }
.entity-logo-placeholder { color: #2f6f9f; font-size: 2.8rem; }
.entity-name { margin: 14px 0 4px; color: #1f2937; font-size: 1.22rem; font-weight: 800; }
.entity-location { margin: 0; color: #667085; font-size: 0.92rem; }
.entity-status { display: inline-flex; align-items: center; gap: 6px; margin-top: 14px; padding: 6px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 800; }
.entity-status::before { content: ""; width: 8px; height: 8px; border-radius: 999px; background: currentColor; }
.entity-status.is-active { background: #eaf8f0; color: #16834d; }
.entity-status.is-inactive { background: #fff1f2; color: #be123c; }
.entity-summary-meta { width: 100%; display: grid; gap: 8px; margin-top: 18px; padding-top: 16px; border-top: 1px solid #edf2f7; text-align: left; }
.entity-summary-meta span { display: flex; justify-content: space-between; gap: 12px; color: #667085; font-size: 0.82rem; }
.entity-summary-meta strong { color: #334155; font-weight: 800; }
.entity-info-stack { display: grid; gap: 14px; }
.entity-info-card { padding: 16px; }
.entity-info-card h3, .entity-danger-card h3 { margin: 0; color: #24384f; font-size: 1rem; font-weight: 800; }
.entity-card-subtitle { margin: 3px 0 0; color: #64748b; font-size: 0.84rem; }
.entity-field-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
.entity-field { min-height: 74px; padding: 11px 12px; border: 1px solid #e5edf4; border-radius: 10px; background: #f8fbfd; }
.entity-field label { display: block; margin: 0 0 5px; padding: 0; color: #64748b; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; }
.entity-field div { color: #1f2937; font-size: 0.94rem; font-weight: 600; word-break: break-word; }
.entity-danger-card { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-top: 14px; padding: 16px; border-color: rgba(190, 18, 60, 0.18); background: linear-gradient(135deg, #fff 0%, #fff7f7 100%); }
.entity-danger-card p { margin: 4px 0 0; color: #667085; font-size: 0.88rem; }
.entity-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.entity-actions .btn { border-radius: 10px; font-weight: 700; padding: 9px 14px; }
@media (max-width: 980px) { .entity-profile-card, .entity-field-grid { grid-template-columns: 1fr; } .entity-danger-card { align-items: flex-start; flex-direction: column; } }
</style>

<main id="main-container" class="internacao-page cadastro-layout entity-show-page">
    <div class="internacao-page__hero">
        <div class="internacao-page__hero-main">
            <h1>Dados da seguradora</h1>
        </div>
        <div class="hero-actions">
            <a href="<?= $BASE_URL ?>seguradoras" class="hero-back-btn">Voltar para lista</a>
            <a href="<?= $BASE_URL ?>seguradoras/editar/<?= (int)$seguradora->id_seguradora ?>" class="hero-back-btn">Editar seguradora</a>
            <span class="internacao-page__tag">Registro #<?= (int)$seguradora->id_seguradora ?></span>
        </div>
    </div>

    <div class="entity-profile-card">
        <aside class="entity-profile-summary">
            <?php if ($logoUrl): ?>
                <img src="<?= seguradoraShowEsc($logoUrl) ?>" alt="Logo de <?= seguradoraShowValue($seguradora->seguradora_seg ?? '') ?>" class="entity-logo">
            <?php else: ?>
                <div class="entity-logo entity-logo-placeholder" aria-hidden="true"><i class="bi bi-heart-pulse"></i></div>
            <?php endif; ?>
            <h2 class="entity-name"><?= seguradoraShowValue($seguradora->seguradora_seg ?? '') ?></h2>
            <p class="entity-location"><?= seguradoraShowValue(trim((string)($seguradora->cidade_seg ?? '') . ' / ' . (string)($seguradora->estado_seg ?? ''), ' /')) ?></p>
            <span class="entity-status <?= $statusClass ?>"><?= $statusLabel ?></span>
            <div class="entity-summary-meta">
                <span><strong>CNPJ</strong><?= seguradoraShowCnpj($seguradora->cnpj_seg ?? '') ?></span>
                <span><strong>CEP</strong><?= seguradoraShowValue($seguradora->cep_seg ?? '') ?></span>
                <span><strong>Cadastrada</strong><?= seguradoraShowDate($seguradora->data_create_seg ?? '') ?></span>
            </div>
        </aside>

        <section class="entity-info-stack">
            <div class="entity-info-card">
                <h3>Identificação</h3>
                <p class="entity-card-subtitle">Dados contratuais e parâmetros operacionais.</p>
                <div class="entity-field-grid">
                    <div class="entity-field"><label>Seguradora</label><div><?= seguradoraShowValue($seguradora->seguradora_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>CNPJ</label><div><?= seguradoraShowCnpj($seguradora->cnpj_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Coordenador</label><div><?= seguradoraShowValue($seguradora->coordenador_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Contato</label><div><?= seguradoraShowValue($seguradora->contato_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Coord. RH</label><div><?= seguradoraShowValue($seguradora->coord_rh_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Criada por</label><div><?= seguradoraShowValue($seguradora->usuario_create_seg ?? '') ?></div></div>
                </div>
            </div>

            <div class="entity-info-card">
                <h3>Contato</h3>
                <p class="entity-card-subtitle">Canais administrativos da seguradora.</p>
                <div class="entity-field-grid">
                    <div class="entity-field"><label>E-mail principal</label><div><?= seguradoraShowValue($seguradora->email01_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>E-mail secundário</label><div><?= seguradoraShowValue($seguradora->email02_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Telefone principal</label><div><?= seguradoraShowPhone($seguradora->telefone01_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Telefone secundário</label><div><?= seguradoraShowPhone($seguradora->telefone02_seg ?? '') ?></div></div>
                </div>
            </div>

            <div class="entity-info-card">
                <h3>Endereço</h3>
                <p class="entity-card-subtitle">Localização registrada.</p>
                <div class="entity-field-grid">
                    <div class="entity-field"><label>Endereço</label><div><?= seguradoraShowValue($endereco) ?></div></div>
                    <div class="entity-field"><label>Bairro</label><div><?= seguradoraShowValue($seguradora->bairro_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Cidade</label><div><?= seguradoraShowValue($seguradora->cidade_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Estado</label><div><?= seguradoraShowValue($seguradora->estado_seg ?? '') ?></div></div>
                </div>
            </div>

            <div class="entity-info-card">
                <h3>Parâmetros</h3>
                <p class="entity-card-subtitle">Regras de operação vinculadas à seguradora.</p>
                <div class="entity-field-grid">
                    <div class="entity-field"><label>Alto custo</label><div><?= seguradoraShowValue($seguradora->valor_alto_custo_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Dias visita</label><div><?= seguradoraShowValue($seguradora->dias_visita_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Dias visita UTI</label><div><?= seguradoraShowValue($seguradora->dias_visita_uti_seg ?? '') ?></div></div>
                    <div class="entity-field"><label>Longa permanência</label><div><?= seguradoraShowValue($seguradora->longa_permanencia_seg ?? '') ?></div></div>
                </div>
            </div>

            <div class="entity-danger-card">
                <div>
                    <h3>Inativar seguradora</h3>
                    <p>Use esta ação apenas quando a seguradora não deve mais aparecer como ativa.</p>
                </div>
                <form action="<?= $BASE_URL ?>process_seguradora.php?id_seguradora=<?= (int)$id_seguradora ?>" method="POST" class="entity-actions">
                    <input type="hidden" name="typeDel" value="delUpdate">
                    <input type="hidden" name="id_seguradora" value="<?= (int)$seguradora->id_seguradora ?>">
                    <a href="<?= $BASE_URL ?>seguradoras" class="btn btn-outline-secondary">Cancelar</a>
                    <button class="btn btn-danger" value="deletar" type="submit" id="deletar-btn" name="deletar">Inativar</button>
                </form>
            </div>
        </section>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once("templates/footer.php"); ?>
