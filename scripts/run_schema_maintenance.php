<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script só pode ser executado pela linha de comando.\n");
}

$appRoot = dirname(__DIR__);
require_once $appRoot . '/db.php';
require_once $appRoot . '/app/schemaEnsurer.php';

$tasks = [
    'visita.timer' => 'ensure_visita_timer_column',
    'visita.faturamento' => 'ensure_visita_faturamento_columns',
    'capeante.core' => 'ensure_capeante_core_columns',
    'internacao.timer' => 'ensure_internacao_timer_column',
    'internacao.core' => 'ensure_internacao_core_columns',
    'internacao.forecast' => 'ensure_internacao_forecast_columns',
    'schema.version' => 'ensure_schema_version_table',
    'password_reset' => 'ensure_password_reset_table',
    'operational.indexes' => 'ensure_operational_list_indexes',
    'hospital.related' => 'ensure_hospital_related_tables',
    'user.login_security' => 'ensure_user_login_security_columns',
    'user.mfa' => 'ensure_user_mfa_schema',
];

foreach ($tasks as $label => $task) {
    $startedAt = microtime(true);
    $task($conn);
    printf("[ok] %-24s %.3fs\n", $label, microtime(true) - $startedAt);
}

echo "Manutenção de schema concluída.\n";
