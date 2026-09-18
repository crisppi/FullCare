<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/security/session_idle.php';
require_once __DIR__ . '/../app/auth_session.php';
function idleAssert(bool $ok, string $label): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $label\n"); exit(1); }
    echo "OK: $label\n";
}
$now = 2000000000;
$session = ['id_usuario' => 42, 'idle_last_activity' => $now];
idleAssert(!fullcare_idle_expired($session, $now + 1199), 'Sessão válida antes de 20 minutos');
idleAssert(fullcare_idle_expired($session, $now + 1200), 'Expira exatamente aos 20 minutos');
idleAssert(FULLCARE_IDLE_WARNING_SECONDS === 300, 'Aviso nos últimos 5 minutos');
$_SESSION = $session + ['email_user' => 'fixture@example.test', 'ativo' => 's', 'csrf' => 'fixture'];
fullcare_idle_check($now + 1200);
idleAssert(!isset($_SESSION['id_usuario'], $_SESSION['email_user']), 'Expiração remove autenticação e identidade');
idleAssert(!empty($_SESSION['session_expired']), 'Expiração identificada para o cliente');
fullcare_idle_check($now + 1201);
idleAssert(!isset($_SESSION['idle_last_activity']), 'Atividade tardia não reativa sessão');
$_SESSION = ['id_usuario' => 42, 'ativo' => 's'];
fullcare_idle_check($now);
idleAssert($_SESSION['idle_last_activity'] === $now, 'Sessões antigas inicializadas uma vez');
fullcare_idle_check($now + 900);
idleAssert($_SESSION['idle_last_activity'] === $now, 'Consulta automática não renova prazo');
idleAssert(fullcare_idle_status($now + 900)['expiresAt'] - ($now + 900) === 300, 'Contagem regressiva em 5:00');
idleAssert(!fullcare_idle_is_navigation(['HTTP_SEC_FETCH_MODE' => 'cors']), 'Fetch não conta como atividade');
idleAssert(!fullcare_idle_is_navigation(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'text/html']), 'Polling AJAX não renova sessão');
idleAssert(fullcare_idle_is_navigation(['HTTP_SEC_FETCH_MODE' => 'navigate']), 'Navegação conta como atividade');
fullcare_login_session_start(['id_usuario' => 42, 'ativo_user' => 's']);
idleAssert(abs(time() - $_SESSION['idle_last_activity']) < 2 && !isset($_SESSION['session_expired']), 'Novo login recebe 20 minutos');
fullcare_login_session_clear();
idleAssert(!isset($_SESSION['idle_last_activity'], $_SESSION['idle_channel']), 'Logout limpa relógio');

$_SESSION = [];
fullcare_idle_check($now);
idleAssert(!isset($_SESSION['idle_last_activity']), 'Tela sem login não inicia contador');
$_SESSION = $session;
fullcare_idle_check($now + 1200, false);
idleAssert(!isset($_SESSION['id_usuario']) && !fullcare_idle_take_notice($now + 1200), 'Entrada pública limpa sessão antiga sem aviso');
$_SESSION = $session;
fullcare_idle_check($now + 1200);
idleAssert(!isset($_SESSION['login_error']) && fullcare_idle_take_notice($now + 1200), 'Expiração durante uso gera aviso separado de falha de login');
idleAssert(!fullcare_idle_take_notice($now + 1201), 'Aviso consumido uma única vez');
$_SESSION = ['idle_notice_at'=>$now];
idleAssert(!fullcare_idle_take_notice($now + 61), 'Aviso antigo não reaparece');
$_SESSION = ['login_error'=>'Sua sessão foi encerrada após 20 minutos de inatividade. Entre novamente.'];
fullcare_idle_take_notice($now);
idleAssert(!isset($_SESSION['login_error']), 'Aviso legado não aparece como falha de login');
$_SESSION = ['login_error'=>'Senha incorreta'];
fullcare_idle_take_notice($now);
idleAssert($_SESSION['login_error']==='Senha incorreta', 'Falha real de login preservada');
