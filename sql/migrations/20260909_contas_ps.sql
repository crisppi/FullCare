CREATE TABLE IF NOT EXISTS tb_ps_lote (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 numero VARCHAR(60) NOT NULL,
 hospital_id INT NOT NULL,
 seguradora_id INT NOT NULL,
 competencia CHAR(7) NOT NULL,
 recebido_em DATE NOT NULL,
 criado_por INT NOT NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_ps_lote (hospital_id, seguradora_id, numero),
 KEY idx_ps_lote_scope (hospital_id, seguradora_id, competencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS tb_ps_conta (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 lote_id BIGINT UNSIGNED NOT NULL,
 numero VARCHAR(60) NOT NULL,
 paciente VARCHAR(180) NOT NULL,
 matricula VARCHAR(80) NOT NULL DEFAULT '',
 atendimento VARCHAR(80) NOT NULL,
 entrada DATETIME NOT NULL,
 saida DATETIME NULL,
 modalidade VARCHAR(20) NOT NULL,
 pacote VARCHAR(180) NOT NULL DEFAULT '',
 status VARCHAR(20) NOT NULL DEFAULT 'em_auditoria',
 observacao TEXT NOT NULL,
 cobrado DECIMAL(14,2) NOT NULL,
 glosado DECIMAL(14,2) NOT NULL,
 liberado DECIMAL(14,2) NOT NULL,
 versao INT NOT NULL DEFAULT 1,
 atualizado_por INT NOT NULL,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_ps_conta (lote_id, numero),
 CONSTRAINT fk_ps_conta_lote FOREIGN KEY (lote_id) REFERENCES tb_ps_lote(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS tb_ps_item (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 conta_id BIGINT UNSIGNED NOT NULL,
 categoria VARCHAR(30) NOT NULL,
 descricao VARCHAR(180) NOT NULL,
 quantidade INT NOT NULL,
 unitario DECIMAL(14,2) NOT NULL,
 glosa DECIMAL(14,2) NOT NULL,
 motivo VARCHAR(500) NOT NULL DEFAULT '',
 CONSTRAINT fk_ps_item_conta FOREIGN KEY (conta_id) REFERENCES tb_ps_conta(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS tb_ps_historico (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 conta_id BIGINT UNSIGNED NOT NULL,
 usuario_id INT NOT NULL,
 versao INT NOT NULL,
 registrado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 dados LONGTEXT NOT NULL,
 CONSTRAINT fk_ps_historico_conta FOREIGN KEY (conta_id) REFERENCES tb_ps_conta(id),
 UNIQUE KEY uq_ps_historico (conta_id, versao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
