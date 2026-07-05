<?php include_once __DIR__ . '/globals.php'; ?>
<!DOCTYPE html>
<?php $currentAppVersion = app_latest_version($conn); ?>
<html lang="pt-BR">
<?php $assetBase = rtrim($BASE_URL, '/'); ?>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FullCare</title>
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/img/full-ico.ico?v=<?= @filemtime(__DIR__ . '/img/full-ico.ico') ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/img/full-ico.ico?v=<?= @filemtime(__DIR__ . '/img/full-ico.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Inter', sans-serif;
        display: flex;
        min-height: 100vh;
        overflow: hidden;
        opacity: 0;
        animation: fadeIn .35s ease-in forwards;
        padding: clamp(18px, 2vw, 30px);
        background:
            radial-gradient(circle at 9% 12%, rgba(79, 172, 222, .14), transparent 28%),
            radial-gradient(circle at 92% 85%, rgba(107, 43, 116, .10), transparent 32%),
            #e9f0f7;
    }

    @keyframes fadeIn { to { opacity: 1; } }

    /* ── Left panel ─────────────────────────────────────── */
    .lp {
        flex: 0 0 61%;
        min-width: 0;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 44px 124px 36px 56px;
        overflow: hidden;
        z-index: 2;
        margin-right: -64px;
        border-radius: 28px 68px 68px 28px;
        filter:
            drop-shadow(12px 0 12px rgba(4, 14, 27, .55))
            drop-shadow(26px 0 28px rgba(4, 14, 27, .38))
            drop-shadow(54px 0 68px rgba(47, 111, 159, .32))
            drop-shadow(88px 0 110px rgba(47, 111, 159, .20));
        background:
            radial-gradient(ellipse at 12% 0%, rgba(95, 164, 208, .14) 0%, transparent 46%),
            radial-gradient(ellipse at 86% 100%, rgba(103, 70, 139, .26) 0%, transparent 54%),
            radial-gradient(ellipse at 18% 80%, rgba(55, 118, 168, .12) 0%, transparent 46%),
            linear-gradient(158deg, #0f2a42 0%, #244762 52%, #2b2f55 100%);
    }

    /* noise overlay */
    .lp::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='200' height='200' filter='url(%23n)' opacity='.035'/%3E%3C/svg%3E");
        pointer-events: none;
        z-index: 0;
    }

    .lp > * { position: relative; z-index: 1; }

    .lp-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-left: -4px;
    }

    .lp-logo img {
        height: 92px;
        width: auto;
        opacity: .9;
        filter: drop-shadow(0 16px 26px rgba(3, 16, 31, .26)) drop-shadow(0 0 1px rgba(255, 255, 255, .42));
    }

    .lp-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 36px 0 24px;
    }

    .lp-headline {
        font-size: clamp(32px, 3.4vw, 52px);
        font-weight: 800;
        line-height: 1.1;
        color: #fff;
        letter-spacing: -0.02em;
    }

    .lp-headline span {
        display: block;
        background: linear-gradient(90deg, #6fc0ec 0%, #8a78de 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .lp-sub {
        margin-top: 20px;
        font-size: 15px;
        font-weight: 400;
        color: rgba(200, 220, 240, .72);
        line-height: 1.6;
        max-width: 420px;
    }

    .lp-features {
        margin-top: 36px;
        display: flex;
        flex-direction: column;
        gap: 13px;
    }

    .lp-features li {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        font-weight: 500;
        color: rgba(210, 230, 248, .85);
        list-style: none;
    }

    .lp-features li::before {
        content: "";
        display: block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
        background: linear-gradient(135deg, #6fc0ec, #8a78de);
        box-shadow: 0 0 8px rgba(111, 192, 236, .34);
    }

    .lp-footer {
        font-size: 12px;
        color: rgba(180, 210, 235, .38);
        letter-spacing: .03em;
    }

    /* ── Right panel ────────────────────────────────────── */
    .rp {
        width: min(42%, 560px);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        background:
            linear-gradient(90deg, rgba(188, 211, 229, .92) 0%, rgba(218, 232, 243, .78) 13%, rgba(239, 246, 251, .92) 34%, #f5f8fc 62%, #f5f8fc 100%);
        margin-left: 0;
        padding: 48px 56px 48px 124px;
        position: relative;
        overflow: hidden;
        z-index: 1;
        border-radius: 0 28px 28px 0;
        box-shadow:
            0 24px 72px rgba(16, 35, 56, .18),
            inset 46px 0 58px rgba(55, 111, 151, .30),
            inset 18px 0 22px rgba(13, 32, 56, .18);
    }

    .rp-inner {
        width: 100%;
        max-width: 360px;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .rp-mark {
        width: 58px;
        height: 58px;
        display: grid;
        place-items: center;
        border-radius: 18px;
        margin-bottom: 24px;
        color: #2f6f9f;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(239, 247, 253, .96)),
            radial-gradient(circle at 30% 20%, rgba(95, 188, 226, .18), transparent 58%);
        border: 1px solid rgba(194, 211, 226, .86);
        box-shadow: 0 18px 42px rgba(47, 111, 159, .13);
    }

    .rp-mark svg {
        width: 26px;
        height: 26px;
        stroke: currentColor;
    }

    .rp-title {
        font-size: 26px;
        font-weight: 800;
        color: #0d2038;
        letter-spacing: -0.02em;
        text-align: center;
    }

    .rp-subtitle {
        margin-top: 6px;
        font-size: 13.5px;
        color: #7a8ea8;
        font-weight: 400;
        text-align: center;
        margin-bottom: 36px;
    }

    /* fields */
    .field-group {
        width: 100%;
        margin-bottom: 16px;
    }

    .field-group label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #5a7390;
        margin-bottom: 7px;
    }

    .field-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .field-wrap svg {
        position: absolute;
        left: 14px;
        color: #9db3c8;
        pointer-events: none;
        flex-shrink: 0;
    }

    .field-wrap input {
        width: 100%;
        padding: 13px 16px 13px 42px;
        background: #fff;
        border: 1.5px solid #d8e4ef;
        border-radius: 10px;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        color: #1a2d3e;
        outline: none;
        transition: border-color .18s, box-shadow .18s;
    }

    .field-wrap input::placeholder { color: #b0c4d6; }

    .field-wrap input:focus {
        border-color: #2f6f9f;
        box-shadow: 0 0 0 3px rgba(47, 111, 159, .12);
    }

    .field-wrap input:-webkit-autofill,
    .field-wrap input:-webkit-autofill:hover,
    .field-wrap input:-webkit-autofill:focus {
        -webkit-text-fill-color: #1a2d3e;
        box-shadow: 0 0 0 1000px #fff inset, 0 0 0 3px rgba(47, 111, 159, .12);
        transition: background-color 9999s ease 0s;
    }

    /* submit */
    .login-btn {
        width: 100%;
        margin-top: 10px;
        padding: 14px;
        background: linear-gradient(135deg, #2f6f9f 0%, #6b2b74 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        box-shadow: 0 10px 28px rgba(47, 111, 159, .28);
        transition: transform .18s, box-shadow .18s, filter .18s;
    }

    .login-btn:hover {
        filter: brightness(1.08);
        transform: translateY(-1px);
        box-shadow: 0 14px 34px rgba(47, 111, 159, .34);
    }

    /* forgot */
    .forgot {
        margin-top: 18px;
        text-align: center;
    }

    .forgot a {
        font-size: 13px;
        font-weight: 600;
        color: #2f6f9f;
        text-decoration: none;
        transition: color .15s;
    }

    .forgot a:hover { color: #6b2b74; text-decoration: underline; }

    /* login attempts */
    .login-attempts-notice {
        margin-top: 14px;
        padding: 10px 13px;
        border-radius: 9px;
        background: #fff8e6;
        border: 1px solid #f1d894;
        border-left: 4px solid #d6a82d;
        color: #705519;
        font-size: 12px;
        line-height: 1.4;
        text-align: center;
        width: 100%;
    }

    /* error toast */
    .error-message {
        position: fixed;
        bottom: 26px;
        left: 50%;
        transform: translateX(-50%);
        width: min(88vw, 420px);
        padding: 12px 14px 12px 16px;
        background: rgba(255, 255, 255, .97);
        border: 1px solid rgba(180, 55, 55, .18);
        border-left: 4px solid #b43737;
        border-radius: 12px;
        box-shadow: 0 18px 42px rgba(72, 48, 58, .18);
        color: #673030;
        font-size: 13px;
        line-height: 1.4;
        animation: fadeIn .3s ease-in-out;
        z-index: 1000;
    }

    .error-message strong {
        display: block;
        font-size: 11px;
        letter-spacing: .03em;
        margin-bottom: 3px;
        color: #9f2f2f;
        text-transform: uppercase;
    }

    .error-message.hide {
        opacity: 0;
        transform: translate(-50%, 12px);
        transition: opacity .35s ease, transform .35s ease;
    }

    /* version badge */
    .version-badge {
        position: absolute;
        bottom: 16px;
        right: 20px;
        font-size: 11px;
        color: #b0c4d6;
        letter-spacing: .04em;
    }

    /* ── Responsive ─────────────────────────────────────── */
    @media (max-width: 860px) {
        body {
            padding: 0;
            background: #f5f8fc;
        }

        .lp { display: none; }
        .rp {
            width: 100%;
            min-height: 100vh;
            margin-left: 0;
            padding: 48px 28px;
            border-radius: 0;
        }
    }

    @media (max-width: 480px) {
        .rp { padding: 40px 20px; }
        .rp-inner { max-width: 100%; }
    }
    </style>
</head>

<body>
    <svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute">
        <defs>
            <clipPath id="loginCurveClip" clipPathUnits="objectBoundingBox">
                <path d="M0 0 H1 C0.94 0.20 0.94 0.80 1 1 H0 Z"></path>
            </clipPath>
        </defs>
    </svg>

    <!-- ── Left panel ── -->
    <div class="lp">
        <div class="lp-logo">
            <img src="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/img/logo_branco.svg" alt="FullCare" />
        </div>

        <div class="lp-body">
            <h1 class="lp-headline">
                Gestão em saúde
                <span>inteligente</span>
            </h1>
            <p class="lp-sub">Centralize internações, auditorias e equipe. IA integrada para análise clínica e acompanhamento em tempo real.</p>
            <ul class="lp-features">
                <li>Jornada de internação com acompanhamento passo a passo</li>
                <li>IA para análise e resumo clínico de pacientes</li>
                <li>BI em tempo real: custos, qualidade e indicadores</li>
                <li>Auditoria médica integrada com alertas automáticos</li>
            </ul>
        </div>

        <div class="lp-footer">
            &copy; <?= date('Y') ?> FullCare &middot; Sistema de Gestão em Saúde
        </div>
    </div>

    <!-- ── Right panel ── -->
    <div class="rp">
        <div class="rp-inner">
            <div class="rp-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </div>

            <h2 class="rp-title">Bem-vindo</h2>
            <p class="rp-subtitle">Acesse sua conta para continuar</p>

            <form action="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/check_login.php" method="post" autocomplete="off" class="login-form">

                <div class="field-group">
                    <label for="email_login">E-mail</label>
                    <div class="field-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input type="email" name="email_login" id="email_login" autocomplete="off" placeholder="seu@email.com" required />
                    </div>
                </div>

                <div class="field-group">
                    <label for="senha_login">Senha</label>
                    <div class="field-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" name="senha_login" id="senha_login" autocomplete="off" placeholder="••••••••" required />
                    </div>
                </div>

                <?php if (isset($_SESSION['login_attempts_notice']) && $_SESSION['login_attempts_notice'] !== "") { ?>
                <div class="login-attempts-notice">
                    <?= htmlspecialchars((string)$_SESSION['login_attempts_notice'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php unset($_SESSION['login_attempts_notice']); ?>
                <?php } ?>

                <input type="submit" value="Entrar" class="login-btn" />
            </form>

            <div class="forgot">
                <a href="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/esqueci_senha.php">Esqueci minha senha</a>
            </div>

            <?php if (isset($_SESSION['login_error']) && $_SESSION['login_error'] !== "") { ?>
            <div class="error-message">
                <strong>Falha no login</strong>
                <div><?= htmlspecialchars((string)$_SESSION['login_error'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php unset($_SESSION['login_error']); ?>
            <?php } ?>
        </div>

        <div class="version-badge">v<?= htmlspecialchars($currentAppVersion) ?></div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const email = document.getElementById("email_login");
        const senha = document.getElementById("senha_login");
        email.value = "";
        senha.value = "";
        setTimeout(() => { email.value = ""; senha.value = ""; }, 100);
    });

    document.querySelector("form").addEventListener("submit", function() {
        setTimeout(() => { this.reset(); }, 100);
    });
    </script>
</body>
</html>
