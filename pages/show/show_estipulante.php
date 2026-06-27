<?php
include_once("check_logado.php");
include_once("globals.php");
include_once("models/estipulante.php");
include_once("dao/estipulanteDao.php");
include_once("templates/header.php");

$id_estipulante = filter_input(INPUT_GET, "id_estipulante", FILTER_VALIDATE_INT);
$estipulanteDao = new EstipulanteDAO($conn, $BASE_URL);
$estipulante = $id_estipulante ? $estipulanteDao->findById($id_estipulante) : null;

if (!$estipulante || empty($estipulante->id_estipulante)) {
    echo "<div class='container mt-4'><div class='alert alert-warning'>Estipulante não encontrado.</div></div>";
    include_once("templates/footer.php");
    exit;
}

$enderecosEstipulante = $estipulanteDao->findEnderecosByEstipulante((int)$estipulante->id_estipulante);
$telefonesEstipulante = $estipulanteDao->findTelefonesByEstipulante((int)$estipulante->id_estipulante);
$contatosEstipulante = $estipulanteDao->findContatosByEstipulante((int)$estipulante->id_estipulante);

function estipulanteShowEsc($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function estipulanteShowValue($value): string
{
    $value = trim((string)$value);
    return $value !== '' ? estipulanteShowEsc($value) : '-';
}

function estipulanteShowPhone($value): string
{
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') {
        return '-';
    }
    if (strlen($digits) === 10) {
        return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6);
    }
    if (strlen($digits) === 11) {
        return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
    }
    return estipulanteShowEsc((string)$value);
}

function estipulanteShowCnpj($value): string
{
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') {
        return '-';
    }
    if (strlen($digits) === 14) {
        return substr($digits, 0, 2) . '.' .
            substr($digits, 2, 3) . '.' .
            substr($digits, 5, 3) . '/' .
            substr($digits, 8, 4) . '-' .
            substr($digits, 12, 2);
    }
    return estipulanteShowEsc((string)$value);
}

function estipulanteShowDate($value): string
{
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y', $timestamp) : estipulanteShowEsc($value);
}

function estipulanteShowLogoUrl($logo, string $baseUrl): ?string
{
    $logo = trim((string)$logo);
    if ($logo === '') {
        return null;
    }
    if (preg_match('#^https?://#i', $logo)) {
        return $logo;
    }

    $logoPath = ltrim($logo, '/');
    $relativePath = stripos($logoPath, 'uploads/') === 0 ? $logoPath : 'uploads/' . $logoPath;
    $localPath = dirname(__DIR__, 2) . '/' . $relativePath;
    return is_file($localPath) ? $baseUrl . $relativePath : null;
}

$statusAtivo = strtolower((string)($estipulante->deletado_est ?? '')) !== 's';
$statusLabel = $statusAtivo ? 'Ativo' : 'Inativo';
$statusClass = $statusAtivo ? 'is-active' : 'is-inactive';
$logoUrl = estipulanteShowLogoUrl($estipulante->logo_est ?? '', $BASE_URL);
$endereco = trim(implode(' ', array_filter([
    trim((string)($estipulante->endereco_est ?? '')),
    trim((string)($estipulante->numero_est ?? '')) !== '' ? ', ' . trim((string)$estipulante->numero_est) : '',
])));
?>
<script src="js/timeout.js"></script>
<link rel="stylesheet" href="css/form_cad_internacao.css?v=<?= @filemtime(dirname(__DIR__, 2) . '/css/form_cad_internacao.css') ?>">

<style>
.estipulante-show-page {
    padding: 0 4px 48px;
}

.estipulante-show-page .internacao-page__hero {
    margin-bottom: 6px !important;
}

.estipulante-profile-card {
    display: grid;
    grid-template-columns: minmax(170px, 220px) minmax(0, 1fr);
    gap: 8px;
    align-items: start;
}

.estipulante-profile-summary,
.estipulante-info-card {
    background: #fff;
    border: 1px solid rgba(47, 111, 159, 0.12);
    border-radius: 8px;
    box-shadow: 0 5px 12px rgba(47, 60, 85, 0.045);
}

.estipulante-profile-summary {
    padding: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.estipulante-logo {
    width: 74px;
    height: 74px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    object-fit: contain;
    background: #eef6fb;
    border: 2px solid #eef6fb;
    box-shadow: 0 5px 12px rgba(47, 111, 159, 0.10);
}

.estipulante-logo-placeholder {
    color: #2f6f9f;
    font-size: 1.9rem;
}

.estipulante-name {
    margin: 8px 0 2px;
    color: #1f2937;
    font-size: .96rem;
    font-weight: 800;
}

.estipulante-location {
    margin: 0;
    color: #667085;
    font-size: .74rem;
}

.estipulante-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: .68rem;
    font-weight: 800;
}

.estipulante-status::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: currentColor;
}

.estipulante-status.is-active {
    background: #eaf8f0;
    color: #16834d;
}

.estipulante-status.is-inactive {
    background: #fff1f2;
    color: #be123c;
}

.estipulante-summary-meta {
    width: 100%;
    display: grid;
    gap: 5px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #edf2f7;
    text-align: left;
}

.estipulante-summary-meta span {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    color: #667085;
    font-size: .72rem;
}

.estipulante-summary-meta strong {
    color: #334155;
    font-weight: 800;
}

.estipulante-info-stack {
    display: grid;
    gap: 8px;
}

.estipulante-info-card {
    padding: 10px 12px;
}

.estipulante-info-card h3 {
    margin: 0;
    color: #24384f;
    font-size: .86rem;
    font-weight: 800;
}

.estipulante-card-subtitle {
    margin: 3px 0 0;
    color: #64748b;
    font-size: .74rem;
}

.estipulante-field-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 7px;
    margin-top: 8px;
}

.estipulante-field {
    min-height: 48px;
    padding: 7px 8px;
    border: 1px solid #e5edf4;
    border-radius: 8px;
    background: #f8fbfd;
}

.estipulante-field label {
    display: block;
    margin: 0 0 3px;
    padding: 0;
    color: #64748b;
    font-size: .6rem;
    font-weight: 800;
    letter-spacing: .025em;
    text-transform: uppercase;
}

.estipulante-field div {
    color: #1f2937;
    font-size: .8rem;
    font-weight: 600;
    word-break: break-word;
}

@media (max-width: 1200px) {
    .estipulante-field-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 980px) {
    .estipulante-profile-card,
    .estipulante-field-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main id="main-container" class="internacao-page cadastro-layout estipulante-show-page">
    <div class="internacao-page__hero">
        <div class="internacao-page__hero-main">
            <h1>Dados do estipulante</h1>
        </div>
        <div class="hero-actions">
            <a href="<?= $BASE_URL ?>estipulantes" class="hero-back-btn">Voltar para lista</a>
            <a href="<?= $BASE_URL ?>estipulantes/editar/<?= (int)$estipulante->id_estipulante ?>" class="hero-back-btn">Editar estipulante</a>
            <span class="internacao-page__tag">Registro #<?= (int)$estipulante->id_estipulante ?></span>
        </div>
    </div>

    <div class="estipulante-profile-card">
        <aside class="estipulante-profile-summary">
            <?php if ($logoUrl): ?>
                <img src="<?= estipulanteShowEsc($logoUrl) ?>" alt="Logo de <?= estipulanteShowValue($estipulante->nome_est ?? '') ?>" class="estipulante-logo">
            <?php else: ?>
                <div class="estipulante-logo estipulante-logo-placeholder" aria-hidden="true">
                    <i class="bi bi-person-vcard"></i>
                </div>
            <?php endif; ?>
            <h2 class="estipulante-name"><?= estipulanteShowValue($estipulante->nome_est ?? '') ?></h2>
            <p class="estipulante-location"><?= estipulanteShowValue(trim((string)($estipulante->cidade_est ?? '') . ' / ' . (string)($estipulante->estado_est ?? ''), ' /')) ?></p>
            <span class="estipulante-status <?= $statusClass ?>"><?= $statusLabel ?></span>

            <div class="estipulante-summary-meta">
                <span><strong>CNPJ</strong><?= estipulanteShowCnpj($estipulante->cnpj_est ?? '') ?></span>
                <span><strong>CEP</strong><?= estipulanteShowValue($estipulante->cep_est ?? '') ?></span>
                <span><strong>Cadastrado</strong><?= estipulanteShowDate($estipulante->data_create_est ?? '') ?></span>
            </div>
        </aside>

        <section class="estipulante-info-stack">
            <div class="estipulante-info-card">
                <h3>Identificação</h3>
                <p class="estipulante-card-subtitle">Dados cadastrais e responsáveis principais.</p>
                <div class="estipulante-field-grid">
                    <div class="estipulante-field">
                        <label>Estipulante</label>
                        <div><?= estipulanteShowValue($estipulante->nome_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>CNPJ</label>
                        <div><?= estipulanteShowCnpj($estipulante->cnpj_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Contato</label>
                        <div><?= estipulanteShowValue($estipulante->nome_contato_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Responsável</label>
                        <div><?= estipulanteShowValue($estipulante->nome_responsavel_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Criado por</label>
                        <div><?= estipulanteShowValue($estipulante->usuario_create_est ?? '') ?></div>
                    </div>
                </div>
            </div>

            <div class="estipulante-info-card">
                <h3>Contato</h3>
                <p class="estipulante-card-subtitle">Canais administrativos do estipulante.</p>
                <div class="estipulante-field-grid">
                    <div class="estipulante-field">
                        <label>E-mail principal</label>
                        <div><?= estipulanteShowValue($estipulante->email01_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>E-mail secundário</label>
                        <div><?= estipulanteShowValue($estipulante->email02_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Telefone principal</label>
                        <div><?= estipulanteShowPhone($estipulante->telefone01_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Telefone secundário</label>
                        <div><?= estipulanteShowPhone($estipulante->telefone02_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>E-mail contato</label>
                        <div><?= estipulanteShowValue($estipulante->email_contato_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>E-mail responsável</label>
                        <div><?= estipulanteShowValue($estipulante->email_responsavel_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Tel. contato</label>
                        <div><?= estipulanteShowPhone($estipulante->telefone_contato_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Tel. responsável</label>
                        <div><?= estipulanteShowPhone($estipulante->telefone_responsavel_est ?? '') ?></div>
                    </div>
                </div>
            </div>

            <div class="estipulante-info-card">
                <h3>Endereço</h3>
                <p class="estipulante-card-subtitle">Localização registrada para o estipulante.</p>
                <div class="estipulante-field-grid">
                    <div class="estipulante-field">
                        <label>Endereço</label>
                        <div><?= estipulanteShowValue($endereco) ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Bairro</label>
                        <div><?= estipulanteShowValue($estipulante->bairro_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Cidade</label>
                        <div><?= estipulanteShowValue($estipulante->cidade_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Estado</label>
                        <div><?= estipulanteShowValue($estipulante->estado_est ?? '') ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>CEP</label>
                        <div><?= estipulanteShowValue($estipulante->cep_est ?? '') ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($enderecosEstipulante) || !empty($telefonesEstipulante) || !empty($contatosEstipulante)): ?>
            <div class="estipulante-info-card">
                <h3>Complementares</h3>
                <p class="estipulante-card-subtitle">Registros adicionais vinculados ao cadastro.</p>
                <div class="estipulante-field-grid">
                    <div class="estipulante-field">
                        <label>Endereços</label>
                        <div><?= count($enderecosEstipulante) ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Telefones</label>
                        <div><?= count($telefonesEstipulante) ?></div>
                    </div>
                    <div class="estipulante-field">
                        <label>Contatos</label>
                        <div><?= count($contatosEstipulante) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once("templates/footer.php"); ?>
