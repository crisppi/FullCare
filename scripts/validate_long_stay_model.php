<?php
chdir(dirname(__DIR__));
require 'db.php';
require 'app/services/LongStayPredictiveService.php';

$result = (new LongStayPredictiveService($conn))->validateHistorical(5);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
