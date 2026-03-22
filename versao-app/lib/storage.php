<?php
declare(strict_types=1);

const VERSION_APP_DATA_FILE = __DIR__ . '/../data/storage.json';

function versionAppStorageDefaults(): array
{
    return [
        'counters' => [
            'admission' => 0,
            'tuss' => 0,
            'extension' => 0,
        ],
        'admissions' => [],
        'tuss_items' => [],
        'extensions' => [],
    ];
}

function versionAppEnsureStorageFile(): void
{
    $dir = dirname(VERSION_APP_DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (!file_exists(VERSION_APP_DATA_FILE)) {
        file_put_contents(
            VERSION_APP_DATA_FILE,
            json_encode(versionAppStorageDefaults(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}

function versionAppReadStorage(): array
{
    versionAppEnsureStorageFile();

    $content = file_get_contents(VERSION_APP_DATA_FILE);
    if ($content === false || trim($content) === '') {
        return versionAppStorageDefaults();
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return versionAppStorageDefaults();
    }

    return array_replace_recursive(versionAppStorageDefaults(), $decoded);
}

function versionAppWriteStorage(array $data): void
{
    versionAppEnsureStorageFile();

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Falha ao serializar o armazenamento.');
    }

    file_put_contents(VERSION_APP_DATA_FILE, $json, LOCK_EX);
}

function versionAppCreateAdmission(array $payload): array
{
    $storage = versionAppReadStorage();
    $storage['counters']['admission']++;

    $admission = [
        'id' => $storage['counters']['admission'],
        'patient_name' => trim((string) ($payload['patient_name'] ?? '')),
        'insurance_name' => trim((string) ($payload['insurance_name'] ?? '')),
        'cid_code' => strtoupper(trim((string) ($payload['cid_code'] ?? ''))),
        'admission_date' => (string) ($payload['admission_date'] ?? ''),
        'doctor_name' => trim((string) ($payload['doctor_name'] ?? '')),
        'status' => trim((string) ($payload['status'] ?? 'em_analise')),
        'notes' => trim((string) ($payload['notes'] ?? '')),
        'created_at' => date('c'),
    ];

    $storage['admissions'][] = $admission;
    versionAppWriteStorage($storage);

    return $admission;
}

function versionAppCreateTussItem(array $payload): array
{
    $storage = versionAppReadStorage();
    $storage['counters']['tuss']++;

    $item = [
        'id' => $storage['counters']['tuss'],
        'admission_id' => (int) ($payload['admission_id'] ?? 0),
        'tuss_code' => trim((string) ($payload['tuss_code'] ?? '')),
        'description' => trim((string) ($payload['description'] ?? '')),
        'quantity' => max(1, (int) ($payload['quantity'] ?? 1)),
        'created_at' => date('c'),
    ];

    $storage['tuss_items'][] = $item;
    versionAppWriteStorage($storage);

    return $item;
}

function versionAppCreateExtension(array $payload): array
{
    $storage = versionAppReadStorage();
    $storage['counters']['extension']++;

    $extension = [
        'id' => $storage['counters']['extension'],
        'admission_id' => (int) ($payload['admission_id'] ?? 0),
        'requested_days' => max(1, (int) ($payload['requested_days'] ?? 1)),
        'status' => trim((string) ($payload['status'] ?? 'solicitada')),
        'justification' => trim((string) ($payload['justification'] ?? '')),
        'created_at' => date('c'),
    ];

    $storage['extensions'][] = $extension;
    versionAppWriteStorage($storage);

    return $extension;
}

function versionAppAdmissionExists(int $admissionId): bool
{
    $storage = versionAppReadStorage();
    foreach ($storage['admissions'] as $admission) {
        if ((int) $admission['id'] === $admissionId) {
            return true;
        }
    }

    return false;
}
