<?php

if (!function_exists('db_env_value')) {
    function db_env_value(string $key): ?string
    {
        $value = getenv($key);
        if ($value === false) {
            return null;
        }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}

if (!function_exists('db_load_env_file')) {
    function db_load_env_file(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            if ($key === '') {
                continue;
            }
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Carrega .env local sem sobrescrever variaveis ja exportadas no ambiente.
db_load_env_file(__DIR__ . '/.env');

$fonte_conexao = '';
$profiles = [];

foreach (['', '_2', '_3'] as $suffix) {
    $host = db_env_value('DB_HOST' . $suffix);
    $name = db_env_value('DB_NAME' . $suffix);
    $user = db_env_value('DB_USER' . $suffix);
    $pass = db_env_value('DB_PASS' . $suffix) ?? '';
    $port = (int)(db_env_value('DB_PORT' . $suffix) ?? '3306');
    $charset = db_env_value('DB_CHARSET' . $suffix) ?? 'utf8mb4';
    $label = db_env_value('DB_LABEL' . $suffix) ?? ('Profile' . ($suffix === '' ? '1' : str_replace('_', '', $suffix)));

    if ($host && $name && $user) {
        $profiles[] = [
            'host' => $host,
            'name' => $name,
            'user' => $user,
            'pass' => $pass,
            'port' => $port > 0 ? $port : 3306,
            'charset' => $charset,
            'label' => $label,
        ];
    }
}

if (!$profiles) {
    error_log('[DB] Nenhum profile DB configurado. Defina DB_HOST/DB_NAME/DB_USER/DB_PASS (e opcionais _2/_3).');
    header("Location: sem_conexao.html");
    exit("Falha na conexao com banco: configuracao ausente.");
}

$conn = null;
$errors = [];

foreach ($profiles as $profile) {
    try {
        $dsn = "mysql:host={$profile['host']};port={$profile['port']};dbname={$profile['name']};charset={$profile['charset']}";
        $conn = new PDO($dsn, $profile['user'], $profile['pass']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $fonte_conexao = "{$profile['label']} ({$profile['name']})";
        break;
    } catch (Throwable $e) {
        $errors[] = "{$profile['label']}: " . $e->getMessage();
    }
}

if (!$conn) {
    error_log('[DB] Falha em todos os profiles: ' . implode(' | ', $errors));
    header("Location: sem_conexao.html");
    exit("Falha na conexao com banco.");
}

try {
    $userId = $_SESSION['id_usuario'] ?? null;
    $userName = $_SESSION['usuario_user'] ?? null;
    $userEmail = $_SESSION['email_user'] ?? null;
    $ipAddr = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare(
        "SET @app_user_id = :uid,
             @app_user_nome = :uname,
             @app_user_email = :uemail,
             @app_ip = :ip,
             @app_user_agent = :ua"
    );
    $stmt->bindValue(':uid', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':uname', $userName);
    $stmt->bindValue(':uemail', $userEmail);
    $stmt->bindValue(':ip', $ipAddr);
    $stmt->bindValue(':ua', $userAgent);
    $stmt->execute();
} catch (Throwable $e) {
    // Se falhar, os triggers ainda registram sem contexto de usuario.
}
