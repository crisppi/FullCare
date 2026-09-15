<?php

// A lista usa o caminho do script executado, nunca parâmetros enviados pelo cliente.
function fullcare_is_public_session_route(string $scriptFile): bool
{
    $root = dirname(__DIR__, 2) . '/';
    $scriptFile = realpath($scriptFile) ?: $scriptFile;
    if (strpos($scriptFile, $root) !== 0) return false;
    $relative = substr($scriptFile, strlen($root));
    return in_array($relative, [
        'index.php', 'index_novo.php', 'check_login.php', 'logout.php', 'destroi.php',
        'esqueci_senha.php', 'redefinir_senha.php',
        'mfa_verify.php', 'process_mfa_verify.php',
        'process_recuperar_senha.php', 'process_redefinir_senha.php',
        'pages/process/process_recuperar_senha.php',
        'pages/process/process_redefinir_senha.php',
    ], true);
}

function fullcare_session_authenticated(array $session): bool
{
    return (int)($session['id_usuario'] ?? 0) > 0
        && strtolower(trim((string)($session['ativo'] ?? ''))) === 's';
}

function enforce_authenticated_session(string $baseUrl): void
{
    if (fullcare_session_authenticated($_SESSION)) return;

    header('Cache-Control: no-store, private');
    $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
        || strpos(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json') !== false
        || strpos(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json') !== false;
    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => !empty($_SESSION['session_expired']) ? 'session_expired' : 'error',
            'message' => 'Não autenticado.',
            'redirect' => rtrim($baseUrl, '/') . '/index.php',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: ' . rtrim($baseUrl, '/') . '/index.php', true, 303);
    exit;
}
