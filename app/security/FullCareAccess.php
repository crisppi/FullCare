<?php

declare(strict_types=1);

final class FullCareAccess
{
    private static array $profileCache = [];
    private static array $permissionCache = [];

    public static function profile(PDO $conn, ?int $userId = null): ?array
    {
        $userId = $userId ?? (int)($_SESSION['id_usuario'] ?? 0);
        if ($userId <= 0) return null;
        if (array_key_exists($userId, self::$profileCache)) return self::$profileCache[$userId];

        $stmt = $conn->prepare("SELECT p.id_access_profile, p.nome, p.slug, p.ativo, p.protegido
            FROM tb_user u
            LEFT JOIN tb_access_profile p ON p.id_access_profile = u.fk_access_profile
            WHERE u.id_usuario = :id
            LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        self::$profileCache[$userId] = $profile;
        return $profile;
    }

    public static function isSuperAdmin(PDO $conn, ?int $userId = null): bool
    {
        $profile = self::profile($conn, $userId);
        return is_array($profile)
            && (int)($profile['ativo'] ?? 0) === 1
            && ($profile['slug'] ?? '') === 'superadministrador';
    }

    public static function can(PDO $conn, string $module, string $action, ?int $userId = null): bool
    {
        $userId = $userId ?? (int)($_SESSION['id_usuario'] ?? 0);
        $module = self::normalizeKey($module);
        $action = self::normalizeAction($action);
        if ($userId <= 0 || $module === '' || $action === '') return false;

        $active = strtolower(trim((string)($_SESSION['ativo'] ?? '')));
        if ($userId === (int)($_SESSION['id_usuario'] ?? 0) && $active !== 's') return false;

        $profile = self::profile($conn, $userId);
        if (!$profile || (int)($profile['ativo'] ?? 0) !== 1) return false;
        if (($profile['slug'] ?? '') === 'superadministrador') return true;

        $cacheKey = $userId . ':' . $module . ':' . $action;
        if (array_key_exists($cacheKey, self::$permissionCache)) {
            return self::$permissionCache[$cacheKey];
        }

        $override = $conn->prepare("SELECT allowed
            FROM tb_user_permission_override
            WHERE user_id = :uid AND module = :module AND action = :action
            LIMIT 1");
        $override->execute([':uid' => $userId, ':module' => $module, ':action' => $action]);
        $overrideValue = $override->fetchColumn();
        if ($overrideValue !== false) {
            return self::$permissionCache[$cacheKey] = ((int)$overrideValue === 1);
        }

        $stmt = $conn->prepare("SELECT allowed
            FROM tb_access_profile_permission
            WHERE profile_id = :profile AND module = :module AND action = :action
            LIMIT 1");
        $stmt->execute([
            ':profile' => (int)$profile['id_access_profile'],
            ':module' => $module,
            ':action' => $action,
        ]);
        return self::$permissionCache[$cacheKey] = ((int)($stmt->fetchColumn() ?: 0) === 1);
    }

    public static function any(PDO $conn, string $module, array $actions, ?int $userId = null): bool
    {
        foreach ($actions as $action) {
            if (self::can($conn, $module, (string)$action, $userId)) return true;
        }
        return false;
    }

    public static function enforce(PDO $conn, string $baseUrl, string $module, string $action): void
    {
        if (self::can($conn, $module, $action)) return;
        self::deny($baseUrl, $module, $action);
    }

    public static function enforceCurrentRequest(PDO $conn, string $baseUrl): void
    {
        $access = self::currentRequestAccess();
        if ($access === null) return;
        self::enforce($conn, $baseUrl, $access['module'], $access['action']);
    }

    public static function currentRequestAccess(): ?array
    {
        $script = strtolower(basename((string)($_SERVER['SCRIPT_NAME'] ?? '')));
        $uri = strtolower((string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: ''));
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if (self::isPublicScript($script)) return null;

        $module = self::detectModule($script, $uri);
        if ($module === null) return null;
        $action = self::detectAction($script, $uri, $method);
        return ['module' => $module, 'action' => $action];
    }

    public static function moduleForCurrentRequest(): ?string
    {
        return self::currentRequestAccess()['module'] ?? null;
    }

    private static function detectModule(string $script, string $uri): ?string
    {
        $haystack = $uri . ' ' . $script;
        $rules = [
            'permissoes' => ['administracao/permissoes', 'permiss', 'access_profile'],
            'usuarios' => ['/usuarios', 'usuario', 'hospitaluser', 'reset_senha'],
            'altas' => ['gerar-alta', 'gerar_alta', 'reverter-alta', 'process_alta', 'edit_alta', 'internacao_alta', 'alta_reverter'],
            'contas' => ['/contas', 'capeante', 'process_rah', '_rah', '/negociacoes', 'negociacoes_', 'faturamento'],
            'visitas' => ['/visitas', 'visita'],
            'censo' => ['/censo', 'censo'],
            'pacientes' => ['/pacientes', 'paciente', 'hub_paciente'],
            'hospitais' => ['/hospitais', 'hospital', 'acomodacao'],
            'seguradoras' => ['/seguradoras', 'seguradora'],
            'estipulantes' => ['/estipulantes', 'estipulante'],
            'internacoes' => ['/internacoes', 'internacao', 'pdf_intern', 'prorrogacao', 'evento_adverso', 'tuss'],
            'gestao' => ['/gestao', 'gestao', 'fila_tarefas', 'pendencias_operacionais'],
            'cuidado_continuado' => ['cuidado-continuado', 'home_care', 'longa_permanencia'],
            'bi_estrategico' => ['/inteligencia', 'dashboard_operacional', 'dashboard_performance', 'faturamento_previsao', '/producao/ia-clinica'],
            'bi_operacional' => ['/bi/', 'bi_', 'bi.php', '/relatorios/operacionais'],
            'cadastros_clinicos' => ['patologia', 'antecedente'],
            'solicitacoes' => ['solicitacao', '/solicitacoes/'],
            'dashboard' => ['/dashboard', '/inicio', '/menu', 'menu_app', 'central-trabalho', 'central-de-trabalho'],
        ];
        foreach ($rules as $module => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) return $module;
            }
        }
        return null;
    }

    private static function detectAction(string $script, string $uri, string $method): string
    {
        $type = strtolower(trim((string)($_POST['type'] ?? '')));
        $haystack = $uri . ' ' . $script;

        if (str_contains($haystack, 'reverter-alta') || str_contains($script, 'alta_reverter') || $script === 'list_internacao_alta.php') return 'revert_discharge';
        if (str_contains($haystack, 'gerar-alta') || in_array($script, ['process_alta.php', 'process_alta_uti.php', 'process_gerar_altas.php', 'list_internacao_gerar_alta.php', 'edit_alta.php', 'edit_alta_uti.php'], true)) return 'discharge';
        if (str_contains($script, '_pdf') || str_contains($uri, '/pdf')) return 'generate_pdf';
        if (str_contains($haystack, 'permissoes') || str_contains($haystack, 'access_profile')) return 'manage';
        if (in_array($type, ['delete', 'delupdate', 'destroy', 'remove'], true)) return 'delete';
        if (in_array($type, ['update', 'edit', 'editar', 'alta_uti'], true)) return 'edit';
        if (in_array($type, ['create', 'insert', 'store', 'gerar_altas'], true)) return 'create';
        if (str_contains($haystack, 'fechar-gestao')) return 'close_management';

        if ($method === 'GET' || $method === 'HEAD') {
            if (preg_match('/(^|_)(cad|novo|new)(_|\.|$)/', $script) || preg_match('#/(novo|nova)(/|$)#', $uri)) return 'create';
            if (preg_match('/(^|_)(edit|editar)(_|\.|$)/', $script) || str_contains($uri, '/editar/')) return 'edit';
            if (preg_match('/(^|_)(del|delete|destroy)(_|\.|$)/', $script)) return 'delete';
            return 'view';
        }

        if (preg_match('/(delete|destroy|remover|excluir)/', $script)) return 'delete';
        if (preg_match('/(update|edit|editar)/', $script)) return 'edit';
        return 'create';
    }

    private static function normalizeKey(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/[^a-z0-9_]/', '_', $value) ?? '';
    }

    private static function normalizeAction(string $action): string
    {
        $action = self::normalizeKey($action);
        $map = [
            'view' => 'view', 'visualizar' => 'view',
            'create' => 'create', 'criar' => 'create',
            'edit' => 'edit', 'editar' => 'edit',
            'delete' => 'delete', 'excluir' => 'delete',
            'discharge' => 'discharge', 'alta' => 'discharge', 'dar_alta' => 'discharge',
            'revert_discharge' => 'revert_discharge', 'reverter_alta' => 'revert_discharge',
            'close_management' => 'close_management', 'fechar_gestao' => 'close_management',
            'generate_pdf' => 'generate_pdf', 'gerar_pdf' => 'generate_pdf', 'pdf' => 'generate_pdf',
            'manage' => 'manage',
        ];
        return $map[$action] ?? $action;
    }

    private static function isPublicScript(string $script): bool
    {
        return in_array($script, [
            'index.php', 'index_novo.php', 'check_login.php', 'logout.php', 'destroi.php',
            'process_mfa_verify.php', 'process_recuperar_senha.php', 'process_redefinir_senha.php',
            'nova_senha.php', 'process_mfa_configuracao.php', 'mfa_configuracao.php',
        ], true);
    }

    private static function deny(string $baseUrl, string $module, string $action): void
    {
        $message = 'Seu nível de acesso não permite esta operação.';
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || str_contains($accept, 'application/json')
            || str_contains(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json');

        http_response_code(403);
        self::auditDenied($GLOBALS['conn'] ?? null, $module, $action);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['mensagem'] = $message;
        $_SESSION['mensagem_tipo'] = 'danger';
        header('Location: ' . rtrim($baseUrl, '/') . '/inicio', true, 303);
        exit;
    }

    private static function auditDenied($conn, string $module, string $action): void
    {
        if (!$conn instanceof PDO) return;
        try {
            $stmt = $conn->prepare("INSERT INTO tb_access_audit
                (actor_user_id, target_user_id, evento, valor_novo, motivo, ip)
                VALUES (:actor, :target, 'acesso_negado', :value, :reason, :ip)");
            $uid = (int)($_SESSION['id_usuario'] ?? 0) ?: null;
            $stmt->execute([
                ':actor' => $uid,
                ':target' => $uid,
                ':value' => json_encode(['module' => $module, 'action' => $action], JSON_UNESCAPED_UNICODE),
                ':reason' => 'Bloqueio aplicado pela autorização central.',
                ':ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
        } catch (Throwable $e) {
            error_log('[ACCESS][AUDIT_DENIED] ' . $e->getMessage());
        }
    }
}
