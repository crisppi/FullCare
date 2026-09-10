<?php

if (!defined('APP_VERSION_DEFAULT')) {
    define('APP_VERSION_DEFAULT', '1.5');
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', APP_VERSION_DEFAULT);
}

if (!function_exists('app_latest_version')) {
    function app_latest_version(PDO $conn): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $cached = APP_VERSION;

        try {
            $stmt = $conn->query("
                SELECT version
                  FROM schema_version
                 WHERE version REGEXP '^v?[0-9]+([.][0-9]+){1,3}$'
                 ORDER BY applied_at DESC, id DESC
                 LIMIT 1
            ");
            $version = $stmt->fetchColumn();
            if ($version) {
                $cached = $version;
            }
        } catch (Throwable $e) {
            error_log('[VERSION] ' . $e->getMessage());
        }

        return $cached;
    }
}
