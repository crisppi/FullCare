<?php

// Executar: php scripts/validate_session_guard.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../app/security/session_guard.php';

function verifySessionGuard(bool $result, string $label): void
{
    if (!$result) {
        fwrite(STDERR, "FAIL: $label\n");
        exit(1);
    }
    echo "OK: $label\n";
}

foreach ([
    [],
    ['email_user' => 'user@example.test', 'ativo' => 's'],
    ['id_usuario' => 0, 'ativo' => 's'],
    ['id_usuario' => -1, 'ativo' => 's'],
    ['id_usuario' => 42],
    ['id_usuario' => 42, 'ativo' => 'n'],
    ['mfa_pending_user_id' => 42, 'ativo' => 's'],
] as $index => $session) {
    verifySessionGuard(!fullcare_session_authenticated($session), "Sessão inválida $index bloqueada");
}
verifySessionGuard(fullcare_session_authenticated(['id_usuario' => 42, 'ativo' => 's']), 'Sessão ativa permitida');
verifySessionGuard(fullcare_session_authenticated(['id_usuario' => '42', 'ativo' => ' S ']), 'Status normalizado');

$root = dirname(__DIR__) . '/';
foreach (['index.php', 'check_login.php', 'esqueci_senha.php', 'redefinir_senha.php',
    'mfa_verify.php', 'process_mfa_verify.php', 'process_recuperar_senha.php',
    'process_redefinir_senha.php', 'pages/process/process_redefinir_senha.php'] as $route) {
    verifySessionGuard(fullcare_is_public_session_route($root . $route), "Fluxo público: $route");
}
foreach (['cad_censo.php', 'dashboard.php', 'nova_senha.php', 'mfa_configuracao.php',
    'process_usuario.php', 'export_capeante_pdf.php', 'pages/show/show_capeante.php',
    'pages/index.php', 'ajax/index.php'] as $route) {
    verifySessionGuard(!fullcare_is_public_session_route($root . $route), "Rota privada: $route");
}
verifySessionGuard(!fullcare_is_public_session_route('/tmp/index.php'), 'Mesmo nome fora da aplicação não libera acesso');

// A função de bloqueio deve retornar sem saída para quem já está autenticado.
$_SESSION = ['id_usuario' => 42, 'ativo' => 's'];
enforce_authenticated_session('http://localhost:8081/FullCare/');
verifySessionGuard(true, 'Guard preserva sessão autenticada');
