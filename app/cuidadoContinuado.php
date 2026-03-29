<?php

require_once __DIR__ . '/schemaEnsurer.php';

if (!function_exists('ensure_cuidado_continuado_schema')) {
    function ensure_cuidado_continuado_schema(PDO $conn): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $statements = [
            "CREATE TABLE IF NOT EXISTS tb_paciente_cronico (
                id_cronico INT AUTO_INCREMENT PRIMARY KEY,
                fk_paciente INT NOT NULL,
                condicao VARCHAR(120) NOT NULL,
                cid_codigo VARCHAR(20) NULL,
                status_acompanhamento ENUM('ativo','monitoramento','encerrado') NOT NULL DEFAULT 'ativo',
                nivel_risco ENUM('baixo','moderado','alto') NOT NULL DEFAULT 'moderado',
                data_diagnostico DATE NULL,
                ultima_consulta DATE NULL,
                proximo_contato DATE NULL,
                plano_cuidado TEXT NULL,
                observacoes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_cronico_paciente (fk_paciente),
                KEY idx_cronico_status (status_acompanhamento),
                KEY idx_cronico_risco (nivel_risco),
                KEY idx_cronico_condicao (condicao),
                CONSTRAINT fk_cc_cronico_paciente
                    FOREIGN KEY (fk_paciente) REFERENCES tb_paciente(id_paciente)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS tb_cronico_indicador (
                id_indicador INT AUTO_INCREMENT PRIMARY KEY,
                fk_cronico INT NOT NULL,
                indicador_nome VARCHAR(120) NOT NULL,
                valor_referencia VARCHAR(120) NULL,
                valor_atual VARCHAR(120) NULL,
                unidade VARCHAR(30) NULL,
                meta VARCHAR(120) NULL,
                aferido_em DATE NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_indicador_cronico (fk_cronico),
                CONSTRAINT fk_cc_indicador_cronico
                    FOREIGN KEY (fk_cronico) REFERENCES tb_paciente_cronico(id_cronico)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS tb_campanha_preventiva (
                id_campanha INT AUTO_INCREMENT PRIMARY KEY,
                nome_campanha VARCHAR(150) NOT NULL,
                descricao TEXT NULL,
                publico_condicao VARCHAR(120) NULL,
                publico_risco ENUM('baixo','moderado','alto','todos') NOT NULL DEFAULT 'todos',
                status_campanha ENUM('planejada','ativa','encerrada') NOT NULL DEFAULT 'planejada',
                periodicidade_dias INT NULL,
                data_inicio DATE NULL,
                data_fim DATE NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_campanha_status (status_campanha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS tb_campanha_preventiva_paciente (
                id_campanha_paciente INT AUTO_INCREMENT PRIMARY KEY,
                fk_campanha INT NOT NULL,
                fk_paciente INT NOT NULL,
                fk_cronico INT NULL,
                status_participacao ENUM('elegivel','convocado','agendado','concluido','nao_aderiu') NOT NULL DEFAULT 'elegivel',
                data_ultimo_contato DATE NULL,
                data_conclusao DATE NULL,
                observacoes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_cp_campanha (fk_campanha),
                KEY idx_cp_paciente (fk_paciente),
                KEY idx_cp_status (status_participacao),
                CONSTRAINT fk_cc_cp_campanha
                    FOREIGN KEY (fk_campanha) REFERENCES tb_campanha_preventiva(id_campanha)
                    ON DELETE CASCADE,
                CONSTRAINT fk_cc_cp_paciente
                    FOREIGN KEY (fk_paciente) REFERENCES tb_paciente(id_paciente)
                    ON DELETE CASCADE,
                CONSTRAINT fk_cc_cp_cronico
                    FOREIGN KEY (fk_cronico) REFERENCES tb_paciente_cronico(id_cronico)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($statements as $sql) {
            try {
                $conn->exec($sql);
            } catch (Throwable $e) {
                error_log('[CUIDADO_CONTINUADO][SCHEMA] ' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('cc_fetch_cronicos_summary')) {
    function cc_fetch_cronicos_summary(PDO $conn): array
    {
        ensure_cuidado_continuado_schema($conn);
        cc_sync_existing_i50_group_chronics($conn);

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN nivel_risco = 'alto' THEN 1 ELSE 0 END) AS alto_risco,
                    SUM(CASE WHEN status_acompanhamento = 'ativo' THEN 1 ELSE 0 END) AS ativos,
                    SUM(
                        CASE
                            WHEN status_acompanhamento <> 'encerrado'
                             AND (
                                 proximo_contato IS NULL
                                 OR proximo_contato < CURRENT_DATE()
                             ) THEN 1 ELSE 0
                        END
                    ) AS pendentes
                FROM tb_paciente_cronico";
        $row = $conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'alto_risco' => (int)($row['alto_risco'] ?? 0),
            'ativos' => (int)($row['ativos'] ?? 0),
            'pendentes' => (int)($row['pendentes'] ?? 0),
        ];
    }
}

if (!function_exists('cc_fetch_cronicos_list')) {
    function cc_fetch_cronicos_list(PDO $conn, string $search = '', string $risk = ''): array
    {
        ensure_cuidado_continuado_schema($conn);
        cc_sync_existing_i50_group_chronics($conn);

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(p.nome_pac LIKE :search OR p.matricula_pac LIKE :search OR c.condicao LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if (in_array($risk, ['baixo', 'moderado', 'alto'], true)) {
            $where[] = 'c.nivel_risco = :risk';
            $params[':risk'] = $risk;
        }

        $sql = "SELECT
                    c.id_cronico,
                    c.fk_paciente,
                    p.nome_pac,
                    p.matricula_pac,
                    c.condicao,
                    c.nivel_risco,
                    c.status_acompanhamento,
                    c.ultima_consulta,
                    c.proximo_contato,
                    c.data_diagnostico
                FROM tb_paciente_cronico c
                INNER JOIN tb_paciente p ON p.id_paciente = c.fk_paciente";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY
                    CASE c.nivel_risco
                        WHEN 'alto' THEN 1
                        WHEN 'moderado' THEN 2
                        ELSE 3
                    END,
                    c.proximo_contato IS NULL DESC,
                    c.proximo_contato ASC,
                    p.nome_pac ASC
                  LIMIT 100";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('cc_fetch_preventiva_summary')) {
    function cc_fetch_preventiva_summary(PDO $conn): array
    {
        ensure_cuidado_continuado_schema($conn);
        cc_sync_existing_i50_group_chronics($conn);

        $campanhas = $conn->query("SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status_campanha = 'ativa' THEN 1 ELSE 0 END) AS ativas
            FROM tb_campanha_preventiva")->fetch(PDO::FETCH_ASSOC) ?: [];

        $status = $conn->query("SELECT
                COUNT(*) AS total_pacientes,
                SUM(CASE WHEN status_participacao = 'elegivel' THEN 1 ELSE 0 END) AS elegiveis,
                SUM(CASE WHEN status_participacao = 'convocado' THEN 1 ELSE 0 END) AS convocados,
                SUM(CASE WHEN status_participacao = 'concluido' THEN 1 ELSE 0 END) AS concluidos
            FROM tb_campanha_preventiva_paciente")->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'campanhas_total' => (int)($campanhas['total'] ?? 0),
            'campanhas_ativas' => (int)($campanhas['ativas'] ?? 0),
            'elegiveis' => (int)($status['elegiveis'] ?? 0),
            'convocados' => (int)($status['convocados'] ?? 0),
            'concluidos' => (int)($status['concluidos'] ?? 0),
            'total_pacientes' => (int)($status['total_pacientes'] ?? 0),
        ];
    }
}

if (!function_exists('cc_fetch_active_campaigns')) {
    function cc_fetch_active_campaigns(PDO $conn): array
    {
        ensure_cuidado_continuado_schema($conn);
        cc_sync_existing_i50_group_chronics($conn);

        $sql = "SELECT
                    cp.id_campanha,
                    cp.nome_campanha,
                    cp.publico_condicao,
                    cp.publico_risco,
                    cp.status_campanha,
                    cp.data_inicio,
                    cp.data_fim,
                    COUNT(cpp.id_campanha_paciente) AS total_publico,
                    SUM(CASE WHEN cpp.status_participacao = 'concluido' THEN 1 ELSE 0 END) AS total_concluido
                FROM tb_campanha_preventiva cp
                LEFT JOIN tb_campanha_preventiva_paciente cpp ON cpp.fk_campanha = cp.id_campanha
                GROUP BY
                    cp.id_campanha,
                    cp.nome_campanha,
                    cp.publico_condicao,
                    cp.publico_risco,
                    cp.status_campanha,
                    cp.data_inicio,
                    cp.data_fim
                ORDER BY
                    CASE cp.status_campanha
                        WHEN 'ativa' THEN 1
                        WHEN 'planejada' THEN 2
                        ELSE 3
                    END,
                    cp.data_inicio DESC,
                    cp.id_campanha DESC
                LIMIT 50";

        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('cc_fetch_preventiva_elegiveis')) {
    function cc_fetch_preventiva_elegiveis(PDO $conn): array
    {
        ensure_cuidado_continuado_schema($conn);
        cc_sync_existing_i50_group_chronics($conn);

        $sql = "SELECT
                    c.id_cronico,
                    c.fk_paciente,
                    p.nome_pac,
                    p.matricula_pac,
                    c.condicao,
                    c.nivel_risco,
                    c.ultima_consulta,
                    MAX(cpp.data_ultimo_contato) AS ultimo_contato_preventivo
                FROM tb_paciente_cronico c
                INNER JOIN tb_paciente p ON p.id_paciente = c.fk_paciente
                LEFT JOIN tb_campanha_preventiva_paciente cpp ON cpp.fk_cronico = c.id_cronico
                WHERE c.status_acompanhamento IN ('ativo', 'monitoramento')
                GROUP BY
                    c.id_cronico,
                    c.fk_paciente,
                    p.nome_pac,
                    p.matricula_pac,
                    c.condicao,
                    c.nivel_risco,
                    c.ultima_consulta
                HAVING ultimo_contato_preventivo IS NULL
                    OR ultimo_contato_preventivo < DATE_SUB(CURRENT_DATE(), INTERVAL 180 DAY)
                ORDER BY
                    CASE c.nivel_risco
                        WHEN 'alto' THEN 1
                        WHEN 'moderado' THEN 2
                        ELSE 3
                    END,
                    p.nome_pac ASC
                LIMIT 100";

        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('cc_detect_chronic_conditions')) {
    function cc_detect_chronic_conditions(?string $text): array
    {
        $text = trim((string)$text);
        if ($text === '') {
            return [];
        }

        $rules = [
            [
                'condicao' => 'Hipertensão arterial',
                'risco' => 'moderado',
                'patterns' => ['/\bhipertens[aã]o\b/ui', '/\bhiperten[sç][aã]o\b/ui', '/\bhas\b/ui', '/\bpress[aã]o alta\b/ui'],
            ],
            [
                'condicao' => 'Diabetes mellitus',
                'risco' => 'moderado',
                'patterns' => ['/\bdiabetes?\b/ui', '/\bdm\b/ui', '/\bdm[\s-]*1\b/ui', '/\bdm[\s-]*2\b/ui'],
            ],
            [
                'condicao' => 'DPOC',
                'risco' => 'alto',
                'patterns' => ['/\bdpoc\b/ui', '/\bdoen[cç]a pulmonar obstrutiva cr[oô]nica\b/ui'],
            ],
            [
                'condicao' => 'Asma',
                'risco' => 'moderado',
                'patterns' => ['/\basma\b/ui', '/\basm[aá]tico\b/ui'],
            ],
            [
                'condicao' => 'Obesidade',
                'risco' => 'moderado',
                'patterns' => ['/\bobesidade\b/ui', '/\bobeso\b/ui'],
            ],
            [
                'condicao' => 'Insuficiência cardíaca',
                'risco' => 'alto',
                'patterns' => ['/\binsufici[eê]ncia card[ií]aca\b/ui', '/\binsuf\.?\s*card[ií]aca\b/ui', '/\bicc\b/ui', '/\bi50(?:\b|[.\d])/ui'],
            ],
            [
                'condicao' => 'Doença renal crônica',
                'risco' => 'alto',
                'patterns' => ['/\bdoen[cç]a renal cr[oô]nica\b/ui', '/\bdrc\b/ui', '/\binsufici[eê]ncia renal cr[oô]nica\b/ui', '/\binsuf\.?\s*renal cr[oô]nica\b/ui', '/\birc\b/ui'],
            ],
            [
                'condicao' => 'Coronariopatia',
                'risco' => 'alto',
                'patterns' => ['/\bcoronariopatia\b/ui', '/\bdoen[cç]a arterial coronariana\b/ui', '/\bdac\b/ui'],
            ],
            [
                'condicao' => 'AVC prévio',
                'risco' => 'alto',
                'patterns' => ['/\bavc\b/ui', '/\bacidente vascular cerebral\b/ui'],
            ],
        ];

        $matches = [];
        foreach ($rules as $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if (preg_match($pattern, $text)) {
                    $matches[$rule['condicao']] = [
                        'condicao' => $rule['condicao'],
                        'risco' => $rule['risco'],
                    ];
                    break;
                }
            }
        }

        return array_values($matches);
    }
}

if (!function_exists('cc_upsert_patient_chronics_from_text')) {
    function cc_upsert_patient_chronics_from_text(PDO $conn, int $patientId, ?string $text, string $source = ''): array
    {
        ensure_cuidado_continuado_schema($conn);

        if ($patientId <= 0) {
            return [];
        }

        $detected = cc_detect_chronic_conditions($text);
        if (!$detected) {
            return [];
        }

        $selectStmt = $conn->prepare("
            SELECT id_cronico, nivel_risco
              FROM tb_paciente_cronico
             WHERE fk_paciente = :patient_id
               AND condicao = :condicao
             ORDER BY id_cronico DESC
             LIMIT 1
        ");

        $insertStmt = $conn->prepare("
            INSERT INTO tb_paciente_cronico (
                fk_paciente,
                condicao,
                status_acompanhamento,
                nivel_risco,
                data_diagnostico,
                ultima_consulta,
                proximo_contato,
                observacoes
            ) VALUES (
                :patient_id,
                :condicao,
                'ativo',
                :risco,
                CURRENT_DATE(),
                CURRENT_DATE(),
                DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY),
                :observacoes
            )
        ");

        $updateStmt = $conn->prepare("
            UPDATE tb_paciente_cronico
               SET status_acompanhamento = 'ativo',
                   nivel_risco = CASE
                       WHEN nivel_risco = 'alto' OR :risco = 'alto' THEN 'alto'
                       WHEN nivel_risco = 'moderado' OR :risco = 'moderado' THEN 'moderado'
                       ELSE 'baixo'
                   END,
                   ultima_consulta = CURRENT_DATE(),
                   proximo_contato = CASE
                       WHEN proximo_contato IS NULL OR proximo_contato < CURRENT_DATE()
                           THEN DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)
                       ELSE proximo_contato
                   END,
                   observacoes = TRIM(CONCAT(COALESCE(observacoes, ''), CASE
                       WHEN COALESCE(observacoes, '') = '' THEN ''
                       ELSE '\n'
                   END, :observacoes))
             WHERE id_cronico = :id_cronico
        ");

        $saved = [];
        foreach ($detected as $item) {
            $obs = 'Sugerido automaticamente a partir de ' . ($source !== '' ? $source : 'relatorio clinico') . ' em ' . date('d/m/Y H:i');

            $selectStmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
            $selectStmt->bindValue(':condicao', $item['condicao'], PDO::PARAM_STR);
            $selectStmt->execute();
            $existing = $selectStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($existing) {
                $updateStmt->bindValue(':risco', $item['risco'], PDO::PARAM_STR);
                $updateStmt->bindValue(':observacoes', $obs, PDO::PARAM_STR);
                $updateStmt->bindValue(':id_cronico', (int)$existing['id_cronico'], PDO::PARAM_INT);
                $updateStmt->execute();
            } else {
                $insertStmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
                $insertStmt->bindValue(':condicao', $item['condicao'], PDO::PARAM_STR);
                $insertStmt->bindValue(':risco', $item['risco'], PDO::PARAM_STR);
                $insertStmt->bindValue(':observacoes', $obs, PDO::PARAM_STR);
                $insertStmt->execute();
            }

            $saved[] = $item['condicao'];
        }

        return $saved;
    }
}

if (!function_exists('cc_upsert_patient_chronics_from_antecedent_names')) {
    function cc_upsert_patient_chronics_from_antecedent_names(PDO $conn, int $patientId, array $antecedentNames, string $source = ''): array
    {
        $chunks = [];
        foreach ($antecedentNames as $name) {
            $name = trim((string)$name);
            if ($name !== '') {
                $chunks[] = $name;
            }
        }

        if (!$chunks) {
            return [];
        }

        return cc_upsert_patient_chronics_from_text(
            $conn,
            $patientId,
            implode("\n", $chunks),
            $source !== '' ? $source : 'antecedentes do paciente'
        );
    }
}

if (!function_exists('cc_fetch_patient_antecedent_names')) {
    function cc_fetch_patient_antecedent_names(PDO $conn, int $patientId): array
    {
        if ($patientId <= 0) {
            return [];
        }

        $stmt = $conn->prepare("
            SELECT DISTINCT antecedente_texto
              FROM (
                    SELECT TRIM(CONCAT_WS(' ',
                               NULLIF(a.antecedente_ant, ''),
                               NULLIF(c.cat, ''),
                               NULLIF(c.descricao, '')
                           )) AS antecedente_texto
                      FROM tb_intern_antec ia
                      INNER JOIN tb_antecedente a
                              ON a.id_antecedente = ia.intern_antec_ant_int
                      LEFT JOIN tb_cid c
                             ON c.id_cid = a.fk_cid_10_ant
                     WHERE ia.fk_id_paciente = :patient_id
                       AND (
                           COALESCE(a.antecedente_ant, '') <> ''
                           OR COALESCE(c.cat, '') <> ''
                           OR COALESCE(c.descricao, '') <> ''
                       )
                    UNION
                    SELECT TRIM(CONCAT_WS(' ',
                               NULLIF(a.antecedente_ant, ''),
                               NULLIF(c.cat, ''),
                               NULLIF(c.descricao, '')
                           )) AS antecedente_texto
                      FROM tb_internacao i
                      INNER JOIN tb_antecedente a
                              ON a.id_antecedente = i.fk_patologia2
                      LEFT JOIN tb_cid c
                             ON c.id_cid = a.fk_cid_10_ant
                     WHERE i.fk_paciente_int = :patient_id_internacao
                       AND i.fk_patologia2 IS NOT NULL
                       AND i.fk_patologia2 > 0
                       AND (
                           COALESCE(a.antecedente_ant, '') <> ''
                           OR COALESCE(c.cat, '') <> ''
                           OR COALESCE(c.descricao, '') <> ''
                       )
                ) antecedentes
             WHERE antecedente_texto <> ''
             ORDER BY antecedente_texto ASC
        ");
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':patient_id_internacao', $patientId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}

if (!function_exists('cc_sync_patient_chronics_from_existing_antecedents')) {
    function cc_sync_patient_chronics_from_existing_antecedents(PDO $conn, int $patientId, string $source = ''): array
    {
        $names = cc_fetch_patient_antecedent_names($conn, $patientId);
        if (!$names) {
            return [];
        }

        return cc_upsert_patient_chronics_from_antecedent_names(
            $conn,
            $patientId,
            $names,
            $source !== '' ? $source : 'antecedentes já cadastrados'
        );
    }
}

if (!function_exists('cc_fetch_antecedent_name_by_id')) {
    function cc_fetch_antecedent_name_by_id(PDO $conn, int $antecedentId): ?string
    {
        if ($antecedentId <= 0) {
            return null;
        }

        $stmt = $conn->prepare("
            SELECT TRIM(CONCAT_WS(' ',
                       NULLIF(a.antecedente_ant, ''),
                       NULLIF(c.cat, ''),
                       NULLIF(c.descricao, '')
                   )) AS antecedente_texto
              FROM tb_antecedente a
              LEFT JOIN tb_cid c
                     ON c.id_cid = a.fk_cid_10_ant
             WHERE id_antecedente = :antecedent_id
             LIMIT 1
        ");
        $stmt->bindValue(':antecedent_id', $antecedentId, PDO::PARAM_INT);
        $stmt->execute();

        $name = $stmt->fetchColumn();
        if ($name !== false) {
            $text = trim((string)$name);
            if ($text !== '') {
                return $text;
            }
        }

        // Fallback para o cadastro de internação, onde o campo "Antecedente"
        // atualmente envia id_cid em vez de id_antecedente.
        $cidStmt = $conn->prepare("
            SELECT TRIM(CONCAT_WS(' ',
                       NULLIF(c.cat, ''),
                       NULLIF(c.descricao, '')
                   )) AS antecedente_texto
              FROM tb_cid c
             WHERE c.id_cid = :cid_id
             LIMIT 1
        ");
        $cidStmt->bindValue(':cid_id', $antecedentId, PDO::PARAM_INT);
        $cidStmt->execute();

        $cidText = $cidStmt->fetchColumn();
        return $cidText !== false ? trim((string)$cidText) : null;
    }
}

if (!function_exists('cc_upsert_patient_chronics_from_antecedent_id')) {
    function cc_upsert_patient_chronics_from_antecedent_id(PDO $conn, int $patientId, int $antecedentId, string $source = ''): array
    {
        $name = cc_fetch_antecedent_name_by_id($conn, $antecedentId);
        if ($name === null || $name === '') {
            return [];
        }

        return cc_upsert_patient_chronics_from_antecedent_names(
            $conn,
            $patientId,
            [$name],
            $source !== '' ? $source : 'antecedente selecionado na internação'
        );
    }
}

if (!function_exists('cc_backfill_patient_chronics_from_existing_antecedents')) {
    function cc_backfill_patient_chronics_from_existing_antecedents(PDO $conn): void
    {
        static $synced = false;
        if ($synced) {
            return;
        }
        $synced = true;

        $stmt = $conn->query("
            SELECT DISTINCT
                   ia.fk_id_paciente AS patient_id,
                   TRIM(CONCAT_WS(' ',
                       NULLIF(a.antecedente_ant, ''),
                       NULLIF(c.cat, ''),
                       NULLIF(c.descricao, '')
                   )) AS antecedente_texto
              FROM tb_intern_antec ia
              INNER JOIN tb_antecedente a
                      ON a.id_antecedente = ia.intern_antec_ant_int
              LEFT JOIN tb_cid c
                     ON c.id_cid = a.fk_cid_10_ant
             WHERE ia.fk_id_paciente IS NOT NULL
               AND (
                   COALESCE(a.antecedente_ant, '') <> ''
                   OR COALESCE(c.cat, '') <> ''
                   OR COALESCE(c.descricao, '') <> ''
               )
             ORDER BY ia.fk_id_paciente ASC
        ");

        $byPatient = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $patientId = (int)($row['patient_id'] ?? 0);
            $texto = trim((string)($row['antecedente_texto'] ?? ''));
            if ($patientId <= 0 || $texto === '') {
                continue;
            }
            $byPatient[$patientId][$texto] = $texto;
        }

        foreach ($byPatient as $patientId => $texts) {
            cc_upsert_patient_chronics_from_antecedent_names(
                $conn,
                (int)$patientId,
                array_values($texts),
                'backfill automático de antecedentes'
            );
        }
    }
}

if (!function_exists('cc_sync_existing_i50_group_chronics')) {
    function cc_sync_existing_i50_group_chronics(PDO $conn): void
    {
        static $synced = false;
        if ($synced) {
            return;
        }
        $synced = true;

        $insertSql = "
            INSERT INTO tb_paciente_cronico (
                fk_paciente,
                condicao,
                cid_codigo,
                status_acompanhamento,
                nivel_risco,
                data_diagnostico,
                ultima_consulta,
                proximo_contato,
                observacoes
            )
            SELECT src.patient_id,
                   'Insuficiência cardíaca',
                   'I50',
                   'ativo',
                   'alto',
                   CURRENT_DATE(),
                   CURRENT_DATE(),
                   DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY),
                   'Sugerido automaticamente a partir de antecedentes/CID I50 em ' . DATE_FORMAT(NOW(), '%d/%m/%Y %H:%i')
              FROM (
                    SELECT DISTINCT i.fk_paciente_int AS patient_id
                      FROM tb_internacao i
                      INNER JOIN tb_antecedente a
                              ON a.id_antecedente = i.fk_patologia2
                      INNER JOIN tb_cid c
                              ON c.id_cid = a.fk_cid_10_ant
                     WHERE i.fk_paciente_int IS NOT NULL
                       AND i.fk_patologia2 IS NOT NULL
                       AND i.fk_patologia2 > 0
                       AND c.cat LIKE 'I50%'
                    UNION
                    SELECT DISTINCT ia.fk_id_paciente AS patient_id
                      FROM tb_intern_antec ia
                      INNER JOIN tb_antecedente a
                              ON a.id_antecedente = ia.intern_antec_ant_int
                      INNER JOIN tb_cid c
                              ON c.id_cid = a.fk_cid_10_ant
                     WHERE ia.fk_id_paciente IS NOT NULL
                       AND c.cat LIKE 'I50%'
                ) src
             WHERE src.patient_id IS NOT NULL
               AND NOT EXISTS (
                    SELECT 1
                      FROM tb_paciente_cronico pc
                     WHERE pc.fk_paciente = src.patient_id
                       AND pc.condicao = 'Insuficiência cardíaca'
               )";

        $updateSql = "
            UPDATE tb_paciente_cronico pc
               JOIN (
                    SELECT DISTINCT i.fk_paciente_int AS patient_id
                      FROM tb_internacao i
                      INNER JOIN tb_antecedente a
                              ON a.id_antecedente = i.fk_patologia2
                      INNER JOIN tb_cid c
                              ON c.id_cid = a.fk_cid_10_ant
                     WHERE i.fk_paciente_int IS NOT NULL
                       AND i.fk_patologia2 IS NOT NULL
                       AND i.fk_patologia2 > 0
                       AND c.cat LIKE 'I50%'
                    UNION
                    SELECT DISTINCT ia.fk_id_paciente AS patient_id
                      FROM tb_intern_antec ia
                      INNER JOIN tb_antecedente a
                              ON a.id_antecedente = ia.intern_antec_ant_int
                      INNER JOIN tb_cid c
                              ON c.id_cid = a.fk_cid_10_ant
                     WHERE ia.fk_id_paciente IS NOT NULL
                       AND c.cat LIKE 'I50%'
               ) src
                 ON src.patient_id = pc.fk_paciente
               SET pc.status_acompanhamento = 'ativo',
                   pc.nivel_risco = 'alto',
                   pc.cid_codigo = CASE
                       WHEN COALESCE(pc.cid_codigo, '') = '' THEN 'I50'
                       ELSE pc.cid_codigo
                   END,
                   pc.proximo_contato = CASE
                       WHEN pc.proximo_contato IS NULL OR pc.proximo_contato < CURRENT_DATE()
                           THEN DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)
                       ELSE pc.proximo_contato
                   END
             WHERE pc.condicao = 'Insuficiência cardíaca'";

        try {
            $conn->exec($insertSql);
            $conn->exec($updateSql);
        } catch (Throwable $e) {
            error_log('[CRONICOS][SYNC_I50] ' . $e->getMessage());
        }
    }
}
