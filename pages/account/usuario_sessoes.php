<?php
include_once("check_logado.php");
require_once("templates/header.php");

if (!function_exists('account_page_e')) {
    function account_page_e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$sessionStartedAt = $_SESSION['session_started_at'] ?? null;
if (empty($sessionStartedAt)) {
    $_SESSION['session_started_at'] = date('Y-m-d H:i:s');
    $sessionStartedAt = $_SESSION['session_started_at'];
}

$sessionLastSeen = date('Y-m-d H:i:s');
$userName = $_SESSION['usuario_user'] ?? 'Usuário';
$userEmail = $_SESSION['email_user'] ?? '';
$userLevel = $_SESSION['nivel'] ?? '';
$userRole = $_SESSION['cargo'] ?? '';
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '-';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '-';
$sessionIdPreview = session_id() ? substr(session_id(), 0, 8) . '...' : '-';
?>

<style>
    .account-session-page {
        max-width: 1120px;
        margin: 18px auto 92px;
        padding: 0 16px;
    }

    .account-session-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 66px;
        padding: 16px 20px;
        border-radius: 14px;
        background: linear-gradient(100deg, #2f6f9f 0%, #5bb9d9 100%);
        color: #fff;
        box-shadow: 0 10px 28px rgba(47, 111, 159, .16);
    }

    .account-session-hero h1 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .account-session-hero p {
        margin: 4px 0 0;
        font-size: .78rem;
        opacity: .92;
    }

    .account-session-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid rgba(255,255,255,.45);
        border-radius: 999px;
        padding: 7px 12px;
        color: #fff;
        text-decoration: none;
        font-size: .76rem;
        font-weight: 700;
        background: rgba(255,255,255,.12);
    }

    .account-session-back:hover {
        color: #fff;
        background: rgba(255,255,255,.2);
    }

    .account-session-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 14px;
    }

    .account-session-card {
        border: 1px solid #e4edf5;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(31, 45, 61, .06);
        padding: 14px;
    }

    .account-session-card--wide {
        grid-column: span 3;
    }

    .account-session-card h2 {
        margin: 0 0 10px;
        color: #24384f;
        font-size: .92rem;
        font-weight: 800;
    }

    .account-session-field {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 10px;
        padding: 8px 0;
        border-top: 1px solid #edf2f7;
        font-size: .78rem;
    }

    .account-session-field:first-of-type {
        border-top: 0;
    }

    .account-session-label {
        color: #6b7a90;
        font-weight: 800;
        text-transform: uppercase;
        font-size: .62rem;
    }

    .account-session-value {
        color: #24384f;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .account-session-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: fit-content;
        border-radius: 999px;
        padding: 5px 10px;
        background: #eaf7ef;
        color: #157347;
        font-size: .72rem;
        font-weight: 800;
    }

    .account-session-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 12px;
    }

    @media (max-width: 900px) {
        .account-session-grid {
            grid-template-columns: 1fr;
        }
        .account-session-card--wide {
            grid-column: span 1;
        }
    }

    @media (max-width: 560px) {
        .account-session-hero {
            align-items: flex-start;
            flex-direction: column;
        }
        .account-session-field {
            grid-template-columns: 1fr;
            gap: 3px;
        }
    }
</style>

<main class="account-session-page">
    <section class="account-session-hero">
        <div>
            <h1>Sessões ativas</h1>
            <p>Confira os dados da sua sessão atual no FullCare.</p>
        </div>
        <a class="account-session-back" href="<?= account_page_e($BASE_URL) ?>dashboard">
            <i class="bi bi-arrow-left"></i>
            Voltar
        </a>
    </section>

    <section class="account-session-grid">
        <article class="account-session-card">
            <h2>Usuário</h2>
            <div class="account-session-field">
                <span class="account-session-label">Nome</span>
                <span class="account-session-value"><?= account_page_e($userName) ?></span>
            </div>
            <div class="account-session-field">
                <span class="account-session-label">E-mail</span>
                <span class="account-session-value"><?= account_page_e($userEmail ?: '-') ?></span>
            </div>
            <div class="account-session-field">
                <span class="account-session-label">Perfil</span>
                <span class="account-session-value"><?= account_page_e($userRole ?: $userLevel ?: '-') ?></span>
            </div>
        </article>

        <article class="account-session-card">
            <h2>Sessão atual</h2>
            <div class="account-session-field">
                <span class="account-session-label">Status</span>
                <span class="account-session-value"><span class="account-session-status"><i class="bi bi-check-circle"></i>Ativa</span></span>
            </div>
            <div class="account-session-field">
                <span class="account-session-label">Início</span>
                <span class="account-session-value"><?= account_page_e($sessionStartedAt) ?></span>
            </div>
            <div class="account-session-field">
                <span class="account-session-label">Última leitura</span>
                <span class="account-session-value"><?= account_page_e($sessionLastSeen) ?></span>
            </div>
        </article>

        <article class="account-session-card">
            <h2>Acesso</h2>
            <div class="account-session-field">
                <span class="account-session-label">IP</span>
                <span class="account-session-value"><?= account_page_e($remoteIp) ?></span>
            </div>
            <div class="account-session-field">
                <span class="account-session-label">Sessão</span>
                <span class="account-session-value"><?= account_page_e($sessionIdPreview) ?></span>
            </div>
            <div class="account-session-actions">
                <a class="btn btn-outline-danger btn-sm" href="<?= account_page_e($BASE_URL) ?>destroi.php">
                    <i class="bi bi-box-arrow-right"></i> Encerrar sessão
                </a>
            </div>
        </article>

        <article class="account-session-card account-session-card--wide">
            <h2>Dispositivo</h2>
            <div class="account-session-field">
                <span class="account-session-label">Navegador</span>
                <span class="account-session-value"><?= account_page_e($userAgent) ?></span>
            </div>
        </article>
    </section>
</main>

<?php require_once("templates/footer.php"); ?>
