CREATE TABLE IF NOT EXISTS tb_solicitacao_customizacao (
    id_solicitacao INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120),
    empresa VARCHAR(120),
    cargo VARCHAR(120),
    email VARCHAR(160),
    telefone VARCHAR(40),
    data_solicitacao DATE,
    descricao TEXT,
    problema_atual TEXT,
    resultado_esperado TEXT,
    impacto_nivel ENUM('Baixo','Medio','Alto') NULL,
    descricao_impacto TEXT,
    prioridade ENUM('Urgente','Alta','Media','Baixa') NULL,
    prazo_desejado DATE,
    responsavel VARCHAR(120),
    assinatura VARCHAR(120),
    data_aprovacao DATE,
    aprovacao_conex VARCHAR(120),
    prazo_resposta DATE,
    precificacao TEXT,
    observacoes_resposta TEXT,
    aprovacao_resposta VARCHAR(120),
    data_resposta DATE,
    status ENUM('Aberto','Em analise','Resolvido','Cancelado') DEFAULT 'Aberto',
    resolvido_em DATETIME NULL,
    resolvido_por INT NULL,
    versao_sistema VARCHAR(40),
    data_create DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_create INT NULL,
    INDEX idx_status (status),
    INDEX idx_data (data_solicitacao),
    INDEX idx_usuario (usuario_create)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE tb_solicitacao_customizacao
    ADD COLUMN aprovacao_conex VARCHAR(120) NULL AFTER data_aprovacao;

CREATE TABLE IF NOT EXISTS tb_solicitacao_customizacao_modulo (
    id_modulo INT AUTO_INCREMENT PRIMARY KEY,
    fk_solicitacao INT NOT NULL,
    modulo VARCHAR(60) NOT NULL,
    modulo_outro VARCHAR(120) NULL,
    INDEX idx_fk_modulo (fk_solicitacao),
    CONSTRAINT fk_solicitacao_modulo FOREIGN KEY (fk_solicitacao)
        REFERENCES tb_solicitacao_customizacao (id_solicitacao)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tb_solicitacao_customizacao_tipo (
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    fk_solicitacao INT NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    INDEX idx_fk_tipo (fk_solicitacao),
    CONSTRAINT fk_solicitacao_tipo FOREIGN KEY (fk_solicitacao)
        REFERENCES tb_solicitacao_customizacao (id_solicitacao)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tb_solicitacao_customizacao_anexo (
    id_anexo INT AUTO_INCREMENT PRIMARY KEY,
    fk_solicitacao INT NOT NULL,
    tipo VARCHAR(60) NULL,
    nome_original VARCHAR(255) NULL,
    arquivo VARCHAR(255) NOT NULL,
    mime VARCHAR(100) NULL,
    tamanho INT NULL,
    data_create DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fk_anexo (fk_solicitacao),
    CONSTRAINT fk_solicitacao_anexo FOREIGN KEY (fk_solicitacao)
        REFERENCES tb_solicitacao_customizacao (id_solicitacao)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
