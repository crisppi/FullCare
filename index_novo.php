<!DOCTYPE html>
<?php $currentAppVersion = app_latest_version($conn); ?>
<html lang="pt-BR">
<?php $assetBase = rtrim($BASE_URL, '/'); ?>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FullCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ===============================
       Base
    =============================== */
    body {
        margin: 0;
        padding: 24px;
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
        background:
            radial-gradient(circle at 18% 18%, rgba(82, 154, 218, .24), transparent 28%),
            radial-gradient(circle at 88% 20%, rgba(92, 38, 118, .22), transparent 26%),
            linear-gradient(135deg, #edf5fb 0%, #dfe9f3 44%, #f0edf7 100%);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        position: relative;
        opacity: 0;
        animation: fadeIn .3s ease-in forwards;
    }

    body::before {
        content: "";
        position: fixed;
        inset: 0;
        background: url("<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/img/17450.jpg") center / cover no-repeat;
        opacity: 0.18;
        z-index: -1;
        pointer-events: none;
    }

    @keyframes fadeIn {
        from {
            opacity: 0
        }

        to {
            opacity: 1
        }
    }

    .login-container {
        display: grid;
        grid-template-columns: 330px minmax(390px, 1fr);
        gap: 56px;
        width: 920px;
        max-width: 95vw;
        min-height: 500px;
        padding: 46px 54px;
        box-sizing: border-box;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, .94);
        border: 1px solid rgba(255, 255, 255, .78);
        border-radius: 2px;
        box-shadow: 0 28px 60px rgba(50, 68, 94, .2);
        backdrop-filter: none;
        position: relative;
        overflow: hidden;
    }

    .login-container::before {
        content: "";
        position: absolute;
        width: 430px;
        height: 430px;
        left: -170px;
        top: -240px;
        border-radius: 46%;
        background: rgba(44, 132, 126, .92);
        transform: rotate(-14deg);
        pointer-events: none;
    }

    .login-container::after {
        content: "";
        position: absolute;
        width: 300px;
        height: 300px;
        right: -190px;
        top: 20px;
        border-radius: 42%;
        background: rgba(244, 194, 0, .95);
        transform: rotate(-12deg);
        pointer-events: none;
    }

    /* ===============================
       Bloco Azul (formulário)
    =============================== */
    .login-form {
        padding: 42px 36px 38px;
        border-radius: 10px;
        width: auto;
        height: auto;
        min-height: 0;
        background: rgba(238, 245, 245, .96);
        border: 1px solid rgba(223, 234, 235, .95);
        box-shadow: 0 18px 42px rgba(71, 88, 106, .1);
        backdrop-filter: none;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        position: relative;
        z-index: 2;
        transform: none;
    }

    .login-form::before {
        content: none;
    }

    .login-form-logo {
        width: 100%;
        max-width: 172px;
        margin-bottom: 34px;
        display: block;
    }

    .form-content {
        width: min(100%, 268px);
    }

    .input-container {
        position: relative;
        margin: 19px 0;
        width: 100%;
    }

    .input-container input {
        width: 100%;
        padding: 10px 0 !important;
        border: 0 !important;
        border-bottom: 1px solid rgba(47, 132, 128, .58) !important;
        background: transparent !important;
        border-radius: 0 !important;
        box-sizing: border-box;
        color: #263241;
        font-size: 13px !important;
        font-weight: 500;
        outline: none;
        box-shadow: none;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .input-container input:focus {
        background: transparent !important;
        border-color: #421849 !important;
        box-shadow: 0 6px 0 -5px rgba(66, 24, 73, .7);
    }

    .input-container label {
        position: absolute;
        top: -16px;
        left: 0;
        color: #2f8480;
        pointer-events: none;
        transition: color .2s ease;
        font-size: 11px;
        font-weight: 700;
    }

    .input-container input:focus+label,
    .input-container input:not(:placeholder-shown)+label {
        top: -20px;
        font-size: 11px;
        color: #421849;
    }

    .login-btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #5f2769, #3f174d);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, .22);
        cursor: pointer;
        font-size: 14px;
        font-weight: 700;
        border-radius: 8px;
        margin-top: 18px;
        box-shadow: 0 10px 24px rgba(49, 18, 62, .23);
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .login-btn:hover {
        box-shadow: 0 13px 28px rgba(49, 18, 62, .28);
        background: linear-gradient(135deg, #6d2e78, #451954);
        transform: translateY(-1px);
    }

    .forgot-password {
        color: #485565;
        text-align: center;
        margin-top: 20px;
    }

    .forgot-password a {
        color: #485565;
        text-decoration: none;
    }

    .login-links {
        width: 100%;
        display: flex;
        justify-content: flex-end;
        margin: -6px 0 6px;
    }

    .login-links a {
        font-size: 12px;
        color: #485565;
        text-decoration: none;
        font-weight: 600;
    }

    .login-links a:hover {
        text-decoration: underline;
    }

    .login-attempts-notice {
        margin: 8px 0 0;
        padding: 10px 12px;
        border-radius: 10px;
        background: rgba(255, 243, 205, 0.22);
        border: 1px solid rgba(255, 232, 163, 0.45);
        color: #fff5d6;
        font-size: 13px;
        line-height: 1.35;
        text-align: left;
    }

    /* ===============================
       Bloco Lilás (lado direito)
    =============================== */
    .side-panel {
        padding: 0;
        background: transparent;
        color: #421849;
        width: auto;
        max-height: none;
        min-height: 0;
        box-shadow: none;
        display: flex;
        flex-direction: column;
        justify-content: center;
        border-radius: 0;
        margin: 0;
        text-align: center;
        position: relative;
        overflow: visible;
        z-index: 2;
    }

    .side-panel-content {
        margin-top: 0;
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .side-panel img.monitor-image {
        width: min(100%, 430px);
        height: auto;
        object-fit: contain;
        margin: 0 auto 18px;
        filter: drop-shadow(0 18px 24px rgba(45, 31, 78, .25));
    }

    .side-panel h3,
    .side-panel p,
    .side-panel .email-btn {
        margin: 10px 0;
    }

    .side-panel p {
        margin: 8px auto 0;
        max-width: 360px;
        line-height: 1.5;
        color: rgba(55, 46, 78, .68);
        font-size: 12px;
        font-weight: 400;
    }

    .side-panel h3 {
        margin-top: 0;
        font-size: 16px;
        line-height: 1.2;
        letter-spacing: 0;
        font-weight: 600;
        color: #421849;
    }

    .side-panel .email-btn {
        background: #421849;
        color: #fff;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        font-size: 16px;
        border-radius: 5px;
    }

    .side-panel::before {
        content: none;
    }

    .side-panel::after {
        content: none;
    }

    /* ===============================
       Mensagem de erro (flutuante)
    =============================== */
    .error-message {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        width: min(92vw, 560px);
        padding: 12px 14px;
        background: rgba(156, 28, 28, 0.92);
        border: 1px solid rgba(255, 255, 255, .18);
        border-left: 5px solid #ffb3b3;
        border-radius: 10px;
        text-align: left;
        box-shadow: 0 10px 24px rgba(0, 0, 0, .22);
        color: #fff;
        font-size: 15px;
        line-height: 1.4;
        animation: fadeIn .3s ease-in-out;
        z-index: 1000;
    }

    .error-message strong {
        display: block;
        font-size: 13px;
        letter-spacing: .03em;
        margin-bottom: 2px;
        opacity: .9;
        text-transform: uppercase;
    }

    .error-message.hide {
        opacity: 0;
        transform: translate(-50%, 12px);
        transition: opacity .35s ease, transform .35s ease;
    }

    /* ===============================
       Responsivo
    =============================== */
    @media (max-width: 1024px) {
        body {
            padding: 20px 16px;
            height: auto;
        }

        .login-container {
            width: 100%;
            max-width: 790px;
            gap: 30px;
            grid-template-columns: 320px minmax(330px, 1fr);
        }

        .form-content {
            width: min(100%, 285px);
        }
    }

    @media (max-width: 900px) {
        .login-container {
            flex-direction: column;
            display: flex;
            border-radius: 16px;
            overflow: visible;
            min-height: 0;
            background: transparent;
            gap: 26px;
        }

        .login-form,
        .side-panel {
            width: 100%;
            height: auto;
        }

        .login-form {
            padding: 32px 28px 30px;
            min-height: 0;
            width: min(100%, 360px);
            margin: 0 auto;
            transform: none;
        }

        .login-form::before {
            content: none;
        }

        .side-panel {
            min-height: 0;
            margin: 0;
            padding: 0 24px 10px;
        }

        .side-panel::after {
            content: none;
        }

        .side-panel-content {
            margin-top: 0;
        }
    }

    @media (max-width: 600px) {
        .side-panel {
            display: none;
        }

        body {
            min-height: 520px;
            align-items: flex-start;
        }

        .login-container {
            align-items: flex-start;
            height: auto;
        }

        .login-form {
            padding: 30px 22px 26px;
            min-height: 0;
            height: auto;
            max-height: none;
            border-radius: 14px;
            transform: none;
        }

        .login-form::before {
            content: none;
        }

        .login-form-logo {
            max-width: 165px;
            margin-bottom: 28px;
        }

        .form-content {
            width: 100%;
        }

        .input-container {
            margin: 22px 0;
        }

        .input-container input {
            padding: 11px 13px !important;
            font-size: 13px !important;
        }

        .input-container label {
            font-size: 12px;
        }

        .input-container input:focus+label,
        .input-container input:not(:placeholder-shown)+label {
            top: -20px;
            font-size: 12px;
        }

        .login-btn {
            padding: 12px;
            margin-top: 18px;
            font-size: 14px;
        }
    }

    .login-footer {
        position: fixed;
        bottom: 12px;
        right: 24px;
        color: rgba(255, 255, 255, .85);
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        pointer-events: none;
    }

    @media (max-width: 900px) {
        .login-footer {
            left: 50%;
            right: auto;
            transform: translateX(-50%);
        }
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-form">
            <img src="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/img/logo_branco.svg" alt="Login Form Logo" class="login-form-logo" />
            <div class="form-content">
                <form action="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/check_login.php" method="post" autocomplete="off">
                    <div class="input-container">
                        <input type="email" name="email_login" autocomplete="off" id="email_login" required />
                        <label for="email_login">Email</label>
                    </div>

                    <div class="input-container">
                        <input type="password" id="senha_login" autocomplete="off" name="senha_login" required />
                        <label for="senha_login">Senha</label>
                    </div>

                    <div class="login-links">
                        <a href="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/esqueci_senha.php">Esqueci minha senha</a>
                    </div>

                    <?php if (isset($_SESSION['login_attempts_notice']) && $_SESSION['login_attempts_notice'] !== "") { ?>
                    <div class="login-attempts-notice">
                        <?= htmlspecialchars((string)$_SESSION['login_attempts_notice'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['login_attempts_notice']); ?>
                    <?php } ?>

                    <input type="submit" value="Login" class="login-btn" />
                </form>

                <!-- Error message -->
                <?php if (isset($_SESSION['login_error']) && $_SESSION['login_error'] !== "") { ?>
                <div class="error-message">
                    <strong>Falha no login</strong>
                    <div><?= htmlspecialchars((string)$_SESSION['login_error'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php unset($_SESSION['login_error']); ?>
                <?php } ?>
            </div>
        </div>

        <div class="side-panel">
            <div class="side-panel-content">
                <img src="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/img/producao_preview.svg" alt="Preview do dashboard de producao" class="monitor-image" />
                <h3>Novidades!</h3>
                <p>Decisões melhores começam com dados claros. Veja internações e contas evoluindo em tempo real.

                    Mais visão, menos suposição: indicadores que conectam cuidado e eficiência.</p>
            </div>
        </div>
    </div>

    <div class="login-footer">
        Versão <?= htmlspecialchars($currentAppVersion) ?>
    </div>

    <script>
    // limpar os campos manualmente e evitar autocompletar
    document.addEventListener("DOMContentLoaded", () => {
        const emailInput = document.getElementById("email_login");
        const senhaInput = document.getElementById("senha_login");
        emailInput.value = "";
        senhaInput.value = "";
        setTimeout(() => {
            emailInput.value = "";
            senhaInput.value = "";
        }, 100);
    });

    // resetar campos após o submit
    document.querySelector("form").addEventListener("submit", function() {
        setTimeout(() => {
            this.reset();
        }, 100);
    });

    </script>
</body>

</html>
