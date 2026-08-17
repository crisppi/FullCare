<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('fullcare_login_index_url')) {
    function fullcare_login_index_url(): string
    {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if (preg_match('#^/(FullCare|FullConex(?:Aud)?)(/|$)#i', $script, $m)) {
            return '/' . trim($m[1], '/') . '/index.php';
        }
        return '/index.php';
    }
}

require_once(__DIR__ . "/app/security/bi_access.php");
require_once(__DIR__ . "/app/security/inteligencia_access.php");
require_once(__DIR__ . "/app/schemaEnsurer.php");
require_once(__DIR__ . "/app/mfa.php");
require_once(__DIR__ . "/db.php");
require_once(__DIR__ . "/app/security/FullCareAccess.php");

if (empty($_SESSION['email_user']) && empty($_SESSION['id_usuario'])) {
    header('Location: ' . fullcare_login_index_url(), true, 303);
    exit;
}

// Reidrata a sessão antes de validar o status. Páginas antigas chamam este
// arquivo antes de globals.php e sessões anteriores podem não ter os campos
// novos, embora o usuário continue autenticado e ativo no banco.
$sessionUserId = (int)($_SESSION['id_usuario'] ?? 0);
if ($sessionUserId > 0) {
    try {
        $sessionStmt = $conn->prepare("SELECT usuario_user, email_user, login_user, ativo_user,
            nivel_user, cargo_user, foto_usuario, fk_access_profile, fk_seguradora_user
            FROM tb_user WHERE id_usuario = :id LIMIT 1");
        $sessionStmt->execute([':id' => $sessionUserId]);
        $sessionDbUser = $sessionStmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($sessionDbUser)) {
            $_SESSION['usuario_user'] = (string)$sessionDbUser['usuario_user'];
            $_SESSION['email_user'] = (string)$sessionDbUser['email_user'];
            $_SESSION['login_user'] = (string)$sessionDbUser['login_user'];
            $_SESSION['ativo'] = (string)$sessionDbUser['ativo_user'];
            $_SESSION['nivel'] = (int)$sessionDbUser['nivel_user'];
            $_SESSION['cargo'] = (string)$sessionDbUser['cargo_user'];
            $_SESSION['foto_usuario'] = (string)$sessionDbUser['foto_usuario'];
            $_SESSION['fk_access_profile'] = $sessionDbUser['fk_access_profile'] !== null
                ? (int)$sessionDbUser['fk_access_profile'] : null;
            $_SESSION['fk_seguradora_user'] = $sessionDbUser['fk_seguradora_user'] !== null
                ? (int)$sessionDbUser['fk_seguradora_user'] : null;
            $_SESSION['user_db_synced_at'] = time();
        } else {
            $_SESSION['ativo'] = 'n';
        }
    } catch (Throwable $e) {
        error_log('[SESSION][CHECK_LOGADO] ' . $e->getMessage());
    }
}

$ativoRaw = (string)($_SESSION['ativo'] ?? '');
$ativoNorm = strtolower(trim($ativoRaw));
$ativoOk = in_array($ativoNorm, ['s', '1', 'true', 'ativo'], true);
if (!$ativoOk) {
    $erro_login = "Usuário inativo";
    $_SESSION['mensagem'] = $erro_login;
    header('Location: ' . fullcare_login_index_url(), true, 303);
    exit;
} else {
};

if (!function_exists('fullcare_mfa_config_url')) {
    function fullcare_mfa_config_url(): string
    {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if (preg_match('#^/(FullCare|FullConex(?:Aud)?)(/|$)#i', $script, $m)) {
            return '/' . trim($m[1], '/') . '/mfa_configuracao.php';
        }
        return '/mfa_configuracao.php';
    }
}

if (!function_exists('fullcare_require_mfa_setup')) {
    function fullcare_require_mfa_setup(PDO $conn): void
    {
        $requireMfaSetup = getenv('FULLCARE_REQUIRE_MFA_SETUP');
        $requireMfaSetup = $requireMfaSetup === false ? '' : strtolower(trim((string)$requireMfaSetup));
        if (!in_array($requireMfaSetup, ['1', 'true', 'on', 'yes', 's', 'sim'], true)) {
            return;
        }

        $scriptBase = strtolower(basename((string)($_SERVER['SCRIPT_NAME'] ?? '')));
        $allowed = [
            'mfa_configuracao.php',
            'process_mfa_configuracao.php',
            'process_mfa_verify.php',
            'destroi.php',
            'logout.php',
            'nova_senha.php',
            'process_recuperar_senha.php',
            'process_redefinir_senha.php',
        ];

        if (in_array($scriptBase, $allowed, true)) {
            return;
        }
        if ($scriptBase === 'process_usuario.php' && strtolower((string)($_POST['type'] ?? '')) === 'update-senha') {
            return;
        }

        $userId = (int)($_SESSION['id_usuario'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        ensure_user_mfa_schema($conn);
        $user = fullcare_mfa_fetch_user($conn, $userId);
        if (function_exists('fullcare_mfa_local_bypass_allowed') && fullcare_mfa_local_bypass_allowed($user)) {
            return;
        }
        if (is_array($user) && fullcare_mfa_user_enabled($user)) {
            return;
        }

        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || strpos($accept, 'application/json') !== false
            || strpos(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== false;

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'mfa_required',
                'message' => 'Configure o MFA para continuar.',
                'redirect' => fullcare_mfa_config_url(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ' . fullcare_mfa_config_url(), true, 303);
        exit;
    }
}

fullcare_require_mfa_setup($conn);

if (function_exists('fullcare_enforce_bi_access')) {
    fullcare_enforce_bi_access();
}
if (function_exists('fullcare_enforce_inteligencia_access')) {
    fullcare_enforce_inteligencia_access();
}

require_once(__DIR__ . "/utils/flow_logger.php");
if (function_exists('flowLog')) {
    $accessCtx = [
        'flow' => 'page_access',
        'trace_id' => $_SERVER['UNIQUE_ID'] ?? substr(md5((string)microtime(true) . (string)($_SESSION['id_usuario'] ?? '0')), 0, 16),
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'session_user_id' => $_SESSION['id_usuario'] ?? null,
        'session_user_name' => $_SESSION['usuario_user'] ?? ($_SESSION['login_user'] ?? ($_SESSION['email_user'] ?? null)),
        'ts' => date('c')
    ];
    flowLog($accessCtx, 'page.access', 'INFO', [
        'script' => basename((string)($_SERVER['SCRIPT_NAME'] ?? '')),
        'query_string' => $_SERVER['QUERY_STRING'] ?? null
    ]);
}
