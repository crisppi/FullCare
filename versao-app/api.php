<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/storage.php';

header('Content-Type: application/json; charset=utf-8');

function versionAppJsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function versionAppRequestData(): array
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return $_GET;
    }

    $raw = file_get_contents('php://input');
    if (!$raw) {
        return $_POST;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

function versionAppValidateRequired(array $data, array $fields): ?string
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            return 'Campo obrigatorio ausente: ' . $field;
        }
    }

    return null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'GET' ? 'dashboard' : '');
$request = versionAppRequestData();

try {
    if ($method === 'GET' && $action === 'dashboard') {
        $storage = versionAppReadStorage();
        versionAppJsonResponse([
            'success' => true,
            'data' => $storage,
        ]);
    }

    if ($method === 'POST' && $action === 'admission') {
        $error = versionAppValidateRequired($request, [
            'patient_name',
            'insurance_name',
            'cid_code',
            'admission_date',
            'doctor_name',
            'status',
        ]);

        if ($error !== null) {
            versionAppJsonResponse(['success' => false, 'message' => $error], 422);
        }

        $created = versionAppCreateAdmission($request);
        versionAppJsonResponse(['success' => true, 'message' => 'Internacao salva.', 'data' => $created], 201);
    }

    if ($method === 'POST' && $action === 'tuss') {
        $error = versionAppValidateRequired($request, [
            'admission_id',
            'tuss_code',
            'description',
            'quantity',
        ]);

        if ($error !== null) {
            versionAppJsonResponse(['success' => false, 'message' => $error], 422);
        }

        $admissionId = (int) $request['admission_id'];
        if (!versionAppAdmissionExists($admissionId)) {
            versionAppJsonResponse(['success' => false, 'message' => 'Internacao informada nao existe.'], 404);
        }

        $created = versionAppCreateTussItem($request);
        versionAppJsonResponse(['success' => true, 'message' => 'Item TUSS salvo.', 'data' => $created], 201);
    }

    if ($method === 'POST' && $action === 'extension') {
        $error = versionAppValidateRequired($request, [
            'admission_id',
            'requested_days',
            'status',
            'justification',
        ]);

        if ($error !== null) {
            versionAppJsonResponse(['success' => false, 'message' => $error], 422);
        }

        $admissionId = (int) $request['admission_id'];
        if (!versionAppAdmissionExists($admissionId)) {
            versionAppJsonResponse(['success' => false, 'message' => 'Internacao informada nao existe.'], 404);
        }

        $created = versionAppCreateExtension($request);
        versionAppJsonResponse(['success' => true, 'message' => 'Prorrogacao salva.', 'data' => $created], 201);
    }

    versionAppJsonResponse(['success' => false, 'message' => 'Rota nao encontrada.'], 404);
} catch (Throwable $exception) {
    versionAppJsonResponse([
        'success' => false,
        'message' => 'Erro interno ao processar a requisicao.',
        'details' => $exception->getMessage(),
    ], 500);
}
