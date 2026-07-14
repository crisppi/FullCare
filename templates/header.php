<?php

include_once("globals.php");
include_once("db.php");
require_once(__DIR__ . "/../app/services/AuditorActionService.php");
require_once(__DIR__ . "/../app/security/bi_access.php");
require_once(__DIR__ . "/../app/security/inteligencia_access.php");
date_default_timezone_set('America/Sao_Paulo');
header("Content-type: text/html; charset=utf-8");

// Fallback defensivo: se BASE_URL vier na raiz, mas a aplicacao estiver em subpasta
// (ex.: /FullCare), forca BASE_URL para evitar links do header indo para /index.php.
$basePathFromBaseUrl = (string)(parse_url((string)$BASE_URL, PHP_URL_PATH) ?? '/');
$basePathFromBaseUrl = '/' . trim($basePathFromBaseUrl, '/') . '/';
if ($basePathFromBaseUrl === '//') {
    $basePathFromBaseUrl = '/';
}
$requestUriPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '');
if ($basePathFromBaseUrl === '/' && preg_match('#^/(FullCare|FullConex(?:Aud)?)(/|$)#i', $requestUriPath, $mBaseApp)) {
    $isHttpsHeader = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    $schemeHeader = $isHttpsHeader ? 'https' : 'http';
    $hostHeader = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $BASE_URL = $schemeHeader . '://' . $hostHeader . '/' . trim((string)$mBaseApp[1], '/') . '/';
}

$currentScriptName = strtolower((string)basename((string)($_SERVER['SCRIPT_NAME'] ?? '')));
$isInternacaoShowPage =
    defined('FULLCARE_INTERNACAO_SHOW_PAGE')
    || $currentScriptName === 'show_internacao.php'
    || preg_match('#/internacoes/visualizar/\\d+/?#i', $requestUriPath) === 1;
$isBiRequestPath = preg_match('#/bi(/|$)#i', $requestUriPath) === 1;
$isOperationalIntelligencePage =
    preg_match('#/inteligencia(/|$)#i', $requestUriPath) === 1
    || (!$isBiRequestPath && in_array($currentScriptName, [
        'dashboard_operacional.php',
        'dashboard_performance.php',
        'faturamento_previsao.php',
        'dashboard_mensal.php',
        'inteligencia_operadora.php',
        'relatorio_tmp.php',
        'relatorio_prorrogacao_vs_alta.php',
        'relatorio_motivos_prorrogacao.php',
        'relatorio_backlog_autorizacoes.php',
        'operational_intelligence.php',
        'permanencia_alertas.php',
        'explicabilidade_insights.php',
        'risco_glosa.php',
        'clusterizacao_clinica.php',
        'text_automation.php',
        'inteligenciainternacoes.php',
        'inteligenciagraficos.php',
        'inteligencia_logs_usuario.php',
    ], true));

// Caminho default da foto do usuario
$defaultFoto = $BASE_URL . 'uploads/usuarios/default-user.jpeg';

// error_reporting(E_ALL);

$sessionNivel = isset($_SESSION['nivel']) ? (int) $_SESSION['nivel'] : 0;
$sessionUsuario = $_SESSION['usuario_user'] ?? '';
$sessionUsuarioPrimeiroNome = trim((string)$sessionUsuario);
if ($sessionUsuarioPrimeiroNome !== '') {
    $sessionUsuarioPrimeiroNome = preg_split('/\s+/u', $sessionUsuarioPrimeiroNome)[0] ?? $sessionUsuarioPrimeiroNome;
}
$sessionIdUsuario = $_SESSION['id_usuario'] ?? null;
$normAccess = function ($txt) {
    $txt = mb_strtolower(trim((string)$txt), 'UTF-8');
    $c = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
    $txt = $c !== false ? $c : $txt;
    return preg_replace('/[^a-z]/', '', $txt);
};
$startsWithAnyAccess = function (string $value, array $prefixes): bool {
    foreach ($prefixes as $prefix) {
        if ($prefix !== '' && strpos($value, $prefix) === 0) {
            return true;
        }
    }
    return false;
};
$normCargoAccess = $normAccess($_SESSION['cargo'] ?? '');
$isBiHubOnly = function_exists('fullcare_is_gestor_seguradora')
    ? fullcare_is_gestor_seguradora()
    : (strpos($normCargoAccess, 'gestorseguradora') === 0);
$isSeguradoraRole = (strpos($normCargoAccess, 'seguradora') !== false);
$canSeeFullMenu = ($sessionNivel > 0) && !$isBiHubOnly;
$canSeeBiMenu = function_exists('fullcare_has_bi_access') ? fullcare_has_bi_access() : false;
$canSeeInteligenciaMenu = false;
$canSeeHubMenu = $isBiHubOnly;
$canSeeInternadosMenu = $isBiHubOnly;
$canSeeGestorListas = $isBiHubOnly;
$normNivelAccess = $normAccess($_SESSION['nivel'] ?? '');
$isDiretoria = in_array($normCargoAccess, ['diretoria', 'diretor', 'administrador', 'admin', 'board'], true)
    || (strpos($normCargoAccess, 'diretor') !== false)
    || (strpos($normCargoAccess, 'diretoria') !== false)
    || in_array($normNivelAccess, ['diretoria', 'diretor', 'administrador', 'admin', 'board'], true)
    || ($sessionNivel === -1);
$canSeeInteligenciaMenu = $isDiretoria;
$canSeeUsuariosCadastro = $isDiretoria && in_array($sessionNivel, [5, -1], true);
$canSeeFullMenu = (($sessionNivel > 0) || $isDiretoria) && !$isBiHubOnly;
$hasHeaderMenuAccess = ($sessionNivel > 0) || $isDiretoria;
$cadastroScripts = [
    'list_paciente.php',
    'cad_paciente.php',
    'edit_paciente.php',
    'show_paciente.php',
    'list_hospital.php',
    'cad_hospital.php',
    'edit_hospital.php',
    'show_hospital.php',
    'list_seguradora.php',
    'cad_seguradora.php',
    'edit_seguradora.php',
    'show_seguradora.php',
    'list_estipulante.php',
    'cad_estipulante.php',
    'edit_estipulante.php',
    'show_estipulante.php',
    'list_usuario.php',
    'cad_usuario.php',
    'edit_usuario.php',
    'show_usuario.php',
    'list_hospitaluser.php',
    'cad_hospitaluser.php',
    'edit_hospitaluser.php',
    'list_acomodacao.php',
    'cad_acomodacao.php',
    'edit_acomodacao.php',
    'show_acomodacao.php',
    'list_patologia.php',
    'cad_patologia.php',
    'edit_patologia.php',
    'show_patologia.php',
    'list_antecedente.php',
    'cad_antecedente.php',
    'edit_antecedente.php',
    'show_antecedente.php',
];
$isCadastroRequestPath = preg_match('#/(pacientes|hospitais|seguradoras|estipulantes|usuarios)(/|$)#i', $requestUriPath) === 1
    || in_array($currentScriptName, $cadastroScripts, true)
    || preg_match('/^(form_)?(list|cad|edit|show)_(paciente|hospital|hospitaluser|seguradora|estipulante|usuario|acomodacao|patologia|antecedente)\.php$/i', $currentScriptName) === 1;
$canSeeCadastrosMenu = $canSeeFullMenu && (
    $sessionNivel > 3
    || $canSeeUsuariosCadastro
    || $isCadastroRequestPath
);
$isPerfilMedicoMenu = $startsWithAnyAccess($normCargoAccess, ['medico', 'med']);
$isAuditorHeaderSearch = class_exists('AuditorActionService')
    ? AuditorActionService::canUseOperationalSearch($_SESSION)
    : false;
$seguradoraHeaderLogoUrl = null;
$seguradoraHeaderNome = null;
$resolveSeguradoraLogoUrl = static function (string $logoSeg, int $seguradoraId, string $seguradoraNome) use ($BASE_URL): ?string {
    $logoSeg = trim($logoSeg);
    if ($logoSeg !== '') {
        if (preg_match('#^https?://#i', $logoSeg)) {
            return $logoSeg;
        }

        $logoPath = ltrim($logoSeg, '/');
        $localCandidates = [];
        $urlCandidates = [];

        if (stripos($logoPath, 'img/') === 0 || stripos($logoPath, 'uploads/') === 0) {
            $localCandidates[] = __DIR__ . '/../' . $logoPath;
            $urlCandidates[] = $BASE_URL . $logoPath;
        } else {
            $localCandidates[] = __DIR__ . '/../img/' . $logoPath;
            $urlCandidates[] = $BASE_URL . 'img/' . $logoPath;
            $localCandidates[] = __DIR__ . '/../uploads/' . $logoPath;
            $urlCandidates[] = $BASE_URL . 'uploads/' . $logoPath;
        }

        foreach ($localCandidates as $idx => $localFile) {
            if (is_file($localFile)) {
                return $urlCandidates[$idx] ?? null;
            }
        }
    }

    $nomeNorm = mb_strtolower(trim($seguradoraNome), 'UTF-8');
    $nomeAscii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomeNorm);
    $nomeNorm = $nomeAscii !== false ? $nomeAscii : $nomeNorm;
    $nomeNorm = preg_replace('/[^a-z0-9]+/', '_', $nomeNorm);
    $nomeNorm = trim((string)$nomeNorm, '_');

    $baseNames = array_filter([
        $seguradoraId > 0 ? 'seguradora_' . $seguradoraId : null,
        $seguradoraId > 0 ? 'logo_seguradora_' . $seguradoraId : null,
        $nomeNorm !== '' ? $nomeNorm : null,
        $nomeNorm !== '' ? 'logo_' . $nomeNorm : null,
    ]);
    $exts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

    foreach ($baseNames as $baseName) {
        foreach ($exts as $ext) {
            $candidate = $baseName . '.' . $ext;
            $imgFile = __DIR__ . '/../img/' . $candidate;
            if (is_file($imgFile)) {
                return $BASE_URL . 'img/' . $candidate;
            }
            $uploadFile = __DIR__ . '/../uploads/' . $candidate;
            if (is_file($uploadFile)) {
                return $BASE_URL . 'uploads/' . $candidate;
            }
        }
    }

    return null;
};
if ($isSeguradoraRole || !empty($_SESSION['fk_seguradora_user'])) {
    $seguradoraId = (int)($_SESSION['fk_seguradora_user'] ?? 0);
    if ($seguradoraId <= 0 && !empty($sessionIdUsuario)) {
        try {
            $stmtUserSeg = $conn->prepare("SELECT fk_seguradora_user FROM tb_user WHERE id_usuario = :id LIMIT 1");
            $stmtUserSeg->bindValue(':id', (int)$sessionIdUsuario, PDO::PARAM_INT);
            $stmtUserSeg->execute();
            $seguradoraId = (int)($stmtUserSeg->fetchColumn() ?: 0);
            if ($seguradoraId > 0) {
                $_SESSION['fk_seguradora_user'] = $seguradoraId;
            }
        } catch (Throwable $e) {
            $seguradoraId = 0;
        }
    }

    if ($seguradoraId > 0) {
        try {
            $stmtSeg = $conn->prepare("SELECT seguradora_seg, logo_seg FROM tb_seguradora WHERE id_seguradora = :id LIMIT 1");
            $stmtSeg->bindValue(':id', $seguradoraId, PDO::PARAM_INT);
            $stmtSeg->execute();
            $seguradoraHeader = $stmtSeg->fetch(PDO::FETCH_ASSOC) ?: null;

            if (is_array($seguradoraHeader)) {
                $logoSeg = trim((string)($seguradoraHeader['logo_seg'] ?? ''));
                $seguradoraHeaderNome = trim((string)($seguradoraHeader['seguradora_seg'] ?? ''));
                $seguradoraHeaderLogoUrl = $resolveSeguradoraLogoUrl($logoSeg, $seguradoraId, $seguradoraHeaderNome);
            }
        } catch (Throwable $e) {
            $seguradoraHeaderLogoUrl = null;
            $seguradoraHeaderNome = null;
        }
    }
}

$chatUnreadCount = 0;
$chatAssistantLink = $BASE_URL . 'show_chat.php';
if (!empty($sessionIdUsuario)) {
    try {
        $stmtChat = $conn->prepare("SELECT COUNT(*) FROM tb_mensagem WHERE para_usuario = :para AND vista = 0");
        $stmtChat->bindValue(':para', (int) $sessionIdUsuario, PDO::PARAM_INT);
        $stmtChat->execute();
        $chatUnreadCount = (int) $stmtChat->fetchColumn();
    } catch (Exception $e) {
        $chatUnreadCount = 0;
    }

}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FullCare</title>
    <base href="<?= $BASE_URL ?>">
    <link rel="icon" type="image/png" href="<?= $BASE_URL ?>assets/fullcare-icon.png?v=<?= @filemtime(__DIR__ . '/../assets/fullcare-icon.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= $BASE_URL ?>assets/fullcare-icon.png?v=<?= @filemtime(__DIR__ . '/../assets/fullcare-icon.png') ?>">
    <link rel="apple-touch-icon" href="<?= $BASE_URL ?>assets/fullcare-icon.png?v=<?= @filemtime(__DIR__ . '/../assets/fullcare-icon.png') ?>">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">

    <link rel="stylesheet" href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/font-awesome-5/css/fontawesome-all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/mdi-font/css/material-design-iconic-font.min.css"
        rel="stylesheet" media="all">
    <link href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/animsition/animsition.min.css" rel="stylesheet"
        media="all">
    <link
        href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css"
        rel="stylesheet" media="all">
    <link href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/wow/animate.css" rel="stylesheet" media="all">
    <link href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/css-hamburgers/hamburgers.min.css" rel="stylesheet"
        media="all">
    <link href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/slick/slick.css" rel="stylesheet" media="all">
    <link href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="<?= $BASE_URL ?>diversos/CoolAdmin-master/vendor/perfect-scrollbar/perfect-scrollbar.css"
        rel="stylesheet" media="all">
    <link href="<?= $BASE_URL ?>diversos/CoolAdmin-master/css/theme.css" rel="stylesheet" media="all">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/css/bootstrap-select.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= $BASE_URL ?>css/style.css?v=<?= @filemtime(__DIR__ . '/../css/style.css') ?>" rel="stylesheet">
    <link href="<?= $BASE_URL ?>css/legendas.css?v=<?= @filemtime(__DIR__ . '/../css/legendas.css') ?>" rel="stylesheet">
    <link href="<?= $BASE_URL ?>css/styleMenu.css?v=<?= @filemtime(__DIR__ . '/../css/styleMenu.css') ?>" rel="stylesheet">
    <link href="<?= $BASE_URL ?>css/module_headers.css?v=<?= @filemtime(__DIR__ . '/../css/module_headers.css') ?>" rel="stylesheet">
    <link href="<?= $BASE_URL ?>css/table_style.css?v=<?= @filemtime(__DIR__ . '/../css/table_style.css') ?>" rel="stylesheet">
    <link href="<?= $BASE_URL ?>css/listagem_padrao.css?v=<?= @filemtime(__DIR__ . '/../css/listagem_padrao.css') ?>" rel="stylesheet">
    <?php if ($isInternacaoShowPage): ?>
    <link href="<?= $BASE_URL ?>css/style_show_internacao.css?v=<?= @filemtime(__DIR__ . '/../css/style_show_internacao.css') ?>" rel="stylesheet">
    <?php endif; ?>
    <link href="<?= $BASE_URL ?>css/feedback.css?v=<?= @filemtime(__DIR__ . '/../css/feedback.css') ?>" rel="stylesheet">
    <script defer src="<?= $BASE_URL ?>js/lista_header_sort.js"></script>
    <script defer src="<?= $BASE_URL ?>js/listagem_enhancer.js?v=<?= @filemtime(__DIR__ . '/../js/listagem_enhancer.js') ?: time() ?>"></script>
    <script defer src="<?= $BASE_URL ?>js/feedback.js?v=<?= @filemtime(__DIR__ . '/../js/feedback.js') ?: time() ?>"></script>
    <script defer src="<?= $BASE_URL ?>js/friendly_urls.js?v=<?= @filemtime(__DIR__ . '/../js/friendly_urls.js') ?: time() ?>"></script>

    <link href="<?= $BASE_URL ?>css/header_layout.css?v=<?= @filemtime(__DIR__ . '/../css/header_layout.css') ?>" rel="stylesheet">
    <?php if ($isOperationalIntelligencePage): ?>
        <link href="<?= $BASE_URL ?>css/operational_intelligence_pages.css?v=<?= @filemtime(__DIR__ . '/../css/operational_intelligence_pages.css') ?>" rel="stylesheet">
    <?php endif; ?>

</head>

<body>
    <div class="col-md-12 fc-inline-1">
        <nav class="navbar navbar-expand-lg navbar-light bg-light nav_bar_custom fixed-top">
            <div class="bar_color fc-inline-2">
            </div>
            <div class="container-fluid">
                <a class="navbar-brand fc-header-brand-link" href="<?= $BASE_URL ?>dashboard">
                    <img src="<?= $BASE_URL ?>img/LogoFullCare.png" class="logo-novo" width="224" height="56"
                        alt="FullCare">
                    <?php if (!empty($seguradoraHeaderLogoUrl)): ?>
                    <span class="brand-divider" aria-hidden="true"></span>
                    <img src="<?= htmlspecialchars($seguradoraHeaderLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="logo-seguradora"
                        alt="<?= htmlspecialchars($seguradoraHeaderNome ?: 'Seguradora', ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll"
                    aria-controls="navbarScroll" aria-expanded="false" aria-label="Alternar navegação">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarScroll">
                    <ul class="nav-tabs navbar-nav me-auto my-2 my-lg-0 navbar-nav-scroll align-items-center fc-inline-3"
                       >
                        <!-- Ícone de mensagem -->

                        <?php if ($hasHeaderMenuAccess) { ?>

                            <?php if ($canSeeFullMenu) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle " href="#" id="navbarMenuDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-stack edit-icon" name="type" value="edite"
                                           ></i>
                                        Menu
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarMenuDropdown">
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>central-trabalho"><i
                                                    class="bi bi-speedometer2 fc-inline-5"
                                                   ></i>
                                                Central de trabalho</a></li>
                                        <?php if ($isDiretoria) { ?>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>dashboard-operacional"><i
                                                        class="bi bi-activity fc-inline-6"
                                                       ></i>
                                                    Dashboard operacional</a></li>
                                        <?php } ?>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>manual.html"><i class="bi bi-person fc-inline-5"
                                                   ></i>
                                                Manual</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>solicitacoes/customizacao">
                                                <i class="bi bi-file-earmark-text fc-inline-4"
                                                   ></i>
                                                Solicitação de Customização
                                            </a></li>
                                        <?php if ($isDiretoria) { ?>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>solicitacoes/customizacao/lista">
                                                    <i class="bi bi-clipboard-check fc-inline-7"
                                                       ></i>
                                                    Solicitações (Lista)
                                                </a></li>
                                        <?php } ?>
                                        <?php if ($isDiretoria) { ?>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/performance-equipes"><i
                                                        class="bi bi-trophy fc-inline-4"
                                                       ></i>
                                                    Performance equipes</a></li>
                                        <?php } ?>
                                        <?php if ($sessionNivel > 3) { ?>
                                            <li class="nav-item">
                                                <a class="dropdown-item" href="<?= $BASE_URL ?>administracao/permissoes">
                                                    <i class="bi bi-shield-lock fc-inline-8"
                                                       ></i>
                                                    Permissões
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php } ?>

                            <?php if ($canSeeCadastrosMenu) { ?>

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle " href="#" id="navbarCadastrosDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-pencil-square edit-icon" name="type" value="edite"
                                           ></i>
                                        Cadastros
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarCadastrosDropdown">
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>pacientes"><i class="bi bi-person fc-inline-5"
                                                   ></i>
                                                Pacientes</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>hospitais"><span
                                                    class="bi bi-hospital fc-inline-9"
                                                   ></span>
                                                Hospitais</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>seguradoras"><span
                                                    class=" bi bi-heart-pulse fc-inline-10"
                                                   ></span>
                                                Seguradoras</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>estipulantes"><i
                                                    class="bi bi-building fc-inline-11"
                                                   ></i>
                                                Estipulantes</a></li>
                                        <?php if ($canSeeUsuariosCadastro) { ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>usuarios"><i
                                                    class="bi bi-people-fill fc-inline-12"
                                                   ></i>
                                                Usuários</a></li>
                                        <?php } ?>
                                        <!-- <li><a class="dropdown-item" href="<?php $BASE_URL ?>list_patologia.php"><span
                                            class=" bi bi-virus fc-inline-13"
                                           ></span>
                                        Patologia</a></li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>list_antecedente.php"><i
                                            class="bi bi-people fc-inline-10"
                                           ></i>
                                        Antecedente</a></li> -->
                                    </ul>
                                </li>
                            <?php }; ?>
                            <?php if ($canSeeFullMenu && $sessionNivel >= 3) { ?>

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarProducaoDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-calendar3 edit-icon" name="type" value="edite"
                                           ></i>
                                        Produção
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarProducaoDropdown">

                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>internacoes/nova"><i
                                                    class="bi bi-calendar2-date fc-inline-5"
                                                   ></i> Nova
                                                Internação</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>censo/lista"><i class="bi bi-book fc-inline-14"
                                                   ></i>
                                                Censo</a></li>
                                        <li><a class="dropdown-item producao-ai-featured" href="<?= $BASE_URL ?>producao/ia-clinica"><i
                                                    class="bi bi-clipboard2-pulse fc-inline-15"
                                                   ></i> IA Cl&iacute;nica</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>

                                        <!-- <li><a class="dropdown-item" href="<?php $BASE_URL ?>list_internacao_uti_alta.php"><span
                                            id="boot-icon3" class="bi bi-box-arrow-left fc-inline-16"
                                           ></span>
                                        Alta UTI</a></li> -->
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>internacoes/reverter-alta"><span
                                                    id="boot-icon3" class="bi bi-postcard-heart fc-inline-17"
                                                   ></span>
                                                Reverter altas</a>
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>internacoes/gerar-alta"><span
                                                    class="bi bi-clipboard-check fc-inline-18"
                                                   ></span>
                                                Gerar altas</a>
                                        </li>
                                    </ul>
                                </li>
                            <?php }; ?>
                            <?php if ($canSeeFullMenu && $sessionNivel >= 3): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="dropdownContasRah" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-journal-richtext me-1 fc-inline-19"></i>Contas
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownContasRah">
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>contas/auditar">
                                                <i class="bi bi-currency-dollar text-success me-2"></i>Contas para Auditar
                                            </a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>contas/finalizadas">
                                                <i class="bi bi-shield-check text-primary me-2"></i>Contas Finalizadas
                                            </a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>contas/senhas-finalizadas">
                                                <i class="bi bi-bookmark-check text-danger me-2"></i>Senhas Finalizadas
                                            </a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>contas/paradas">
                                                <i class="bi bi-pause-circle text-warning me-2"></i>Contas Paradas
                                            </a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>contas/jornada">
                                                <i class="bi bi-diagram-3 text-info me-2"></i>Jornada da Conta
                                            </a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <?php if ($canSeeFullMenu && $sessionNivel >= 3) { ?>

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle " href="#" id="navbarListasDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-list-ul edit-icon" name="type" value="edite"
                                           ></i>
                                        Listas
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarListasDropdown">

                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>internacoes/lista"> <i
                                                    class="bi bi-calendar2-date fc-inline-5"
                                                   ></i>
                                                Internação</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>internacoes/uti"> <i
                                                    class="bi bi-clipboard-heart fc-inline-20"
                                                   ></i>
                                                Internação UTI</a>
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>listas/altas"><i
                                                    class="bi bi-clipboard-check fc-inline-21"
                                                   ></i>
                                                Lista de altas</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>censo/lista"><i class="bi bi-book fc-inline-14"
                                                   ></i>
                                                Censo</a></li>
                                    </ul>
                                </li>
                            <?php }; ?>
                            <?php if ($canSeeFullMenu && $sessionNivel >= 3) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle " href="#" id="navbarGestaoDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-receipt edit-icon" name="type" value="edite"
                                           ></i>
                                        Gestão
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarGestaoDropdown">
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>gestao"><i
                                                    class="bi bi-postcard-heart fc-inline-22"
                                                   ></i>
                                                Gestão Assistencial</a></li>
                                        <?php if ($isDiretoria) { ?>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/logs-usuarios"><i
                                                        class="bi bi-journal-code fc-inline-23"
                                                       ></i>
                                                    Logs por Usuário</a></li>
                                        <?php } ?>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>internacoes/ciclo"><i
                                                    class="bi bi-postcard-heart fc-inline-20"
                                                   ></i>
                                                Rota do Paciente</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>internacoes/sem-senha"><i
                                                    class="bi bi-shield-exclamation fc-inline-24"
                                                   ></i>
                                                Internações sem senha</a></li>
                                        <?php if (!$isPerfilMedicoMenu) { ?>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>gestao/pendencias-operacionais"><i
                                                        class="bi bi-exclamation-diamond fc-inline-25"
                                                       ></i>
                                                    Pendências operacionais</a></li>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>negociacoes"><i
                                                        class="bi bi-currency-dollar fc-inline-23"
                                                       ></i>
                                                    Negociações</a></li>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>negociacoes/graficos"><i
                                                        class="bi bi-bar-chart fc-inline-26"
                                                       ></i>
                                                    Gráfico Negociações</a></li>
                                        <?php } ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>gestao/fila-tarefas"><i
                                                    class="bi bi-list-check fc-inline-27"
                                                   ></i>
                                                Fila de Tarefas</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>gestao/prorrogacoes-pendentes"><i
                                                    class="bi bi-hourglass-split fc-inline-28"
                                                   ></i>
                                                Prorrogação Pendente</a></li>
                                        <?php if (!$isPerfilMedicoMenu) { ?>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>visitas/lista"><i
                                                        class="bi bi-list-check fc-inline-4"
                                                       ></i>
                                                    Lista Visitas</a></li>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>visitas/faturamento"><i
                                                        class="bi bi-clipboard-check fc-inline-29"
                                                       ></i>
                                                    Faturamento Mensal</a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php }; ?>
                            <?php if ($canSeeFullMenu && $sessionNivel >= 3) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarCuidadoContinuado" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-heart-pulse"></i>
                                        Cuidado Continuado
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarCuidadoContinuado">
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>cuidado-continuado"><i
                                                    class="bi bi-grid-1x2 fc-inline-23"
                                                   ></i>
                                                Dashboard</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>cuidado-continuado/cronicos"><i
                                                    class="bi bi-heart-pulse-fill fc-inline-30"
                                                   ></i>
                                                Gestão de Crônicos</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>cuidado-continuado/medicina-preventiva"><i
                                                    class="bi bi-shield-check fc-inline-31"
                                                   ></i>
                                                Medicina Preventiva</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>cuidado-continuado/longa-permanencia"><i
                                                    class="bi bi-hourglass-split fc-inline-4"
                                                   ></i>
                                                Longa Permanência</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>cuidado-continuado/home-care"><i
                                                    class="bi bi-house-heart fc-inline-32"
                                                   ></i>
                                                Home Care</a></li>
                                    </ul>
                                </li>
                            <?php }; ?>
                            <?php if ($canSeeBiMenu) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle " href="#" id="navbarBiDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-bar-chart-line edit-icon" name="type" value="edite"
                                           ></i>
                                        BI
                                    </a>
                                    <ul class="dropdown-menu bi-dropdown" aria-labelledby="navbarBiDropdown">
                                        <li><a class="dropdown-item bi-dropdown-featured" href="<?= $BASE_URL ?>bi/navegacao"><i
                                                    class="bi bi-grid-3x3-gap fc-inline-33"
                                                   ></i>
                                                Navegação BI</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item bi-dropdown-featured" href="<?= $BASE_URL ?>bi/resultados"><i
                                                    class="bi bi-graph-up-arrow fc-inline-34"
                                                   ></i>
                                                BI Resultados</a></li>
                                        <li><a class="dropdown-item bi-dropdown-featured" href="<?= $BASE_URL ?>bi/produtividade"><i
                                                    class="bi bi-clipboard2-check fc-inline-33"
                                                   ></i>
                                                BI Produtividade</a></li>
                                        <li><a class="dropdown-item bi-dropdown-featured" href="<?= $BASE_URL ?>bi/qualidade-360"><i
                                                    class="bi bi-award fc-inline-35"
                                                   ></i>
                                                BI Qualidade</a></li>
                                        <li><a class="dropdown-item bi-dropdown-featured" href="<?= $BASE_URL ?>bi/preditivo"><i
                                                    class="bi bi-bullseye fc-inline-36"
                                                   ></i>
                                                BI Preditivo</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/indicadores"><i
                                                    class="bi bi-speedometer2 fc-inline-33"
                                                   ></i>
                                                Resumo</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/indicadores-essenciais"><i
                                                    class="bi bi-bar-chart-steps fc-inline-34"
                                                   ></i>
                                                Indicadores Essenciais</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/uti"><i
                                                    class="bi bi-heart-pulse fc-inline-37"
                                                   ></i>
                                                Clínico</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/seguradora"><i
                                                    class="bi bi-shield-check fc-inline-38"
                                                   ></i>
                                                Operacional</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/sinistro"><i
                                                    class="bi bi-clipboard-data fc-inline-39"
                                                   ></i>
                                                Financeiro</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/gastos-patologia"><i
                                                    class="bi bi-activity fc-inline-40"
                                                   ></i>
                                                Controle de Gastos</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/anomalias-permanencia"><i
                                                    class="bi bi-exclamation-triangle fc-inline-41"
                                                   ></i>
                                                Anomalias &amp; Fraude</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/auditoria-documentacao"><i
                                                    class="bi bi-clipboard-check fc-inline-42"
                                                   ></i>
                                                Conformidade &amp; Auditoria</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/risco-cronicos"><i
                                                    class="bi bi-person-exclamation fc-inline-36"
                                                   ></i>
                                                Segmentação de Risco</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/risco-prevencao-matriz"><i
                                                    class="bi bi-shield-exclamation fc-inline-41"
                                                   ></i>
                                                Risco &amp; Prevenção</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/rede-volume-custo"><i
                                                    class="bi bi-bar-chart-line fc-inline-43"
                                                   ></i>
                                                Negociação &amp; Rede</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>bi/qualidade-desfecho"><i
                                                    class="bi bi-exclamation-octagon fc-inline-35"
                                                   ></i>
                                                Qualidade &amp; Desfecho</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>cuidado-continuado/longa-permanencia"><i
                                                    class="bi bi-hourglass-split fc-inline-44"
                                                   ></i>
                                                Gestão Longa Permanência</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>cuidado-continuado/home-care"><i
                                                    class="bi bi-house-heart fc-inline-45"
                                                   ></i>
                                                Gestão Home Care</a></li>
                                    </ul>
                                </li>
                            <?php }; ?>
                            <?php if ($canSeeInteligenciaMenu) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle " href="#" id="navbarInteligenciaDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-robot edit-icon" name="type" value="edite"
                                           ></i>
                                        Inteligência Operacional
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarInteligenciaDropdown">
                                        <li><a class="dropdown-item inteligencia-chat-featured" href="<?= $BASE_URL ?>inteligencia/assistente-internacoes"><i
                                                    class="bi bi-chat-dots fc-inline-46"
                                                   ></i>
                                                IA de Internações</a></li>
                                        <li><a class="dropdown-item inteligencia-chat-featured" href="<?= $BASE_URL ?>inteligencia/ia-graficos"><i
                                                    class="bi bi-bar-chart-line fc-inline-47"
                                                   ></i>
                                                IA Gráficos</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/dashboard-360"><i
                                                    class="bi bi-grid-3x3-gap fc-inline-4"
                                                   ></i>
                                                Dashboard 360°</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/previsao-faturamento"><i
                                                    class="bi bi-graph-up-arrow fc-inline-48"
                                                   ></i>
                                                Previsão faturamento</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/painel-mensal"><i
                                                    class="bi bi-graph-up-arrow fc-inline-48"
                                                   ></i>
                                                Painel Mensal</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/inteligencia-operadora"><i
                                                    class="bi bi-shield-check fc-inline-32"
                                                   ></i>
                                                Inteligência da Operadora</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/tmp"><i
                                                    class="bi bi-activity fc-inline-32"
                                                   ></i>
                                                TMP por CID/Procedimento/Convênio</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/prorrogacao-vs-alta"><i
                                                    class="bi bi-hourglass-split fc-inline-49"
                                                   ></i>
                                                Prorrogação vs Alta no prazo</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/motivos-prorrogacao"><i
                                                    class="bi bi-list-check fc-inline-50"
                                                   ></i>
                                                Motivos de Prorrogação</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/backlog-autorizacoes"><i
                                                    class="bi bi-card-checklist fc-inline-51"
                                                   ></i>
                                                Backlog de Autorizações</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/previsoes-operacionais"><i
                                                    class="bi bi-robot fc-inline-26"
                                                   ></i>
                                                Previsões Operacionais</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/permanencias-alertas"><i
                                                    class="bi bi-clock-history fc-inline-52"
                                                   ></i>
                                                Permanências e alertas</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/insights-explicaveis"><i
                                                    class="bi bi-lightbulb fc-inline-53"
                                                   ></i>
                                                Insights explicáveis</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/oportunidade-glosa"><i
                                                    class="bi bi-exclamation-octagon fc-inline-51"
                                                   ></i>
                                                Oportunidade de glosa / contas</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/clusterizacao-clinica"><i
                                                    class="bi bi-diagram-3 fc-inline-32"
                                                   ></i>
                                                Clusterização clínica</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/assistente-textos"><i
                                                    class="bi bi-pencil-square fc-inline-54"
                                                   ></i>
                                                Assistente de Textos</a></li>
                                        <?php if ($isDiretoria) { ?>
                                            <li><a class="dropdown-item" href="<?= $BASE_URL ?>inteligencia/logs-usuarios"><i
                                                        class="bi bi-journal-code fc-inline-23"
                                                       ></i>
                                                    Logs por Usuário</a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php } ?>
                            <?php if ($canSeeHubMenu) { ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?= $BASE_URL ?>pacientes">
                                        <i class="fc-inline-4 bi bi-person-badge edit-icon"></i>
                                        HUB de Pacientes
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if ($canSeeGestorListas) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarGestorListas" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fc-inline-4 bi bi-list edit-icon"></i>
                                        Lista
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarGestorListas">
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>central-trabalho">
                                                <i class="bi bi-grid-1x2-fill fc-inline-4"
                                                   ></i>
                                                Central de trabalho</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>internacoes/lista">
                                                <i class="bi bi-clipboard-data fc-inline-4"
                                                   ></i>
                                                Internacao</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>gestao">
                                                <i class="bi bi-postcard-heart fc-inline-4"
                                                   ></i>
                                                Gestao Assistencial</a></li>
                                        <li><a class="dropdown-item" href="<?= $BASE_URL ?>listas/altas">
                                                <i class="bi bi-clipboard-check fc-inline-4"
                                                   ></i>
                                                Altas</a></li>
                                    </ul>
                                </li>
                            <?php } ?>
                        <?php } ?>
                            <!-- <?php if ($_SESSION['nivel'] >= 2) { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle " href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fc-inline-4 fa-solid fa-pills edit-icon" name="type" value="edite"
                                   ></i>
                                DRG
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                <li><a class="dropdown-item"
                                        href="<?php $BASE_URL ?>list_internacao_patologia.php"><span id="boot-icon1"
                                            class="bi bi-capsule-pill fc-inline-55"
                                           > </span>
                                        Pesquisa internações
                                    </a></li>
                                <li>
                            </ul>
                        </li>
                        <?php }; ?> -->
                            <!-- <?php if ($_SESSION['nivel'] > 3) { ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle " href="#" id="navbarScrollingDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fc-inline-4 fa-solid fa-print edit-icon" name="type" value="edite"
                                   ></i>
                                Relatórios
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarScrollingDropdown">
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>relatorios.php"><span
                                            id="boot-icon1" class="bi bi-clipboard-data fc-inline-55"
                                           >
                                        </span> Relatórios </a></li>
                                <li>
                                <li><a class="dropdown-item"
                                        href="https://app.powerbi.com/reportEmbed?reportId=162595d1-241c-45dc-b282-e5134dc77636&autoAuth=true&ctid=5d8203ef-bc77-4057-86a0-56d58ebd6258">
                                        <span id="boot-icon1" class="bi bi-clipboard-data fc-inline-55"
                                           >
                                        </span> Relatórios - APP</a></li>
                                <li>
                                <li><a class="dropdown-item" href="<?php $BASE_URL ?>relatorios_capeante.php"><span
                                            id="boot-icon1" class="bi bi-clipboard-data fc-inline-55"
                                           >
                                        </span> Relatórios Capeantes</a></li>
                                <li>
                            </ul>
                        </li>

                        <?php }; ?>
                    </ul> -->
                </div>

            <div class="d-flex align-items-center gap-2 ms-auto header-actions pe-3">
                <a href="<?= htmlspecialchars($chatAssistantLink) ?>"
                    id="header-chat-launcher"
                    class="btn btn-outline-secondary position-relative header-chat-launcher header-action-btn<?= $chatUnreadCount > 0 ? ' has-unread' : '' ?>"
                    title="<?= $chatUnreadCount > 0 ? htmlspecialchars($chatUnreadCount . ' mensagem(ns) não lida(s)') : 'Mensagens entre usuários' ?>"
                    aria-label="<?= $chatUnreadCount > 0 ? htmlspecialchars('Mensagens: ' . $chatUnreadCount . ' não lida(s)') : 'Mensagens entre usuários' ?>"
                    data-unread-count="<?= (int)$chatUnreadCount ?>">
                    <i class="bi bi-chat-dots"></i>
                    <span class="d-none d-xl-inline ms-1">Mensagens</span>
                    <span
                        id="header-chat-unread-badge"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger chat-unread-badge"
                        <?= $chatUnreadCount > 0 ? '' : 'hidden' ?>>
                        <?= $chatUnreadCount > 99 ? '99+' : (int)$chatUnreadCount ?>
                    </span>
                </a>
                <form class="d-flex position-relative" id="global-patient-search" autocomplete="off">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <?php if ($isAuditorHeaderSearch): ?>
                            <select class="form-select global-search-type" id="global-search-type" aria-label="Tipo de pesquisa">
                                <option value="paciente" selected>Paciente</option>
                                <option value="internacao">Internação</option>
                                <option value="conta">Conta</option>
                            </select>
                        <?php endif; ?>
                        <input type="text" class="form-control" id="inp-search-paciente"
                            placeholder="<?= $isAuditorHeaderSearch ? 'Nome ou matrícula' : 'Pesquisar por senha, matrícula ou nome' ?>"
                            aria-label="<?= $isAuditorHeaderSearch ? 'Buscar pelo tipo selecionado' : 'Buscar por senha, matrícula ou nome' ?>" />
                    </div>

                    <div id="search-results-dropdown" class="dropdown-menu show fc-inline-56"
                       >
                    </div>
                </form>

                <div class="account-wrap">
                    <div class="account-item clearfix js-item-menu fc-inline-57">
                        <div class="image">
                            <?php
                            // arquivo da sessão (sanitizado) e checagem no filesystem real
                            $sessFoto  = $_SESSION['foto_usuario'] ?? '';
                            $fileName  = $sessFoto ? basename($sessFoto) : '';
                            $fsPath    = __DIR__ . '/../uploads/usuarios/' . $fileName;
                            $urlFoto   = ($fileName && is_file($fsPath))
                                ? ($BASE_URL . 'uploads/usuarios/' . $fileName)
                                : $defaultFoto;
                            ?>
                            <img src="<?= htmlspecialchars($urlFoto) ?>" alt="Usuário"
                                onerror="this.onerror=null;this.src='<?= $defaultFoto ?>';" />
                        </div>
                        <div class="content">
                            <button type="button" class="js-acc-btn account-user-trigger" aria-expanded="false">
                                <span class="account-user-name" title="<?= htmlspecialchars((string)$sessionUsuario, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$sessionUsuarioPrimeiroNome, ENT_QUOTES, 'UTF-8') ?></span>
                                <i class="bi bi-chevron-down account-user-caret" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="account-dropdown js-dropdown">
                            <div class="account-dropdown__summary">
                                <span class="account-dropdown__summary-icon">
                                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                                </span>
                                <span>
                                    <span class="account-dropdown__summary-title"><?= htmlspecialchars((string)$sessionUsuario, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="account-dropdown__summary-subtitle">Conta FullCare</span>
                                </span>
                            </div>
                            <div class="account-dropdown__body">
                                <?php if (!empty($sessionIdUsuario)): ?>
                                <div class="account-dropdown__item">
                                    <a href="<?= $BASE_URL ?>usuarios/ver/<?= (int)$sessionIdUsuario ?>">
                                        <i class="bi bi-person" aria-hidden="true"></i>Meu perfil</a>
                                </div>
                                <?php endif; ?>
                                <div class="account-dropdown__item">
                                    <a href="<?= $BASE_URL ?>mfa_configuracao.php">
                                        <i class="bi bi-shield-lock" aria-hidden="true"></i>Segurança e MFA</a>
                                </div>
                                <div class="account-dropdown__item">
                                    <a href="<?= $BASE_URL ?>usuario/sessoes">
                                        <i class="bi bi-display" aria-hidden="true"></i>Sessões ativas</a>
                                </div>
                            </div>
                            <div class="account-dropdown__footer">
                                <a href="<?= $BASE_URL ?>destroi.php">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sair</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </nav>
        <?php
        $shouldRenderBISidebar = empty($hideBIMenu ?? false)
            && !$isOperationalIntelligencePage
            && function_exists('fullcare_is_bi_request')
            && fullcare_is_bi_request();
        if ($shouldRenderBISidebar) {
            include_once(__DIR__ . '/bi_topbar.php');
        }
        ?>
        <?php if ($isOperationalIntelligencePage): ?>
            <script>
                (function () {
                    const cleanupBiSidebar = () => {
                        document.body.classList.remove('bi-theme', 'bi-nav-open', 'bi-nav-collapsed', 'bi-navegacao');
                        document.querySelectorAll('.bi-sidebar-shell, .bi-side-toggle, .bi-mobile-backdrop').forEach((el) => el.remove());
                    };
                    cleanupBiSidebar();
                    document.addEventListener('DOMContentLoaded', cleanupBiSidebar);
                })();
            </script>
        <?php endif; ?>

        <!-- feedback visual global -->
        <?php if (session_status() !== PHP_SESSION_ACTIVE) session_start(); ?>
        <?php
        $feedbackItems = [];
        if (!empty($_SESSION['fullcare_feedback']) && is_array($_SESSION['fullcare_feedback'])) {
            foreach ($_SESSION['fullcare_feedback'] as $item) {
                if (!is_array($item) || empty($item['message'])) continue;
                $feedbackItems[] = [
                    'type' => function_exists('fullcare_feedback_type') ? fullcare_feedback_type($item['type'] ?? 'info') : ($item['type'] ?? 'info'),
                    'title' => $item['title'] ?? (function_exists('fullcare_feedback_title') ? fullcare_feedback_title($item['type'] ?? 'info', $item['message'] ?? '') : null),
                    'message' => (string)$item['message'],
                ];
            }
        }
        if (!empty($_SESSION['mensagem'])) {
            $feedbackType = function_exists('fullcare_feedback_type') ? fullcare_feedback_type($_SESSION['mensagem_tipo'] ?? 'danger') : ($_SESSION['mensagem_tipo'] ?? 'danger');
            $feedbackItems[] = [
                'type' => $feedbackType,
                'title' => function_exists('fullcare_feedback_title') ? fullcare_feedback_title($feedbackType, $_SESSION['mensagem']) : null,
                'message' => (string)$_SESSION['mensagem'],
            ];
        }
        if (!empty($_SESSION['msg'])) {
            $feedbackType = function_exists('fullcare_feedback_type') ? fullcare_feedback_type($_SESSION['type'] ?? 'info') : ($_SESSION['type'] ?? 'info');
            $feedbackItems[] = [
                'type' => $feedbackType,
                'title' => function_exists('fullcare_feedback_title') ? fullcare_feedback_title($feedbackType, $_SESSION['msg']) : null,
                'message' => trim(strip_tags((string)$_SESSION['msg'])),
            ];
        }
        $feedbackSeen = [];
        $feedbackItems = array_values(array_filter($feedbackItems, static function ($item) use (&$feedbackSeen) {
            $message = trim((string)($item['message'] ?? ''));
            if ($message === '') return false;
            $key = (string)($item['type'] ?? 'info') . '|' . mb_strtolower($message, 'UTF-8');
            if (isset($feedbackSeen[$key])) return false;
            $feedbackSeen[$key] = true;
            return true;
        }));
        unset($_SESSION['fullcare_feedback'], $_SESSION['mensagem'], $_SESSION['mensagem_tipo'], $_SESSION['msg'], $_SESSION['type']);
        ?>
        <script>
            window.FullCareInitialFeedback = <?= json_encode($feedbackItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        </script>

        <div class="modal fade" id="globalModal">
            <div class="modal-dialog  modal-lg modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="fc-inline-58">
                        <h4>Paciente</h4>
                        <p class="page-description">Informações
                            do paciente</p>
                    </div>
                    <div class="modal-body">
                        <div id="global-content-php"></div>
                    </div>
                </div>
            </div>
        </div>

</body>
<script src="js/fix-header.js"></script>
<script>
    window.FullCareListUserId = <?= json_encode((string)($sessionIdUsuario ?? 'anon')) ?>;
    window.FullCareChatUnreadUrl = <?= json_encode($BASE_URL . 'ajax/chat_unread_count.php') ?>;

    function updateHeaderChatBadge(count) {
        const btn = document.getElementById('header-chat-launcher');
        const badge = document.getElementById('header-chat-unread-badge');
        if (!btn || !badge) return;

        const unread = Math.max(0, Number(count || 0));
        btn.dataset.unreadCount = String(unread);
        btn.classList.toggle('has-unread', unread > 0);
        btn.title = unread > 0 ? `${unread} mensagem(ns) não lida(s)` : 'Mensagens entre usuários';
        btn.setAttribute('aria-label', unread > 0 ? `Mensagens: ${unread} não lida(s)` : 'Mensagens entre usuários');
        badge.textContent = unread > 99 ? '99+' : String(unread);
        badge.hidden = unread <= 0;
    }

    function refreshHeaderChatBadge() {
        if (!window.FullCareChatUnreadUrl || typeof fetch !== 'function') return;
        fetch(window.FullCareChatUnreadUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then((response) => response.ok ? response.json() : null)
            .then((payload) => {
                if (!payload || payload.success === false) return;
                updateHeaderChatBadge(payload.count || 0);
            })
            .catch(() => {});
    }

    function setupHeaderAccountDropdown() {
        var items = document.querySelectorAll('.js-item-menu');
        if (!items.length) return;

        items.forEach(function(item) {
            var trigger = item.querySelector('.js-acc-btn');
            var dropdown = item.querySelector('.js-dropdown');
            if (!trigger || !dropdown || trigger.dataset.accountDropdownBound === '1') return;
            trigger.dataset.accountDropdownBound = '1';

            trigger.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                items.forEach(function(otherItem) {
                    if (otherItem !== item) {
                        otherItem.classList.remove('show-dropdown');
                        var otherTrigger = otherItem.querySelector('.js-acc-btn');
                        if (otherTrigger) {
                            otherTrigger.setAttribute('aria-expanded', 'false');
                        }
                    }
                });

                var willOpen = !item.classList.contains('show-dropdown');
                item.classList.toggle('show-dropdown', willOpen);
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            dropdown.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        });

        document.addEventListener('click', function() {
            items.forEach(function(item) {
                item.classList.remove('show-dropdown');
                var trigger = item.querySelector('.js-acc-btn');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        try {
            localStorage.removeItem('fcx_zoom');
        } catch (e) {}
        document.documentElement.style.zoom = '';
        setupHeaderAccountDropdown();
        updateHeaderChatBadge(document.getElementById('header-chat-launcher')?.dataset.unreadCount || 0);
        refreshHeaderChatBadge();
        window.setInterval(refreshHeaderChatBadge, 30000);
    });
</script>

<!-- Jquery JS-->
<script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>

<!-- Bootstrap JS-->
<script src="./diversos/CoolAdmin-master/vendor/bootstrap-4.1/popper.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/bootstrap-4.1/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous">
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta2/js/bootstrap-select.min.js"></script>
<!-- Vendor JS       -->
<script src="./diversos/CoolAdmin-master/vendor/slick/slick.min.js">
</script>
<script src="./diversos/CoolAdmin-master/vendor/wow/wow.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/animsition/animsition.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/bootstrap-progressbar/bootstrap-progressbar.min.js">
</script>
<script src="./diversos/CoolAdmin-master/vendor/counter-up/jquery.waypoints.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/counter-up/jquery.counterup.min.js">
</script>
<script src="./diversos/CoolAdmin-master/vendor/circle-progress/circle-progress.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="diversos/chartjs/Chart.min.js"></script>
<script src="./diversos/CoolAdmin-master/vendor/select2/select2.min.js"></script>
<script src="./scripts/cadastro/general.js"></script>
<script src="<?= $BASE_URL ?>js/stepper.js?v=<?= rawurlencode(defined('APP_VERSION') ? APP_VERSION : '1') ?>"></script>
<script src="js/show_internacao_visitas.js"></script>
<script src="<?= $BASE_URL ?>js/contextual-assistant.js"></script>
<script>
    // Base para links absolutos
    window.BASE_URL = '<?= $BASE_URL ?>';

    function setupModalForms(container, modalEl) {
        if (!container || !modalEl) return;
        const forms = container.querySelectorAll('form');
        forms.forEach((form) => {
            if (form.dataset.modalAjaxBound === '1') return;
            form.dataset.modalAjaxBound = '1';

            form.addEventListener('submit', function modalFormSubmit(ev) {
                if (!modalEl.contains(form)) return;
                ev.preventDefault();

                const action = form.getAttribute('action') || window.location.href;
                const method = (form.getAttribute('method') || 'POST').toUpperCase();
                const submitBtn = form.querySelector('[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                fetch(action, {
                        method,
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    })
                    .then(resp => {
                        const ct = resp.headers.get('content-type') || '';
                        if (ct.includes('application/json')) {
                            return resp.json();
                        }
                        return resp.text().then(html => ({
                            html
                        }));
                    })
                    .then(payload => {
                        if (payload && payload.success) {
                            if (window.bootstrap && bootstrap.Modal) {
                                const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                inst.hide();
                            } else if (window.$ && typeof $('#globalModal').modal === 'function') {
                                $('#globalModal').modal('hide');
                            }
                            document.dispatchEvent(new CustomEvent('modalFormSuccess', {
                                detail: payload
                            }));
                            if (payload.paciente) {
                                document.dispatchEvent(new CustomEvent('paciente:cadastrado', {
                                    detail: payload.paciente
                                }));
                            }
                            if (window.FullCareFeedback && typeof window.FullCareFeedback.success === 'function') {
                                window.FullCareFeedback.success(payload.message || 'As informações foram salvas com sucesso.', 'Cadastro atualizado');
                            }
                            return;
                        }
                        if (payload && payload.success === false) {
                            throw new Error(payload.message || 'Não foi possível salvar o formulário.');
                        }
                        if (payload && payload.html) {
                            const temp = document.createElement('div');
                            temp.innerHTML = payload.html;
                            let inner = temp.querySelector('#main-container') || temp.querySelector('main') || temp.querySelector('body');
                            const html = inner ? inner.innerHTML : payload.html;
                            renderModalBody(container, html, modalEl);
                            return;
                        }
                        throw new Error('Resposta inesperada');
                    })
                    .catch((err) => {
                        const msg = (err && err.message) ? err.message : 'Erro ao processar o formulário.';
                        if (window.FullCareFeedback && typeof window.FullCareFeedback.error === 'function') {
                            window.FullCareFeedback.error(msg, 'Não foi possível salvar');
                        }
                        container.innerHTML = '<div class="p-4 text-danger">' + escapeHtml(msg) + '</div>';
                    })
                    .finally(() => {
                        if (submitBtn) submitBtn.disabled = false;
                    });
            });
        });
    }

    function renderModalBody(target, html, modalEl) {
        if (!target) return;
        target.innerHTML = html;

        try {
            if (window.$ && typeof $('.selectpicker').selectpicker === 'function') {
                $('.selectpicker', target).each(function() {
                    var $el = $(this);
                    var hasWrapper = $el.siblings('div.bootstrap-select').length > 0;
                    if (!hasWrapper && !$el.data('selectpicker')) {
                        $el.selectpicker();
                    }
                    if ($el.siblings('div.bootstrap-select').length > 1) {
                        $el.siblings('div.bootstrap-select').slice(1).remove();
                    }
                    if ($el.siblings('div.bootstrap-select').length) {
                        $el.addClass('bs-select-hidden');
                    }
                    try {
                        $el.selectpicker('refresh');
                    } catch (_) {}
                });
            }
        } catch (_) {}

        setupModalForms(target, modalEl);
        if (typeof window.initPacienteHomonimoCheck === 'function') {
            window.initPacienteHomonimoCheck(target);
        }
    }

    if (typeof window.openModalPac !== 'function') {
        window.openModalPac = function(url, titulo = 'Cadastro') {
            const modalEl = document.getElementById('globalModal');
            if (!modalEl) {
                console.warn('[openModalPac] #globalModal não encontrado. Navegando para:', url);
                window.location.href = url;
                return;
            }

            const body = modalEl.querySelector('.modal-body');
            const titleEl = modalEl.querySelector('.modal-title');
            if (titleEl) titleEl.textContent = titulo;
            body.innerHTML = '<div class="p-4 text-center text-muted">Carregando...</div>';

            // Bootstrap 5.0/5.1: não tem getOrCreateInstance
            let bsModal = null;
            if (window.bootstrap && bootstrap.Modal) {
                if (typeof bootstrap.Modal.getInstance === 'function') {
                    bsModal = bootstrap.Modal.getInstance(modalEl);
                }
                if (!bsModal) {
                    bsModal = new bootstrap.Modal(modalEl); // 5.0/5.1 OK
                }
                bsModal.show();
            } else if (window.$ && typeof $('#globalModal').modal === 'function') {
                // fallback jQuery/BS4
                $('#globalModal').modal('show');
            }

            fetch(url, {
                    credentials: 'same-origin'
                })
                .then(r => r.text())
                .then(html => {
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    let inner = temp.querySelector('#main-container') || temp.querySelector('main') || temp.querySelector('body');
                    const resolvedHtml = inner ? inner.innerHTML : html;
                    renderModalBody(body, resolvedHtml, modalEl);
                })
                .catch(err => {
                    console.error(err);
                    const msg = 'Falha ao carregar conteúdo do modal.';
                    if (window.FullCareFeedback && typeof window.FullCareFeedback.error === 'function') {
                        window.FullCareFeedback.error(msg, 'Conteúdo indisponível');
                    }
                    body.innerHTML = '<div class="p-4 text-danger">' + msg + '</div>';
                });
        };
    }

    // --- debounce simples ---
    function debounce(fn, wait) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    (function initFullCareHeaderSearch() {
    var $input = $('#inp-search-paciente');
    var $menu = $('#search-results-dropdown');
    var $searchType = $('#global-search-type');

    var searchTypeConfig = {
        paciente: {
            placeholder: 'Nome ou matrícula',
            empty: 'Nenhum paciente encontrado por nome ou matrícula.',
            create: true,
        },
        internacao: {
            placeholder: 'Nome do paciente ou ID da internação',
            empty: 'Nenhuma internação encontrada por nome ou ID.',
            create: false,
        },
        conta: {
            placeholder: 'Nome do paciente ou ID da conta',
            empty: 'Nenhuma conta encontrada por nome ou ID.',
            create: false,
        },
    };

    function currentSearchType() {
        return ($searchType.length ? String($searchType.val() || 'paciente') : 'paciente');
    }

    function applySearchTypeUi() {
        var cfg = searchTypeConfig[currentSearchType()] || searchTypeConfig.paciente;
        if ($searchType.length) {
            $input.attr('placeholder', cfg.placeholder);
        }
        $menu.hide();
    }

    // Renderiza itens no dropdown
    function renderResults(items) {
        if (!items || !items.length) {
            var termo = $input.val().trim();
            var termoEsc = escapeHtml(termo);
            var cfg = searchTypeConfig[currentSearchType()] || searchTypeConfig.paciente;
            var createLink = cfg.create ? `
                <a href="#" id="create-new-pac" class="dropdown-item d-flex justify-content-between align-items-center">
                    <div>
                        <div><strong>Cadastrar novo paciente</strong></div>
                        ${termo ? `<small class="text-muted">Iniciar cadastro com: <em>${termoEsc}</em></small>` : ''}
                    </div>
                    <i class="bi bi-plus-circle"></i>
                </a>
            ` : '';
            $menu.html(`
        <div class="dropdown-item text-muted">${escapeHtml(cfg.empty)}</div>
        ${createLink}
        `).show();
            return;
        }

        var html = items.map((p, idx) => {
            var isOperational = Boolean(p.type || p.title || p.url);
            var href = isOperational && p.url
                ? p.url
                : `pacientes/hub/${encodeURIComponent(p.id_paciente)}`;
            var icon = escapeHtml(p.icon || (p.type === 'internacao' ? 'bi-hospital' : (p.type === 'conta' ? 'bi-receipt' : 'bi-person-vcard')));
            var typeLabel = p.type ? escapeHtml(String(p.type).charAt(0).toUpperCase() + String(p.type).slice(1)) : 'Paciente';
            var title = p.title || p.nome || 'Paciente sem nome';
            var subtitle = p.subtitle || '';
            if (!subtitle) {
                var metaParts = [];
                if (p.senha) metaParts.push(`Senha: ${p.senha}`);
                if (p.matricula) metaParts.push(`Matrícula: ${p.matricula}`);
                if (p.nascimento_fmt) metaParts.push(`Nasc.: ${p.nascimento_fmt}`);
                subtitle = metaParts.join(' • ');
            }
            var meta = subtitle ? `<small class="text-muted">${escapeHtml(subtitle)}</small>` : '';

            return `
        <a href="${escapeHtml(href)}"
            class="dropdown-item d-flex justify-content-between align-items-center ${idx === 0 ? 'active' : ''}"
            data-id="${escapeHtml(p.id_paciente || '')}">
            <div>
                <div><strong>${escapeHtml(title)}</strong></div>
                ${meta}
            </div>
            <span class="d-inline-flex align-items-center gap-2">
                <small class="text-muted">${typeLabel}</small>
                <i class="bi ${icon}"></i>
            </span>
        </a>
        `;
        }).join('');
        $menu.html(html).show();
    }


    // Faz a busca
    var doSearch = debounce(function() {
        var q = $input.val().trim();
        if (q.length < 2) {
            $menu.hide();
            return;
        }
        $.getJSON('ajax/pacientes_search.php', {
                q,
                type: currentSearchType()
            })
            .done(res => {
                renderResults(res);
            })
            .fail((jqXHR, textStatus, errorThrown) => {
                console.error('[BUSCA ERRO]', {
                    status: jqXHR.status,
                    textStatus,
                    errorThrown,
                    responseText: jqXHR.responseText
                });
                if (window.FullCareFeedback && typeof window.FullCareFeedback.error === 'function') {
                    window.FullCareFeedback.error('Não foi possível concluir a busca. Tente novamente.', 'Busca indisponível');
                }
                $menu
                    .html(
                        `<div class="dropdown-item text-danger">
            Erro ao buscar (${jqXHR.status} / ${textStatus})<br>
                <small>${errorThrown}</small>
        </div>`
                    )
                    .show();
            });

    }, 250);

    $input.off('input.fullcareHeaderSearch').on('input.fullcareHeaderSearch', doSearch);
    $searchType.off('change.fullcareHeaderSearch').on('change.fullcareHeaderSearch', function() {
        applySearchTypeUi();
        if ($input.val().trim().length >= 2) {
            doSearch();
        }
        $input.trigger('focus');
    });
    applySearchTypeUi();

    // Fecha dropdown ao clicar fora
    $(document).off('click.fullcareHeaderSearch').on('click.fullcareHeaderSearch', function(e) {
        if (!$(e.target).closest('#global-patient-search').length) {
            $menu.hide();
        }
    });

    // Teclas: ↑ ↓ Enter Esc
    $input.off('keydown.fullcareHeaderSearch').on('keydown.fullcareHeaderSearch', function(e) {
        var $items = $menu.find('.dropdown-item');
        if (!$items.length || $menu.is(':hidden')) return;

        var $current = $items.filter('.active');
        var idx = $items.index($current);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            $current.removeClass('active');
            idx = (idx + 1) % $items.length;
            $items.eq(idx).addClass('active')[0].scrollIntoView({
                block: 'nearest'
            });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            $current.removeClass('active');
            idx = (idx - 1 + $items.length) % $items.length;
            $items.eq(idx).addClass('active')[0].scrollIntoView({
                block: 'nearest'
            });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var href = ($current.length ? $current : $items.eq(0)).attr('href');
            if (href) window.location.href = href;
        } else if (e.key === 'Escape') {
            $menu.hide();
        }
    });

    // Clique em item
    $menu.off('click.fullcareHeaderSearchItem', '.dropdown-item').on('click.fullcareHeaderSearchItem', '.dropdown-item', function(e) {
        // deixa o link funcionar (navegar)
    });
    $menu.off('click.fullcareHeaderSearchCreate', '#create-new-pac').on('click.fullcareHeaderSearchCreate', '#create-new-pac', function(e) {
        e.preventDefault();
        var termo = $input.val().trim();
        // Se quiser pré-preencher:
        // const url = BASE_URL + 'cad_paciente.php' + (termo ? ('?nome_pac=' + encodeURIComponent(termo)) : '');
        var url = window.BASE_URL + 'pacientes/novo';
        navigateWithReturn(url);
        $menu.hide();
    });
    })();

    function escapeAttrValue(val) {
        return String(val)
            .replace(/\\/g, '\\\\')
            .replace(/"/g, '\\"');
    }

    function navigateWithReturn(url) {
        try {
            sessionStorage.removeItem('return_flow_url');
        } catch (e) {}
        try {
            const draft = collectFormDraft();
            if (draft) {
                sessionStorage.setItem('return_form_draft', JSON.stringify(draft));
            } else {
                sessionStorage.removeItem('return_form_draft');
            }
        } catch (e) {}
        window.location.href = url;
    }

    function collectFormDraft() {
        const form = document.getElementById('myForm');
        if (!form) return null;
        const elements = Array.from(form.elements || []);
        const values = {};
        const checks = {};
        let hasValue = false;

        const skipTypes = ['button', 'submit', 'reset', 'file'];

        elements.forEach(el => {
            if (!el || !el.name || el.disabled) return;
            const type = (el.type || '').toLowerCase();
            if (skipTypes.includes(type)) return;

            if (type === 'checkbox') {
                if (!checks[el.name]) checks[el.name] = {};
                const key = el.value || '__on__';
                checks[el.name][key] = el.checked;
                if (el.checked) hasValue = true;
                return;
            }

            if (type === 'radio') {
                if (el.checked) {
                    values[el.name] = el.value;
                    hasValue = true;
                } else if (!(el.name in values)) {
                    values[el.name] = null;
                }
                return;
            }

            if (el.tagName === 'SELECT' && el.multiple) {
                const selected = Array.from(el.options || [])
                    .filter(opt => opt.selected)
                    .map(opt => opt.value);
                values[el.name] = selected;
                if (selected.length) hasValue = true;
                return;
            }

            values[el.name] = el.value;
            if (el.value) hasValue = true;
        });

        if (!hasValue) return null;

        return {
            url: window.location.href,
            timestamp: Date.now(),
            values,
            checks
        };
    }

    function restoreFormDraft() {
        let raw = null;
        try {
            raw = sessionStorage.getItem('return_form_draft');
        } catch (e) {
            raw = null;
        }
        if (!raw) return;
        let payload;
        try {
            payload = JSON.parse(raw);
        } catch (e) {
            sessionStorage.removeItem('return_form_draft');
            return;
        }
        if (!payload || payload.url !== window.location.href) return;
        const form = document.getElementById('myForm');
        if (!form) return;

        const values = payload.values || {};
        Object.keys(values).forEach(name => {
            const field = form.elements.namedItem(name);
            if (!field) return;
            const stored = values[name];

            if (field instanceof RadioNodeList || (field.length && field[0] && field[0].type === 'radio')) {
                const radios = field.length ? Array.from(field) : [field];
                radios.forEach(radio => {
                    radio.checked = stored !== null && radio.value === stored;
                });
                return;
            }

            if (field.tagName === 'SELECT' && field.multiple && Array.isArray(stored)) {
                Array.from(field.options || []).forEach(opt => {
                    opt.selected = stored.includes(opt.value);
                });
                return;
            }

            field.value = stored ?? '';
        });

        const checkboxStates = payload.checks || {};
        Object.keys(checkboxStates).forEach(name => {
            const states = checkboxStates[name];
            const selector = 'input[type="checkbox"][name="' + escapeAttrValue(name) + '"]';
            const boxes = form.querySelectorAll(selector);
            boxes.forEach(box => {
                const key = box.value || '__on__';
                if (Object.prototype.hasOwnProperty.call(states, key)) {
                    box.checked = !!states[key];
                }
            });
        });

        if (window.$ && $.fn.selectpicker) {
            $('.selectpicker', form).each(function() {
                try {
                    var id = this.id || '';
                    if (id === 'hospital_selected' || id === 'fk_paciente_int') {
                        return;
                    }
                    $(this).selectpicker('refresh');
                } catch (_) {}
            });
        }

        try {
            sessionStorage.removeItem('return_form_draft');
        } catch (_) {}
    }

    document.addEventListener('keydown', function(e) {
        if (!e.ctrlKey || !e.shiftKey) return;
        const key = (e.key || '').toUpperCase();
        let handled = false;

        if (key === 'I') {
            handled = true;
            navigateWithReturn(window.BASE_URL + 'internacoes/nova');
        } else if (key === 'P') {
            handled = true;
            navigateWithReturn(window.BASE_URL + 'pacientes/novo');
        } else if (key === 'V') {
            handled = true;
            navigateWithReturn(window.BASE_URL + 'visitas/nova');
        } else if (key === 'S') {
            handled = true;
            if (typeof triggerInternacaoAutoSave === 'function') {
                triggerInternacaoAutoSave();
            } else {
                const form = document.getElementById('myForm');
                form && form.submit();
            }
        } else if (key === 'L') {
            handled = true;
            navigateWithReturn(window.BASE_URL + 'internacoes/lista');
        } else if (key === 'C') {
            handled = true;
            navigateWithReturn(window.BASE_URL + 'internacoes/rah');
        } else if (key === 'A') {
            handled = true;
            navigateWithReturn(window.BASE_URL + 'listas/altas');
        }

        if (handled) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        try {
            sessionStorage.removeItem('return_flow_url');
        } catch (_) {}
        restoreFormDraft();
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var delay = 160;
        document.querySelectorAll('.bi-dropdown.bi-mega').forEach(function(menu) {
            menu.addEventListener('mouseleave', function() {
                menu.querySelectorAll('.bi-submenu.open').forEach(function(openItem) {
                    openItem.classList.remove('open');
                });
            });
        });

        document.querySelectorAll('.bi-dropdown.bi-mega .bi-submenu').forEach(function(item) {
            var timer;
            var trigger = item.querySelector('.dropdown-item');
            if (trigger) {
                trigger.addEventListener('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    item.parentElement.querySelectorAll('.bi-submenu.open').forEach(function(openItem) {
                        if (openItem !== item) {
                            openItem.classList.remove('open');
                            openItem.classList.remove('submenu-left');
                        }
                    });
                    if (item.classList.contains('open')) {
                        item.classList.remove('open');
                        item.classList.remove('submenu-left');
                        return;
                    }
                    item.classList.add('open');
                    item.classList.remove('submenu-left');
                    var submenu = item.querySelector('.bi-submenu-list');
                    if (submenu) {
                        var rect = submenu.getBoundingClientRect();
                        if (rect.right > window.innerWidth) {
                            item.classList.add('submenu-left');
                        }
                    }
                });
            }
            item.addEventListener('mouseenter', function() {
                timer = setTimeout(function() {
                    item.parentElement.querySelectorAll('.bi-submenu.open').forEach(function(openItem) {
                        if (openItem !== item) {
                            openItem.classList.remove('open');
                        }
                    });
                    item.classList.add('open');
                    item.classList.remove('submenu-left');
                    var submenu = item.querySelector('.bi-submenu-list');
                    if (submenu) {
                        var rect = submenu.getBoundingClientRect();
                        if (rect.right > window.innerWidth) {
                            item.classList.add('submenu-left');
                        }
                    }
                }, delay);
            });
            item.addEventListener('mouseleave', function() {
                clearTimeout(timer);
                item.classList.remove('open');
                item.classList.remove('submenu-left');
            });
            item.addEventListener('focusin', function() {
                item.classList.add('open');
                item.classList.remove('submenu-left');
                var submenu = item.querySelector('.bi-submenu-list');
                if (submenu) {
                    var rect = submenu.getBoundingClientRect();
                    if (rect.right > window.innerWidth) {
                        item.classList.add('submenu-left');
                    }
                }
            });
            item.addEventListener('focusout', function() {
                item.classList.remove('open');
                item.classList.remove('submenu-left');
            });
        });
    });
</script>

</html>
