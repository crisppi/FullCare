<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../app/services/ProntoSocorroAuditService.php';
ProntoSocorroAuditService::migrate($conn);
echo "Estrutura de contas de PS instalada.\n";
