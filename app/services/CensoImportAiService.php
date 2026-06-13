<?php

class CensoImportAiService
{
    private PDO $conn;
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private array $hospitals = [];
    private array $patients = [];

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        $this->loadEnvFile(dirname(__DIR__, 2) . '/.env');
        $this->apiKey = trim((string)(getenv('MINHA_API_TOKEN') ?: ($_ENV['MINHA_API_TOKEN'] ?? '') ?: getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? '')));
        $this->apiUrl = trim((string)(getenv('OPENAI_API_URL') ?: 'https://api.openai.com/v1/responses'));
        $this->model = trim((string)(getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini'));
        $this->loadLookupData();
    }

    public function parseUpload(array $file, string $pdfText = '', string $pdfImagesJson = ''): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Envie um arquivo PDF, Excel ou CSV.');
        }

        $name = (string)($file['name'] ?? '');
        $tmp = (string)($file['tmp_name'] ?? '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!is_uploaded_file($tmp)) {
            throw new RuntimeException('Upload inválido.');
        }

        if (in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            $rows = $this->parseSpreadsheet($tmp, $ext);
            $source = 'excel';
        } elseif ($ext === 'pdf') {
            $text = trim($pdfText);
            $images = $this->decodePdfImages($pdfImagesJson);
            if ($text !== '') {
                $rows = $this->parseText($text);
                $source = $this->apiKey !== '' ? 'pdf_texto_ia' : 'pdf_texto_local';
            } elseif ($images) {
                if ($this->apiKey === '' || !function_exists('curl_init')) {
                    throw new RuntimeException('Este PDF parece ser escaneado. Para ler imagem, configure a chave de IA ou envie em Excel/CSV.');
                }
                $rows = $this->parseImagesWithAi($images);
                $source = 'pdf_ocr_ia';
            } else {
                throw new RuntimeException('Este PDF parece ser escaneado e não trouxe texto. Recarregue a página e tente novamente; se persistir, envie em Excel/CSV.');
            }
        } else {
            throw new RuntimeException('Formato não suportado. Use PDF, XLSX, XLS ou CSV.');
        }

        $candidates = [];
        foreach ($rows as $index => $row) {
            $candidate = $this->normalizeCandidate($row, $index + 1);
            if ($this->isEmptyCandidate($candidate)) {
                continue;
            }
            $candidates[] = $this->validateCandidate($candidate);
        }

        return [
            'source' => $source,
            'file_name' => $name,
            'total' => count($candidates),
            'valid' => count(array_filter($candidates, fn($item) => !empty($item['valid']))),
            'candidates' => $candidates,
        ];
    }

    public function importCandidates(array $candidates, array $selected): array
    {
        $selected = array_map('intval', $selected);
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($selected as $index) {
            if (!isset($candidates[$index])) {
                continue;
            }
            $item = $candidates[$index];
            if (empty($item['valid'])) {
                $skipped++;
                $errors[] = 'Linha ' . ($item['row_number'] ?? '?') . ': registro inválido não importado.';
                continue;
            }

            try {
                if ($this->censoExists($item)) {
                    $skipped++;
                    continue;
                }
                $this->insertCenso($item);
                $created++;
            } catch (Throwable $e) {
                $skipped++;
                $errors[] = 'Linha ' . ($item['row_number'] ?? '?') . ': ' . $this->redactSecrets($e->getMessage());
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function applyManualEdits(array $candidates, array $edits): array
    {
        foreach ($edits as $index => $fields) {
            $index = (int)$index;
            if (!isset($candidates[$index]) || !is_array($fields)) {
                continue;
            }
            foreach (['hospital', 'paciente', 'senha', 'acomodacao', 'tipo_admissao', 'modo_internacao', 'medico'] as $field) {
                if (array_key_exists($field, $fields)) {
                    $candidates[$index][$field] = trim((string)$fields[$field]);
                }
            }
            if (array_key_exists('data_censo', $fields)) {
                $candidates[$index]['data_censo'] = $this->parseDate((string)$fields['data_censo']);
            }
            $candidates[$index] = $this->validateCandidate($candidates[$index]);
        }

        return array_values($candidates);
    }

    public function applyInstruction(array $candidates, string $instruction): array
    {
        $instruction = trim($instruction);
        if ($instruction === '') {
            return $candidates;
        }

        try {
            if ($this->apiKey !== '' && function_exists('curl_init')) {
                return $this->applyInstructionWithAi($candidates, $instruction);
            }
        } catch (Throwable $e) {
            // Fallback local abaixo.
        }

        return $this->applyInstructionLocally($candidates, $instruction);
    }

    private function parseSpreadsheet(string $path, string $ext): array
    {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('PhpSpreadsheet não está disponível.');
        }
        require_once $autoload;

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        if (!$rows) {
            return [];
        }

        $headerRow = $this->detectHeaderRow($rows);
        $headers = [];
        foreach ($rows[$headerRow] ?? [] as $col => $value) {
            $headers[$col] = $this->normalizeHeader((string)$value);
        }

        $parsed = [];
        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber <= $headerRow) {
                continue;
            }
            $item = [];
            foreach ($row as $col => $value) {
                $field = $this->mapHeaderToField($headers[$col] ?? '');
                if (!$field) {
                    continue;
                }
                $item[$field] = $this->formatCellValue($value);
            }
            if ($item) {
                $parsed[] = $item + ['row_number' => $rowNumber];
            }
        }

        return $parsed;
    }

    private function parseText(string $text): array
    {
        try {
            if ($this->apiKey !== '' && function_exists('curl_init')) {
                return $this->parseTextWithAi($text);
            }
        } catch (Throwable $e) {
            // Fallback local abaixo.
        }

        return $this->parseTextLocally($text);
    }

    private function parseTextWithAi(string $text): array
    {
        $text = mb_substr($text, 0, 18000, 'UTF-8');
        $prompt = "Extraia registros de censo hospitalar do texto abaixo.\n\n"
            . "Retorne SOMENTE JSON válido, no formato:\n"
            . "[{\"hospital\":\"\",\"paciente\":\"\",\"data_censo\":\"YYYY-MM-DD ou vazio\",\"senha\":\"\",\"acomodacao\":\"\",\"tipo_admissao\":\"\",\"modo_internacao\":\"\",\"medico\":\"\"}]\n\n"
            . "Regras:\n"
            . "- Não invente dados ausentes.\n"
            . "- Cada paciente/linha de censo deve virar um objeto.\n"
            . "- Se houver data em formato brasileiro, converta para YYYY-MM-DD.\n"
            . "- Campos desconhecidos devem ficar vazios.\n\n"
            . "TEXTO DO CENSO:\n{$text}";

        $payload = [
            'model' => $this->model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => 'Você extrai dados administrativos de censos hospitalares. Responda apenas JSON válido.',
                    ]],
                ],
                [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => $prompt,
                    ]],
                ],
            ],
            'temperature' => 0.1,
            'max_output_tokens' => 3500,
        ];

        $raw = $this->requestOpenAi($payload);
        $decoded = json_decode($raw, true);
        $content = is_array($decoded) ? $this->extractText($decoded) : null;
        if (!$content) {
            throw new RuntimeException('IA retornou resposta vazia.');
        }

        $json = trim($content);
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $json, $match)) {
            $json = trim($match[1]);
        }
        $rows = json_decode($json, true);
        if (!is_array($rows)) {
            throw new RuntimeException('IA retornou JSON inválido.');
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private function parseImagesWithAi(array $images): array
    {
        $content = [[
            'type' => 'input_text',
            'text' => "Leia as imagens de um censo hospitalar escaneado.\n\n"
                . "Retorne SOMENTE JSON válido, no formato:\n"
                . "[{\"hospital\":\"\",\"paciente\":\"\",\"data_censo\":\"YYYY-MM-DD ou vazio\",\"senha\":\"\",\"acomodacao\":\"\",\"tipo_admissao\":\"\",\"modo_internacao\":\"\",\"medico\":\"\"}]\n\n"
                . "Regras:\n"
                . "- Não invente dados ausentes.\n"
                . "- Cada paciente/linha de censo deve virar um objeto.\n"
                . "- Se houver data em formato brasileiro, converta para YYYY-MM-DD.\n"
                . "- Se a mesma página tiver um hospital no cabeçalho, use esse hospital nas linhas.\n"
                . "- Campos desconhecidos devem ficar vazios.",
        ]];

        foreach (array_slice($images, 0, 8) as $image) {
            $content[] = [
                'type' => 'input_image',
                'image_url' => $image,
            ];
        }

        $payload = [
            'model' => $this->model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => 'Você faz OCR de censos hospitalares escaneados e responde apenas JSON válido.',
                    ]],
                ],
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
            'temperature' => 0.1,
            'max_output_tokens' => 4500,
        ];

        $raw = $this->requestOpenAi($payload);
        $decoded = json_decode($raw, true);
        $contentText = is_array($decoded) ? $this->extractText($decoded) : null;
        if (!$contentText) {
            throw new RuntimeException('IA retornou resposta vazia ao ler o PDF escaneado.');
        }

        $json = trim($contentText);
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $json, $match)) {
            $json = trim($match[1]);
        }
        $rows = json_decode($json, true);
        if (!is_array($rows)) {
            throw new RuntimeException('IA retornou JSON inválido ao ler o PDF escaneado.');
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private function applyInstructionWithAi(array $candidates, string $instruction): array
    {
        $compact = [];
        foreach ($candidates as $index => $item) {
            $compact[] = [
                'index' => $index,
                'hospital' => (string)($item['hospital'] ?? ''),
                'paciente' => (string)($item['paciente'] ?? ''),
                'data_censo' => (string)($item['data_censo'] ?? ''),
                'senha' => (string)($item['senha'] ?? ''),
                'acomodacao' => (string)($item['acomodacao'] ?? ''),
                'tipo_admissao' => (string)($item['tipo_admissao'] ?? ''),
                'modo_internacao' => (string)($item['modo_internacao'] ?? ''),
                'medico' => (string)($item['medico'] ?? ''),
            ];
        }

        $payload = [
            'model' => $this->model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => 'Você corrige uma prévia de importação de censos. Responda somente JSON válido.',
                    ]],
                ],
                [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => "Aplique a instrução do usuário aos registros abaixo.\n\n"
                            . "INSTRUÇÃO:\n{$instruction}\n\n"
                            . "REGISTROS:\n" . json_encode($compact, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"
                            . "Retorne SOMENTE JSON array com os mesmos campos e index. Datas em YYYY-MM-DD. Não invente dados não orientados.",
                    ]],
                ],
            ],
            'temperature' => 0.1,
            'max_output_tokens' => 4500,
        ];

        $raw = $this->requestOpenAi($payload);
        $decoded = json_decode($raw, true);
        $content = is_array($decoded) ? $this->extractText($decoded) : null;
        if (!$content) {
            throw new RuntimeException('IA retornou resposta vazia ao corrigir a prévia.');
        }

        $json = trim($content);
        if (preg_match('/```(?:json)?\s*(.*?)```/is', $json, $match)) {
            $json = trim($match[1]);
        }
        $rows = json_decode($json, true);
        if (!is_array($rows)) {
            throw new RuntimeException('IA retornou JSON inválido ao corrigir a prévia.');
        }

        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['index'])) {
                continue;
            }
            $index = (int)$row['index'];
            if (!isset($candidates[$index])) {
                continue;
            }
            foreach (['hospital', 'paciente', 'senha', 'acomodacao', 'tipo_admissao', 'modo_internacao', 'medico'] as $field) {
                if (array_key_exists($field, $row)) {
                    $candidates[$index][$field] = trim((string)$row[$field]);
                }
            }
            if (array_key_exists('data_censo', $row)) {
                $candidates[$index]['data_censo'] = $this->parseDate((string)$row['data_censo']);
            }
            $candidates[$index] = $this->validateCandidate($candidates[$index]);
        }

        return array_values($candidates);
    }

    private function applyInstructionLocally(array $candidates, string $instruction): array
    {
        $hospital = '';
        if (preg_match('/\b(?:hospital|unidade)\s+(?:correto|certo|e|é|eh|=|:)\s*([^\n;.]+)/iu', $instruction, $match)) {
            $hospital = trim($match[1]);
        } elseif (preg_match('/\b(?:hospital|unidade)\s*[:=]\s*([^\n;.]+)/iu', $instruction, $match)) {
            $hospital = trim($match[1]);
        }

        $date = '';
        if (preg_match('/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}-\d{2}-\d{2})\b/', $instruction, $match)) {
            $date = $this->parseDate($match[1]);
        }

        foreach ($candidates as $index => $item) {
            if ($hospital !== '') {
                $candidates[$index]['hospital'] = $hospital;
            }
            if ($date !== '') {
                $candidates[$index]['data_censo'] = $date;
            }
            $candidates[$index] = $this->validateCandidate($candidates[$index]);
        }

        return array_values($candidates);
    }

    private function parseTextLocally(string $text): array
    {
        $lines = preg_split('/\R+/', $text) ?: [];
        $rows = [];
        $currentHospital = '';

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line) ?? '');
            if ($line === '') {
                continue;
            }

            if (preg_match('/\b(hospital|hosp\.?)\s*[:\-]\s*(.+)$/i', $line, $match)) {
                $currentHospital = trim($match[2]);
                continue;
            }

            if (!preg_match('/\b([A-ZÁÀÂÃÉÊÍÓÔÕÚÇ][A-ZÁÀÂÃÉÊÍÓÔÕÚÇ\' ]{4,})\b/u', $line, $nameMatch)) {
                continue;
            }

            $date = '';
            if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})\b/', $line, $dateMatch)) {
                $date = $this->parseDate($dateMatch[0]);
            }

            $senha = '';
            if (preg_match('/\b(?:senha|aut(?:orizacao)?\.?)\s*[:\-]?\s*([A-Z0-9.\-\/]+)/i', $line, $senhaMatch)) {
                $senha = $senhaMatch[1];
            }

            $rows[] = [
                'hospital' => $currentHospital,
                'paciente' => trim($nameMatch[1]),
                'data_censo' => $date,
                'senha' => $senha,
                'acomodacao' => $this->detectAcomodacao($line),
                'tipo_admissao' => '',
                'modo_internacao' => '',
                'medico' => '',
            ];
        }

        return $rows;
    }

    private function normalizeCandidate(array $row, int $fallbackRow): array
    {
        return [
            'row_number' => (int)($row['row_number'] ?? $fallbackRow),
            'hospital' => trim((string)($row['hospital'] ?? $row['nome_hosp'] ?? '')),
            'paciente' => trim((string)($row['paciente'] ?? $row['nome_pac'] ?? '')),
            'data_censo' => $this->parseDate((string)($row['data_censo'] ?? $row['data'] ?? '')),
            'senha' => trim((string)($row['senha'] ?? $row['senha_censo'] ?? '')),
            'acomodacao' => trim((string)($row['acomodacao'] ?? $row['acomodacao_censo'] ?? '')),
            'tipo_admissao' => trim((string)($row['tipo_admissao'] ?? $row['tipo_internacao'] ?? $row['tipo_admissao_censo'] ?? '')),
            'modo_internacao' => trim((string)($row['modo_internacao'] ?? $row['modo_admissao'] ?? $row['modo_internacao_censo'] ?? '')),
            'medico' => trim((string)($row['medico'] ?? $row['titular'] ?? $row['titular_censo'] ?? '')),
            'raw' => $row,
        ];
    }

    private function validateCandidate(array $item): array
    {
        $errors = [];
        unset($item['hospital_match'], $item['patient_match'], $item['valid'], $item['errors']);
        $hospital = $this->matchHospital($item['hospital']);
        $patient = $this->matchPatient($item['paciente']);

        if (!$hospital) $errors[] = 'Hospital não encontrado';
        if (!$patient) $errors[] = 'Paciente não encontrado';
        if ($item['data_censo'] === '') $errors[] = 'Data do censo não encontrada';
        if ($item['paciente'] === '') $errors[] = 'Paciente vazio';

        $item['hospital_match'] = $hospital;
        $item['patient_match'] = $patient;
        $item['valid'] = !$errors;
        $item['errors'] = $errors;

        return $item;
    }

    private function insertCenso(array $item): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO tb_censo (
                fk_paciente_censo,
                fk_hospital_censo,
                data_censo,
                senha_censo,
                acomodacao_censo,
                tipo_admissao_censo,
                modo_internacao_censo,
                usuario_create_censo,
                data_create_censo,
                titular_censo
            ) VALUES (
                :paciente,
                :hospital,
                :data_censo,
                :senha,
                :acomodacao,
                :tipo_admissao,
                :modo_internacao,
                :usuario,
                :data_create,
                :medico
            )
        ");

        $stmt->execute([
            ':paciente' => (int)$item['patient_match']['id_paciente'],
            ':hospital' => (int)$item['hospital_match']['id_hospital'],
            ':data_censo' => $item['data_censo'],
            ':senha' => $item['senha'],
            ':acomodacao' => $item['acomodacao'],
            ':tipo_admissao' => $item['tipo_admissao'],
            ':modo_internacao' => $item['modo_internacao'],
            ':usuario' => (string)($_SESSION['email_user'] ?? $_SESSION['usuario_user'] ?? 'importacao_ia'),
            ':data_create' => date('Y-m-d H:i:s'),
            ':medico' => $item['medico'],
        ]);
    }

    private function censoExists(array $item): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id_censo
            FROM tb_censo
            WHERE fk_paciente_censo = :paciente
              AND fk_hospital_censo = :hospital
              AND data_censo = :data_censo
              AND COALESCE(senha_censo, '') = :senha
            LIMIT 1
        ");
        $stmt->execute([
            ':paciente' => (int)$item['patient_match']['id_paciente'],
            ':hospital' => (int)$item['hospital_match']['id_hospital'],
            ':data_censo' => $item['data_censo'],
            ':senha' => $item['senha'],
        ]);

        return (bool)$stmt->fetchColumn();
    }

    private function loadLookupData(): void
    {
        $this->hospitals = $this->conn->query("SELECT id_hospital, nome_hosp FROM tb_hospital ORDER BY nome_hosp")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $this->patients = $this->conn->query("SELECT id_paciente, nome_pac FROM tb_paciente WHERE COALESCE(deletado_pac, 'n') <> 's' ORDER BY nome_pac")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function matchHospital(string $name): ?array
    {
        return $this->matchByName($name, $this->hospitals, 'nome_hosp');
    }

    private function matchPatient(string $name): ?array
    {
        return $this->matchByName($name, $this->patients, 'nome_pac');
    }

    private function matchByName(string $name, array $rows, string $field): ?array
    {
        $needle = $this->normalizeName($name);
        if ($needle === '') return null;

        $best = null;
        $bestScore = 0.0;
        foreach ($rows as $row) {
            $candidate = $this->normalizeName((string)($row[$field] ?? ''));
            if ($candidate === '') continue;
            if ($candidate === $needle) return $row;
            if (strpos($candidate, $needle) !== false || strpos($needle, $candidate) !== false) {
                $score = 92.0;
            } else {
                similar_text($needle, $candidate, $score);
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        return $bestScore >= 86 ? $best : null;
    }

    private function detectHeaderRow(array $rows): int
    {
        $bestRow = 1;
        $bestScore = -1;
        foreach (array_slice($rows, 0, 12, true) as $index => $row) {
            $score = 0;
            foreach ($row as $cell) {
                if ($this->mapHeaderToField($this->normalizeHeader((string)$cell))) $score++;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = (int)$index;
            }
        }
        return $bestRow;
    }

    private function mapHeaderToField(string $header): ?string
    {
        $map = [
            'hospital' => ['hospital', 'hosp', 'unidade'],
            'paciente' => ['paciente', 'beneficiario', 'beneficiário', 'nome'],
            'data_censo' => ['data', 'data censo', 'internacao', 'internação', 'dt internacao', 'dt internação'],
            'senha' => ['senha', 'autorizacao', 'autorização', 'guia'],
            'acomodacao' => ['acomodacao', 'acomodação', 'leito', 'quarto'],
            'tipo_admissao' => ['tipo', 'tipo internacao', 'tipo internação'],
            'modo_internacao' => ['modo', 'modo admissao', 'modo admissão', 'carater', 'caráter'],
            'medico' => ['medico', 'médico', 'titular', 'assistente'],
        ];

        foreach ($map as $field => $aliases) {
            foreach ($aliases as $alias) {
                if ($header === $this->normalizeHeader($alias) || strpos($header, $this->normalizeHeader($alias)) !== false) {
                    return $field;
                }
            }
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9 ]+/', ' ', $this->normalizeName($value)) ?? '');
    }

    private function normalizeName(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $from = ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç'];
        $to = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'];
        $value = str_replace($from, $to, $value);
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]+/', ' ', $value) ?? '') ?? '');
    }

    private function parseDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') return '';
        if (is_numeric($value) && (float)$value > 25000) {
            try {
                $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
                if (!class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date') && is_file($autoload)) {
                    require_once $autoload;
                }
                if (!class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
                    return '';
                }
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return $dt->format('Y-m-d');
            } catch (Throwable $e) {
                return '';
            }
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $value : '';
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/', $value, $m)) {
            $year = (int)$m[3];
            if ($year < 100) $year += 2000;
            return checkdate((int)$m[2], (int)$m[1], $year) ? sprintf('%04d-%02d-%02d', $year, (int)$m[2], (int)$m[1]) : '';
        }
        $time = strtotime($value);
        return $time ? date('Y-m-d', $time) : '';
    }

    private function formatCellValue($value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        return trim((string)$value);
    }

    private function detectAcomodacao(string $line): string
    {
        if (preg_match('/\b(uti|u\.t\.i\.|cti)\b/i', $line)) return 'UTI';
        if (preg_match('/\b(apartamento|apto)\b/i', $line)) return 'Apartamento';
        if (preg_match('/\b(enfermaria)\b/i', $line)) return 'Enfermaria';
        return '';
    }

    private function isEmptyCandidate(array $candidate): bool
    {
        return trim($candidate['hospital'] . $candidate['paciente'] . $candidate['senha']) === '';
    }

    private function decodePdfImages(string $json): array
    {
        $json = trim($json);
        if ($json === '') return [];
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return [];

        $images = [];
        foreach ($decoded as $image) {
            if (!is_string($image)) continue;
            if (!preg_match('#^data:image/(?:png|jpeg|jpg);base64,[A-Za-z0-9+/=]+$#', $image)) continue;
            if (strlen($image) > 2500000) continue;
            $images[] = $image;
            if (count($images) >= 8) break;
        }
        return $images;
    }

    private function requestOpenAi(array $payload): string
    {
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 40,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlErr) {
            throw new RuntimeException('Falha de conexão com o serviço de IA.');
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Serviço de IA indisponível no momento (HTTP ' . $httpCode . ').');
        }

        return (string)$raw;
    }

    private function extractText(array $responseJson): ?string
    {
        if (!empty($responseJson['output_text']) && is_string($responseJson['output_text'])) {
            return trim($responseJson['output_text']);
        }
        $parts = [];
        foreach (($responseJson['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (!empty($content['text']) && is_string($content['text'])) {
                    $parts[] = $content['text'];
                }
            }
        }
        return $parts ? trim(implode("\n", $parts)) : null;
    }

    private function loadEnvFile(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($key === '' || getenv($key) !== false) continue;
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }

    private function redactSecrets(string $message): string
    {
        if ($this->apiKey !== '') {
            $message = str_replace($this->apiKey, '[redacted]', $message);
        }
        return preg_replace('/sk-[A-Za-z0-9_\-]+/', 'sk-[redacted]', $message) ?? $message;
    }
}
