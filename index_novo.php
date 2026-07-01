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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= $assetBase ?>/css/login_modern.css?v=<?= @filemtime(__DIR__ . '/css/login_modern.css') ?>" rel="stylesheet">
</head>

<body>
    <!-- ── Left panel ── -->
    <div class="lp">
        <div class="lp-logo">
            <img src="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/img/logo_branco.png" alt="FullCare" />
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
            <img src="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/img/LogoFullCare.png" alt="FullCare" class="rp-logo" />

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
