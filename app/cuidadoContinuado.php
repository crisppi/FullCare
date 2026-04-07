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
            "CREATE TABLE IF NOT EXISTS tb_cuidado_prelista (
                id_prelista INT AUTO_INCREMENT PRIMARY KEY,
                programa_sugerido ENUM('cronicos','preventiva') NOT NULL DEFAULT 'cronicos',
                fk_paciente INT NOT NULL,
                condicao VARCHAR(120) NOT NULL,
                nivel_risco ENUM('baixo','moderado','alto') NOT NULL DEFAULT 'moderado',
                origem_tipo VARCHAR(50) NOT NULL,
                origem_descricao VARCHAR(190) NULL,
                fk_internacao INT NULL,
                fk_visita INT NULL,
                resumo_clinico TEXT NULL,
                status_prelista ENUM('pendente','admitido','descartado') NOT NULL DEFAULT 'pendente',
                review_observacao TEXT NULL,
                reviewed_by INT NULL,
                reviewed_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_cc_prelista_programa (programa_sugerido),
                KEY idx_cc_prelista_paciente (fk_paciente),
                KEY idx_cc_prelista_status (status_prelista),
                KEY idx_cc_prelista_condicao (condicao),
                CONSTRAINT fk_cc_prelista_paciente
                    FOREIGN KEY (fk_paciente) REFERENCES tb_paciente(id_paciente)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS tb_paciente_preventivo (
                id_preventivo INT AUTO_INCREMENT PRIMARY KEY,
                fk_paciente INT NOT NULL,
                fk_cronico INT NULL,
                foco_monitoramento VARCHAR(150) NOT NULL,
                nivel_risco ENUM('baixo','moderado','alto') NOT NULL DEFAULT 'moderado',
                status_monitoramento ENUM('ativo','monitoramento','encerrado') NOT NULL DEFAULT 'ativo',
                canal_preferencial ENUM('telefone','whatsapp','visita','indefinido') NOT NULL DEFAULT 'telefone',
                ultima_interacao DATE NULL,
                proximo_contato DATE NULL,
                observacoes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_cc_prev_paciente (fk_paciente),
                KEY idx_cc_prev_cronico (fk_cronico),
                KEY idx_cc_prev_status (status_monitoramento),
                CONSTRAINT fk_cc_prev_paciente
                    FOREIGN KEY (fk_paciente) REFERENCES tb_paciente(id_paciente)
                    ON DELETE CASCADE,
                CONSTRAINT fk_cc_prev_cronico
                    FOREIGN KEY (fk_cronico) REFERENCES tb_paciente_cronico(id_cronico)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS tb_cuidado_acompanhamento (
                id_acompanhamento INT AUTO_INCREMENT PRIMARY KEY,
                programa ENUM('cronicos','preventiva') NOT NULL,
                fk_paciente INT NOT NULL,
                fk_cronico INT NULL,
                fk_preventivo INT NULL,
                fk_prelista INT NULL,
                tipo_acao VARCHAR(40) NOT NULL,
                realizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                proximo_contato DATE NULL,
                responsavel VARCHAR(120) NULL,
                observacoes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_cc_acomp_programa (programa),
                KEY idx_cc_acomp_paciente (fk_paciente),
                KEY idx_cc_acomp_cronico (fk_cronico),
                KEY idx_cc_acomp_preventivo (fk_preventivo),
                CONSTRAINT fk_cc_acomp_paciente
                    FOREIGN KEY (fk_paciente) REFERENCES tb_paciente(id_paciente)
                    ON DELETE CASCADE
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
        $prelista = $conn->query("SELECT COUNT(*) FROM tb_cuidado_prelista WHERE programa_sugerido = 'cronicos' AND status_prelista = 'pendente'")->fetchColumn();

        return [
            'total' => (int)($row['total'] ?? 0),
            'alto_risco' => (int)($row['alto_risco'] ?? 0),
            'ativos' => (int)($row['ativos'] ?? 0),
            'pendentes' => (int)($row['pendentes'] ?? 0),
            'prelista' => (int)$prelista,
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
                    c.data_diagnostico,
                    (
                        SELECT a.tipo_acao
                          FROM tb_cuidado_acompanhamento a
                         WHERE a.programa = 'cronicos'
                           AND a.fk_cronico = c.id_cronico
                         ORDER BY a.realizado_em DESC, a.id_acompanhamento DESC
                         LIMIT 1
                    ) AS ultima_acao,
                    (
                        SELECT a.realizado_em
                          FROM tb_cuidado_acompanhamento a
                         WHERE a.programa = 'cronicos'
                           AND a.fk_cronico = c.id_cronico
                         ORDER BY a.realizado_em DESC, a.id_acompanhamento DESC
                         LIMIT 1
                    ) AS ultima_acao_em
                FROM tb_paciente_cronico c
                INNER JOIN tb_paciente p ON p.id_paciente = c.fk_paciente
                WHERE c.status_acompanhamento <> 'encerrado'";

        if ($where) {
            $sql .= ' AND ' . implode(' AND ', $where);
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

        $status = $conn->query("SELECT
                COUNT(*) AS total_pacientes,
                SUM(CASE WHEN status_monitoramento = 'ativo' THEN 1 ELSE 0 END) AS ativos,
                SUM(CASE WHEN status_monitoramento <> 'encerrado'
                           AND (proximo_contato IS NULL OR proximo_contato < CURRENT_DATE())
                         THEN 1 ELSE 0 END) AS pendentes,
                SUM(CASE WHEN nivel_risco = 'alto' THEN 1 ELSE 0 END) AS alto_risco
            FROM tb_paciente_preventivo")->fetch(PDO::FETCH_ASSOC) ?: [];
        $elegiveis = $conn->query("
            SELECT COUNT(*)
              FROM tb_paciente_cronico c
             WHERE c.status_acompanhamento IN ('ativo', 'monitoramento')
               AND NOT EXISTS (
                    SELECT 1
                      FROM tb_paciente_preventivo p
                     WHERE p.fk_cronico = c.id_cronico
                       AND p.status_monitoramento <> 'encerrado'
               )
        ")->fetchColumn();

        return [
            'campanhas_total' => 0,
            'campanhas_ativas' => 0,
            'elegiveis' => (int)$elegiveis,
            'convocados' => 0,
            'concluidos' => 0,
            'total_pacientes' => (int)($status['total_pacientes'] ?? 0),
            'ativos' => (int)($status['ativos'] ?? 0),
            'pendentes' => (int)($status['pendentes'] ?? 0),
            'alto_risco' => (int)($status['alto_risco'] ?? 0),
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
                    c.proximo_contato AS proximo_contato_cronico
                FROM tb_paciente_cronico c
                INNER JOIN tb_paciente p ON p.id_paciente = c.fk_paciente
                WHERE c.status_acompanhamento IN ('ativo', 'monitoramento')
                  AND NOT EXISTS (
                        SELECT 1
                          FROM tb_paciente_preventivo pp
                         WHERE pp.fk_cronico = c.id_cronico
                           AND pp.status_monitoramento <> 'encerrado'
                  )
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

if (!function_exists('cc_excerpt_text')) {
    function cc_excerpt_text(?string $text, int $limit = 220): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string)$text));
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_substr') && function_exists('mb_strlen')) {
            return mb_strlen($text, 'UTF-8') > $limit ? mb_substr($text, 0, $limit, 'UTF-8') . '...' : $text;
        }
        return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
    }
}

if (!function_exists('cc_append_note')) {
    function cc_append_note(?string $base, string $note): string
    {
        $base = trim((string)$base);
        $note = trim($note);
        if ($base === '') {
            return $note;
        }
        if ($note === '') {
            return $base;
        }
        return $base . "\n" . $note;
    }
}

if (!function_exists('cc_enqueue_chronic_candidate')) {
    function cc_enqueue_chronic_candidate(PDO $conn, int $patientId, string $condition, string $risk, string $source, array $meta = []): bool
    {
        ensure_cuidado_continuado_schema($conn);

        if ($patientId <= 0 || trim($condition) === '') {
            return false;
        }

        $risk = in_array($risk, ['baixo', 'moderado', 'alto'], true) ? $risk : 'moderado';
        $sourceType = trim((string)($meta['origem_tipo'] ?? 'auditoria'));
        $sourceDesc = trim((string)($meta['origem_descricao'] ?? $source));
        $summary = cc_excerpt_text((string)($meta['resumo_clinico'] ?? ''), 220);
        $fkInternacao = (int)($meta['fk_internacao'] ?? 0);
        $fkVisita = (int)($meta['fk_visita'] ?? 0);

        $select = $conn->prepare("
            SELECT id_prelista, nivel_risco, resumo_clinico, origem_descricao
              FROM tb_cuidado_prelista
             WHERE programa_sugerido = 'cronicos'
               AND fk_paciente = :patient_id
               AND condicao = :condition
               AND status_prelista = 'pendente'
             ORDER BY id_prelista DESC
             LIMIT 1
        ");
        $select->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $select->bindValue(':condition', $condition, PDO::PARAM_STR);
        $select->execute();
        $existing = $select->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($existing) {
            $newRisk = $existing['nivel_risco'] === 'alto' || $risk === 'alto'
                ? 'alto'
                : (($existing['nivel_risco'] === 'moderado' || $risk === 'moderado') ? 'moderado' : 'baixo');
            $update = $conn->prepare("
                UPDATE tb_cuidado_prelista
                   SET nivel_risco = :risk,
                       origem_descricao = :source_desc,
                       resumo_clinico = :summary
                 WHERE id_prelista = :id
            ");
            $update->bindValue(':risk', $newRisk, PDO::PARAM_STR);
            $update->bindValue(':source_desc', $sourceDesc, PDO::PARAM_STR);
            $update->bindValue(':summary', $summary !== '' ? $summary : ($existing['resumo_clinico'] ?? null), $summary !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $update->bindValue(':id', (int)$existing['id_prelista'], PDO::PARAM_INT);
            return $update->execute();
        }

        $insert = $conn->prepare("
            INSERT INTO tb_cuidado_prelista (
                programa_sugerido,
                fk_paciente,
                condicao,
                nivel_risco,
                origem_tipo,
                origem_descricao,
                fk_internacao,
                fk_visita,
                resumo_clinico
            ) VALUES (
                'cronicos',
                :patient_id,
                :condition,
                :risk,
                :source_type,
                :source_desc,
                :fk_internacao,
                :fk_visita,
                :summary
            )
        ");
        $insert->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $insert->bindValue(':condition', $condition, PDO::PARAM_STR);
        $insert->bindValue(':risk', $risk, PDO::PARAM_STR);
        $insert->bindValue(':source_type', $sourceType, PDO::PARAM_STR);
        $insert->bindValue(':source_desc', $sourceDesc, PDO::PARAM_STR);
        $insert->bindValue(':fk_internacao', $fkInternacao > 0 ? $fkInternacao : null, $fkInternacao > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $insert->bindValue(':fk_visita', $fkVisita > 0 ? $fkVisita : null, $fkVisita > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $insert->bindValue(':summary', $summary !== '' ? $summary : null, $summary !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        return $insert->execute();
    }
}

if (!function_exists('cc_enqueue_chronic_candidates_from_text')) {
    function cc_enqueue_chronic_candidates_from_text(PDO $conn, int $patientId, ?string $text, string $source, array $meta = []): array
    {
        ensure_cuidado_continuado_schema($conn);

        if ($patientId <= 0) {
            return [];
        }

        $detected = cc_detect_chronic_conditions($text);
        if (!$detected) {
            return [];
        }

        $saved = [];
        foreach ($detected as $item) {
            $localMeta = $meta;
            if (!isset($localMeta['resumo_clinico']) || trim((string)$localMeta['resumo_clinico']) === '') {
                $localMeta['resumo_clinico'] = (string)$text;
            }
            if (cc_enqueue_chronic_candidate($conn, $patientId, (string)$item['condicao'], (string)$item['risco'], $source, $localMeta)) {
                $saved[] = (string)$item['condicao'];
            }
        }

        return array_values(array_unique($saved));
    }
}

if (!function_exists('cc_enqueue_chronic_candidates_from_antecedent_names')) {
    function cc_enqueue_chronic_candidates_from_antecedent_names(PDO $conn, int $patientId, array $antecedentNames, string $source = '', array $meta = []): array
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

        return cc_enqueue_chronic_candidates_from_text(
            $conn,
            $patientId,
            implode("\n", $chunks),
            $source !== '' ? $source : 'antecedentes do paciente',
            $meta
        );
    }
}

if (!function_exists('cc_enqueue_chronic_candidates_from_antecedent_id')) {
    function cc_enqueue_chronic_candidates_from_antecedent_id(PDO $conn, int $patientId, int $antecedentId, string $source = '', array $meta = []): array
    {
        $name = cc_fetch_antecedent_name_by_id($conn, $antecedentId);
        if ($name === null || $name === '') {
            return [];
        }

        return cc_enqueue_chronic_candidates_from_antecedent_names(
            $conn,
            $patientId,
            [$name],
            $source !== '' ? $source : 'antecedente selecionado na internação',
            $meta
        );
    }
}

if (!function_exists('cc_fetch_cronicos_prelist')) {
    function cc_fetch_cronicos_prelist(PDO $conn, string $search = '', string $risk = ''): array
    {
        ensure_cuidado_continuado_schema($conn);

        $where = ["pl.programa_sugerido = 'cronicos'", "pl.status_prelista = 'pendente'"];
        $params = [];
        if ($search !== '') {
            $where[] = '(p.nome_pac LIKE :search OR p.matricula_pac LIKE :search OR pl.condicao LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        if (in_array($risk, ['baixo', 'moderado', 'alto'], true)) {
            $where[] = 'pl.nivel_risco = :risk';
            $params[':risk'] = $risk;
        }

        $sql = "SELECT
                    pl.id_prelista,
                    pl.fk_paciente,
                    pl.condicao,
                    pl.nivel_risco,
                    pl.origem_tipo,
                    pl.origem_descricao,
                    pl.fk_internacao,
                    pl.fk_visita,
                    pl.resumo_clinico,
                    pl.created_at,
                    p.nome_pac,
                    p.matricula_pac
                FROM tb_cuidado_prelista pl
                INNER JOIN tb_paciente p ON p.id_paciente = pl.fk_paciente
                WHERE " . implode(' AND ', $where) . "
                ORDER BY
                    CASE pl.nivel_risco
                        WHEN 'alto' THEN 1
                        WHEN 'moderado' THEN 2
                        ELSE 3
                    END,
                    pl.created_at DESC
                LIMIT 100";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('cc_fetch_program_actions')) {
    function cc_fetch_program_actions(PDO $conn, string $program, int $limit = 20): array
    {
        ensure_cuidado_continuado_schema($conn);

        if (!in_array($program, ['cronicos', 'preventiva'], true)) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $sql = "SELECT
                    a.id_acompanhamento,
                    a.programa,
                    a.tipo_acao,
                    a.realizado_em,
                    a.proximo_contato,
                    a.responsavel,
                    a.observacoes,
                    p.nome_pac,
                    p.matricula_pac,
                    COALESCE(c.condicao, pv.foco_monitoramento) AS foco
                FROM tb_cuidado_acompanhamento a
                INNER JOIN tb_paciente p ON p.id_paciente = a.fk_paciente
                LEFT JOIN tb_paciente_cronico c ON c.id_cronico = a.fk_cronico
                LEFT JOIN tb_paciente_preventivo pv ON pv.id_preventivo = a.fk_preventivo
                WHERE a.programa = :program
                ORDER BY a.realizado_em DESC, a.id_acompanhamento DESC
                LIMIT {$limit}";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':program', $program, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('cc_admit_cronico_candidate')) {
    function cc_admit_cronico_candidate(PDO $conn, int $candidateId, int $userId = 0, string $notes = ''): bool
    {
        ensure_cuidado_continuado_schema($conn);

        if ($candidateId <= 0) {
            return false;
        }

        $candidateStmt = $conn->prepare("
            SELECT *
              FROM tb_cuidado_prelista
             WHERE id_prelista = :id
               AND programa_sugerido = 'cronicos'
             LIMIT 1
        ");
        $candidateStmt->bindValue(':id', $candidateId, PDO::PARAM_INT);
        $candidateStmt->execute();
        $candidate = $candidateStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$candidate || ($candidate['status_prelista'] ?? '') !== 'pendente') {
            return false;
        }

        $select = $conn->prepare("
            SELECT id_cronico, nivel_risco, observacoes
              FROM tb_paciente_cronico
             WHERE fk_paciente = :patient_id
               AND condicao = :condition
             ORDER BY id_cronico DESC
             LIMIT 1
        ");
        $select->bindValue(':patient_id', (int)$candidate['fk_paciente'], PDO::PARAM_INT);
        $select->bindValue(':condition', (string)$candidate['condicao'], PDO::PARAM_STR);
        $select->execute();
        $existing = $select->fetch(PDO::FETCH_ASSOC) ?: null;

        $obsBase = 'Admitido via pré-lista de auditoria em ' . date('d/m/Y H:i');
        if ($notes !== '') {
            $obsBase .= ' - ' . $notes;
        }

        $conn->beginTransaction();
        try {
            if ($existing) {
                $risk = ($existing['nivel_risco'] ?? '') === 'alto' || ($candidate['nivel_risco'] ?? '') === 'alto'
                    ? 'alto'
                    : ((($existing['nivel_risco'] ?? '') === 'moderado' || ($candidate['nivel_risco'] ?? '') === 'moderado') ? 'moderado' : 'baixo');

                $update = $conn->prepare("
                    UPDATE tb_paciente_cronico
                       SET status_acompanhamento = 'ativo',
                           nivel_risco = :risk,
                           ultima_consulta = CURRENT_DATE(),
                           proximo_contato = CASE
                               WHEN proximo_contato IS NULL OR proximo_contato < CURRENT_DATE()
                                   THEN DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)
                               ELSE proximo_contato
                           END,
                           observacoes = :obs
                     WHERE id_cronico = :id
                ");
                $update->bindValue(':risk', $risk, PDO::PARAM_STR);
                $update->bindValue(':obs', cc_append_note((string)($existing['observacoes'] ?? ''), $obsBase), PDO::PARAM_STR);
                $update->bindValue(':id', (int)$existing['id_cronico'], PDO::PARAM_INT);
                $update->execute();
                $cronicoId = (int)$existing['id_cronico'];
            } else {
                $insert = $conn->prepare("
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
                        :condition,
                        'ativo',
                        :risk,
                        CURRENT_DATE(),
                        CURRENT_DATE(),
                        DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY),
                        :obs
                    )
                ");
                $insert->bindValue(':patient_id', (int)$candidate['fk_paciente'], PDO::PARAM_INT);
                $insert->bindValue(':condition', (string)$candidate['condicao'], PDO::PARAM_STR);
                $insert->bindValue(':risk', (string)$candidate['nivel_risco'], PDO::PARAM_STR);
                $insert->bindValue(':obs', $obsBase, PDO::PARAM_STR);
                $insert->execute();
                $cronicoId = (int)$conn->lastInsertId();
            }

            $log = $conn->prepare("
                INSERT INTO tb_cuidado_acompanhamento (
                    programa, fk_paciente, fk_cronico, fk_prelista, tipo_acao, proximo_contato, responsavel, observacoes
                ) VALUES (
                    'cronicos', :patient_id, :cronico_id, :prelista_id, 'admissao', DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY), :responsavel, :obs
                )
            ");
            $log->bindValue(':patient_id', (int)$candidate['fk_paciente'], PDO::PARAM_INT);
            $log->bindValue(':cronico_id', $cronicoId, PDO::PARAM_INT);
            $log->bindValue(':prelista_id', $candidateId, PDO::PARAM_INT);
            $log->bindValue(':responsavel', $userId > 0 ? ('Usuário #' . $userId) : null, $userId > 0 ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $log->bindValue(':obs', $obsBase, PDO::PARAM_STR);
            $log->execute();

            $mark = $conn->prepare("
                UPDATE tb_cuidado_prelista
                   SET status_prelista = 'admitido',
                       review_observacao = :notes,
                       reviewed_by = :user_id,
                       reviewed_at = NOW()
                 WHERE id_prelista = :id
            ");
            $mark->bindValue(':notes', $notes !== '' ? $notes : null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $mark->bindValue(':user_id', $userId > 0 ? $userId : null, $userId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $mark->bindValue(':id', $candidateId, PDO::PARAM_INT);
            $mark->execute();

            $conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('[CUIDADO_CONTINUADO][ADMIT_CRONICO] ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('cc_discard_candidate')) {
    function cc_discard_candidate(PDO $conn, int $candidateId, int $userId = 0, string $notes = ''): bool
    {
        ensure_cuidado_continuado_schema($conn);

        $stmt = $conn->prepare("
            UPDATE tb_cuidado_prelista
               SET status_prelista = 'descartado',
                   review_observacao = :notes,
                   reviewed_by = :user_id,
                   reviewed_at = NOW()
             WHERE id_prelista = :id
               AND status_prelista = 'pendente'
        ");
        $stmt->bindValue(':notes', $notes !== '' ? $notes : 'Descartado manualmente', PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId > 0 ? $userId : null, $userId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $candidateId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

if (!function_exists('cc_register_cronico_followup')) {
    function cc_register_cronico_followup(PDO $conn, int $cronicoId, string $actionType, ?string $nextContact, ?string $notes, string $responsavel = ''): bool
    {
        ensure_cuidado_continuado_schema($conn);

        $allowed = ['ligacao', 'visita_medica', 'visita_enfermagem', 'orientacao', 'encerramento'];
        if ($cronicoId <= 0 || !in_array($actionType, $allowed, true)) {
            return false;
        }

        $cronicoStmt = $conn->prepare("SELECT id_cronico, fk_paciente, observacoes FROM tb_paciente_cronico WHERE id_cronico = :id LIMIT 1");
        $cronicoStmt->bindValue(':id', $cronicoId, PDO::PARAM_INT);
        $cronicoStmt->execute();
        $cronico = $cronicoStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$cronico) {
            return false;
        }

        $obs = trim((string)$notes);
        $conn->beginTransaction();
        try {
            $log = $conn->prepare("
                INSERT INTO tb_cuidado_acompanhamento (
                    programa, fk_paciente, fk_cronico, tipo_acao, proximo_contato, responsavel, observacoes
                ) VALUES (
                    'cronicos', :patient_id, :cronico_id, :type, :next_contact, :responsavel, :obs
                )
            ");
            $log->bindValue(':patient_id', (int)$cronico['fk_paciente'], PDO::PARAM_INT);
            $log->bindValue(':cronico_id', $cronicoId, PDO::PARAM_INT);
            $log->bindValue(':type', $actionType, PDO::PARAM_STR);
            $log->bindValue(':next_contact', $nextContact ?: null, $nextContact ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $log->bindValue(':responsavel', $responsavel !== '' ? $responsavel : null, $responsavel !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $log->bindValue(':obs', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $log->execute();

            $update = $conn->prepare("
                UPDATE tb_paciente_cronico
                   SET ultima_consulta = CURRENT_DATE(),
                       proximo_contato = :next_contact,
                       status_acompanhamento = :status,
                       observacoes = :obs
                 WHERE id_cronico = :id
            ");
            $status = $actionType === 'encerramento' ? 'encerrado' : 'ativo';
            $noteLine = ucfirst(str_replace('_', ' ', $actionType)) . ' registrada em ' . date('d/m/Y H:i') . ($obs !== '' ? ' - ' . $obs : '');
            $update->bindValue(':next_contact', $nextContact ?: null, $nextContact ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $update->bindValue(':status', $status, PDO::PARAM_STR);
            $update->bindValue(':obs', cc_append_note((string)($cronico['observacoes'] ?? ''), $noteLine), PDO::PARAM_STR);
            $update->bindValue(':id', $cronicoId, PDO::PARAM_INT);
            $update->execute();

            $conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('[CUIDADO_CONTINUADO][FOLLOWUP_CRONICO] ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('cc_fetch_preventiva_active')) {
    function cc_fetch_preventiva_active(PDO $conn, string $search = ''): array
    {
        ensure_cuidado_continuado_schema($conn);

        $sql = "SELECT
                    pprev.id_preventivo,
                    pprev.fk_paciente,
                    pprev.fk_cronico,
                    pprev.foco_monitoramento,
                    pprev.nivel_risco,
                    pprev.status_monitoramento,
                    pprev.ultima_interacao,
                    pprev.proximo_contato,
                    pac.nome_pac,
                    pac.matricula_pac,
                    (
                        SELECT a.tipo_acao
                          FROM tb_cuidado_acompanhamento a
                         WHERE a.programa = 'preventiva'
                           AND a.fk_preventivo = pprev.id_preventivo
                         ORDER BY a.realizado_em DESC, a.id_acompanhamento DESC
                         LIMIT 1
                    ) AS ultima_acao,
                    (
                        SELECT a.realizado_em
                          FROM tb_cuidado_acompanhamento a
                         WHERE a.programa = 'preventiva'
                           AND a.fk_preventivo = pprev.id_preventivo
                         ORDER BY a.realizado_em DESC, a.id_acompanhamento DESC
                         LIMIT 1
                    ) AS ultima_acao_em
                FROM tb_paciente_preventivo pprev
                INNER JOIN tb_paciente pac ON pac.id_paciente = pprev.fk_paciente
                WHERE pprev.status_monitoramento <> 'encerrado'";
        $params = [];
        if ($search !== '') {
            $searchLike = '%' . $search . '%';
            $sql .= " AND (
                        pac.nome_pac LIKE :search_nome
                        OR pac.matricula_pac LIKE :search_matricula
                        OR pprev.foco_monitoramento LIKE :search_foco
                     )";
            $params[':search_nome'] = $searchLike;
            $params[':search_matricula'] = $searchLike;
            $params[':search_foco'] = $searchLike;
        }
        $sql .= " ORDER BY
                    CASE pprev.nivel_risco
                        WHEN 'alto' THEN 1
                        WHEN 'moderado' THEN 2
                        ELSE 3
                    END,
                    pprev.proximo_contato IS NULL DESC,
                    pprev.proximo_contato ASC,
                    pac.nome_pac ASC
                  LIMIT 100";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('cc_admit_preventiva_from_cronico')) {
    function cc_admit_preventiva_from_cronico(PDO $conn, int $cronicoId, int $userId = 0, string $notes = ''): bool
    {
        ensure_cuidado_continuado_schema($conn);

        if ($cronicoId <= 0) {
            return false;
        }

        $cronicoStmt = $conn->prepare("
            SELECT id_cronico, fk_paciente, condicao, nivel_risco, observacoes
              FROM tb_paciente_cronico
             WHERE id_cronico = :id
             LIMIT 1
        ");
        $cronicoStmt->bindValue(':id', $cronicoId, PDO::PARAM_INT);
        $cronicoStmt->execute();
        $cronico = $cronicoStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$cronico) {
            return false;
        }

        $select = $conn->prepare("
            SELECT id_preventivo, observacoes
              FROM tb_paciente_preventivo
             WHERE fk_cronico = :cronico_id
             ORDER BY id_preventivo DESC
             LIMIT 1
        ");
        $select->bindValue(':cronico_id', $cronicoId, PDO::PARAM_INT);
        $select->execute();
        $existing = $select->fetch(PDO::FETCH_ASSOC) ?: null;

        $obsBase = 'Admitido em medicina preventiva em ' . date('d/m/Y H:i');
        if ($notes !== '') {
            $obsBase .= ' - ' . $notes;
        }

        $conn->beginTransaction();
        try {
            if ($existing) {
                $update = $conn->prepare("
                    UPDATE tb_paciente_preventivo
                       SET status_monitoramento = 'ativo',
                           nivel_risco = :risk,
                           foco_monitoramento = :focus,
                           canal_preferencial = 'telefone',
                           ultima_interacao = CURRENT_DATE(),
                           proximo_contato = CASE
                               WHEN proximo_contato IS NULL OR proximo_contato < CURRENT_DATE()
                                   THEN DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY)
                               ELSE proximo_contato
                           END,
                           observacoes = :obs
                     WHERE id_preventivo = :id
                ");
                $update->bindValue(':risk', (string)$cronico['nivel_risco'], PDO::PARAM_STR);
                $update->bindValue(':focus', (string)$cronico['condicao'], PDO::PARAM_STR);
                $update->bindValue(':obs', cc_append_note((string)($existing['observacoes'] ?? ''), $obsBase), PDO::PARAM_STR);
                $update->bindValue(':id', (int)$existing['id_preventivo'], PDO::PARAM_INT);
                $update->execute();
                $preventivoId = (int)$existing['id_preventivo'];
            } else {
                $insert = $conn->prepare("
                    INSERT INTO tb_paciente_preventivo (
                        fk_paciente,
                        fk_cronico,
                        foco_monitoramento,
                        nivel_risco,
                        status_monitoramento,
                        canal_preferencial,
                        ultima_interacao,
                        proximo_contato,
                        observacoes
                    ) VALUES (
                        :patient_id,
                        :cronico_id,
                        :focus,
                        :risk,
                        'ativo',
                        'telefone',
                        CURRENT_DATE(),
                        DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY),
                        :obs
                    )
                ");
                $insert->bindValue(':patient_id', (int)$cronico['fk_paciente'], PDO::PARAM_INT);
                $insert->bindValue(':cronico_id', $cronicoId, PDO::PARAM_INT);
                $insert->bindValue(':focus', (string)$cronico['condicao'], PDO::PARAM_STR);
                $insert->bindValue(':risk', (string)$cronico['nivel_risco'], PDO::PARAM_STR);
                $insert->bindValue(':obs', $obsBase, PDO::PARAM_STR);
                $insert->execute();
                $preventivoId = (int)$conn->lastInsertId();
            }

            $log = $conn->prepare("
                INSERT INTO tb_cuidado_acompanhamento (
                    programa, fk_paciente, fk_cronico, fk_preventivo, tipo_acao, proximo_contato, responsavel, observacoes
                ) VALUES (
                    'preventiva', :patient_id, :cronico_id, :preventivo_id, 'admissao', DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY), :responsavel, :obs
                )
            ");
            $log->bindValue(':patient_id', (int)$cronico['fk_paciente'], PDO::PARAM_INT);
            $log->bindValue(':cronico_id', $cronicoId, PDO::PARAM_INT);
            $log->bindValue(':preventivo_id', $preventivoId, PDO::PARAM_INT);
            $log->bindValue(':responsavel', $userId > 0 ? ('Usuário #' . $userId) : null, $userId > 0 ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $log->bindValue(':obs', $obsBase, PDO::PARAM_STR);
            $log->execute();

            $conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('[CUIDADO_CONTINUADO][ADMIT_PREVENTIVA] ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('cc_register_preventiva_followup')) {
    function cc_register_preventiva_followup(PDO $conn, int $preventivoId, ?string $nextContact, ?string $notes, string $responsavel = '', string $actionType = 'monitoramento_telefonico'): bool
    {
        ensure_cuidado_continuado_schema($conn);

        $allowed = ['monitoramento_telefonico', 'orientacao', 'encerramento'];
        if ($preventivoId <= 0 || !in_array($actionType, $allowed, true)) {
            return false;
        }

        $stmt = $conn->prepare("SELECT id_preventivo, fk_paciente, fk_cronico, observacoes FROM tb_paciente_preventivo WHERE id_preventivo = :id LIMIT 1");
        $stmt->bindValue(':id', $preventivoId, PDO::PARAM_INT);
        $stmt->execute();
        $preventivo = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$preventivo) {
            return false;
        }

        $obs = trim((string)$notes);
        $conn->beginTransaction();
        try {
            $log = $conn->prepare("
                INSERT INTO tb_cuidado_acompanhamento (
                    programa, fk_paciente, fk_cronico, fk_preventivo, tipo_acao, proximo_contato, responsavel, observacoes
                ) VALUES (
                    'preventiva', :patient_id, :cronico_id, :preventivo_id, :type, :next_contact, :responsavel, :obs
                )
            ");
            $log->bindValue(':patient_id', (int)$preventivo['fk_paciente'], PDO::PARAM_INT);
            $log->bindValue(':cronico_id', !empty($preventivo['fk_cronico']) ? (int)$preventivo['fk_cronico'] : null, !empty($preventivo['fk_cronico']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $log->bindValue(':preventivo_id', $preventivoId, PDO::PARAM_INT);
            $log->bindValue(':type', $actionType, PDO::PARAM_STR);
            $log->bindValue(':next_contact', $nextContact ?: null, $nextContact ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $log->bindValue(':responsavel', $responsavel !== '' ? $responsavel : null, $responsavel !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $log->bindValue(':obs', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $log->execute();

            $update = $conn->prepare("
                UPDATE tb_paciente_preventivo
                   SET ultima_interacao = CURRENT_DATE(),
                       proximo_contato = :next_contact,
                       status_monitoramento = :status,
                       observacoes = :obs
                 WHERE id_preventivo = :id
            ");
            $status = $actionType === 'encerramento' ? 'encerrado' : 'ativo';
            $noteLine = ucfirst(str_replace('_', ' ', $actionType)) . ' registrada em ' . date('d/m/Y H:i') . ($obs !== '' ? ' - ' . $obs : '');
            $update->bindValue(':next_contact', $nextContact ?: null, $nextContact ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $update->bindValue(':status', $status, PDO::PARAM_STR);
            $update->bindValue(':obs', cc_append_note((string)($preventivo['observacoes'] ?? ''), $noteLine), PDO::PARAM_STR);
            $update->bindValue(':id', $preventivoId, PDO::PARAM_INT);
            $update->execute();

            $conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('[CUIDADO_CONTINUADO][FOLLOWUP_PREVENTIVA] ' . $e->getMessage());
            return false;
        }
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

        $sql = "
            SELECT DISTINCT src.patient_id
              FROM (
                    SELECT i.fk_paciente_int AS patient_id
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
                    SELECT ia.fk_id_paciente AS patient_id
                      FROM tb_intern_antec ia
                      INNER JOIN tb_antecedente a
                              ON a.id_antecedente = ia.intern_antec_ant_int
                      INNER JOIN tb_cid c
                              ON c.id_cid = a.fk_cid_10_ant
                     WHERE ia.fk_id_paciente IS NOT NULL
                       AND c.cat LIKE 'I50%'
                ) src
             WHERE src.patient_id IS NOT NULL
        ";

        try {
            $patients = $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($patients as $patientId) {
                cc_enqueue_chronic_candidate(
                    $conn,
                    (int)$patientId,
                    'Insuficiência cardíaca',
                    'alto',
                    'CID I50 identificado no histórico',
                    [
                        'origem_tipo' => 'cid_historico',
                        'origem_descricao' => 'CID I50 identificado no histórico do paciente',
                        'resumo_clinico' => 'Paciente com histórico compatível com insuficiência cardíaca (CID I50).'
                    ]
                );
            }
        } catch (Throwable $e) {
            error_log('[CRONICOS][SYNC_I50] ' . $e->getMessage());
        }
    }
}
