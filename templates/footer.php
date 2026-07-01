<?php
if (defined('FULLCARE_FOOTER_RENDERED')) {
    return;
}
define('FULLCARE_FOOTER_RENDERED', true);
require_once(__DIR__ . '/../app/security/bi_access.php');

$footerVersion = '1.4';
$footerYear = date('Y');
$canSeeBiLink = function_exists('fullcare_has_bi_access') ? fullcare_has_bi_access() : false;
?>
<link href="<?= $BASE_URL ?>css/footer_simple.css?v=<?= @filemtime(__DIR__ . '/../css/footer_simple.css') ?>" rel="stylesheet">
<footer id="myFooterSimple" aria-label="Rodapé FullCare">
    <div class="footer-simple-inner">
        <div class="footer-simple-topline"></div>

        <div class="footer-simple-main">
            <a href="https://fullcare.cloud" target="_blank" rel="noopener noreferrer"
                class="footer-simple-brand" aria-label="Abrir fullcare.cloud">
                <img src="<?= $BASE_URL ?>img/full-03.png" alt="FullCare">
                <span class="footer-simple-brand-text">Gestão em Saúde</span>
            </a>

            <nav class="footer-simple-links" aria-label="Links rápidos">
                <a href="<?= $BASE_URL ?>inicio">Início</a>
                <a href="<?= $BASE_URL ?>internacoes/lista">Internações</a>
                <a href="<?= $BASE_URL ?>visitas/lista">Visitas</a>
                <?php if ($canSeeBiLink) { ?>
                    <a href="<?= $BASE_URL ?>bi/navegacao">BI</a>
                <?php } ?>
            </nav>

            <div class="footer-simple-meta">
                <span class="footer-simple-copy">© <?= (int)$footerYear ?> FullCare - Accert Consult.</span>
                <span class="footer-simple-version">Versão <?= htmlspecialchars((string)$footerVersion, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
</footer>


<script>
document.addEventListener('DOMContentLoaded', function () {
    function syncSelectPlaceholder(select) {
        var selected = select.options && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
        var selectedText = selected ? selected.textContent.replace(/\s+/g, ' ').trim().toLowerCase() : '';
        var isPlaceholderText = selectedText === 'selecione' ||
            selectedText === 'selecione...' ||
            selectedText === 'selecione o estado';
        var isPlaceholder = select.value === '' || isPlaceholderText || (selected && selected.value === '' && selected.disabled);
        select.classList.toggle('select-placeholder-empty', isPlaceholder);
    }

    document.querySelectorAll('#main-container.internacao-page select.form-control').forEach(function (select) {
        syncSelectPlaceholder(select);
        select.addEventListener('change', function () {
            syncSelectPlaceholder(select);
        });
    });
});
</script>
