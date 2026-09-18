<?php

const FULLCARE_IDLE_SECONDS = 20 * 60;
const FULLCARE_IDLE_WARNING_SECONDS = 5 * 60;

// Não depende do banco; chamadas automáticas nunca renovam este relógio.
function fullcare_idle_expired(array $session, int $now): bool
{
    $last = (int)($session['idle_last_activity'] ?? 0);
    return (int)($session['id_usuario'] ?? 0) > 0
        && $last > 0 && $now - $last >= FULLCARE_IDLE_SECONDS;
}

function fullcare_idle_is_navigation(array $server): bool
{
    if (strtolower((string)($server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') return false;
    $mode = strtolower((string)($server['HTTP_SEC_FETCH_MODE'] ?? ''));
    if ($mode !== '') return $mode === 'navigate';
    return strpos(strtolower((string)($server['HTTP_ACCEPT'] ?? '')), 'text/html') !== false;
}

function fullcare_idle_check(int $now, bool $notify = true): void
{
    if (fullcare_idle_expired($_SESSION, $now)) {
        // Limpa também email/nome, evitando reidratação de uma sessão expirada.
        $_SESSION = [
            'session_expired' => true,
            'idle_notice_at' => $notify ? $now : 0,
        ];
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) session_regenerate_id(true);
        return;
    }
    if ((int)($_SESSION['id_usuario'] ?? 0) <= 0) return;
    // Sessões anteriores à implantação recebem o prazo completo uma única vez.
    if (empty($_SESSION['idle_last_activity'])) $_SESSION['idle_last_activity'] = $now;
    if (empty($_SESSION['idle_channel'])) $_SESSION['idle_channel'] = bin2hex(random_bytes(16));
}

function fullcare_idle_status(int $now): array
{
    return [
        'status' => 'ok',
        'serverTime' => $now,
        'expiresAt' => (int)$_SESSION['idle_last_activity'] + FULLCARE_IDLE_SECONDS,
        'warningSeconds' => FULLCARE_IDLE_WARNING_SECONDS,
    ];
}

// Aviso informativo de uso único; não reaparece numa visita posterior ao login.
function fullcare_idle_take_notice(int $now): bool
{
    $issued = (int)($_SESSION['idle_notice_at'] ?? 0);
    unset($_SESSION['idle_notice_at']);
    // Compatibilidade com sessões criadas antes da separação dos avisos.
    if (($_SESSION['login_error'] ?? '') === 'Sua sessão foi encerrada após 20 minutos de inatividade. Entre novamente.') {
        unset($_SESSION['login_error']);
    }
    return $issued > 0 && $issued <= $now && $now - $issued <= 60;
}
