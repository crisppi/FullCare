CREATE TABLE IF NOT EXISTS ge_escopo (
 id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, hospital_id INT NOT NULL,
 estipulante_id INT NOT NULL, todos_pacientes TINYINT NOT NULL DEFAULT 0,
 UNIQUE KEY escopo (usuario_id, hospital_id, estipulante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ge_caso (
 internacao_id INT PRIMARY KEY, responsavel_id INT NULL, previsao_alta DATE NULL,
 barreiras TEXT NULL, plano_alta TEXT NULL, alta_em DATETIME NULL, alta_destino VARCHAR(255) NULL,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 versao INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ge_evolucao (
 id INT AUTO_INCREMENT PRIMARY KEY, internacao_id INT NOT NULL, autor_id INT NOT NULL,
 dados LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'rascunho',
 complemento_de INT NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 finalizado_em DATETIME NULL, INDEX caso (internacao_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ge_pendencia (
 id INT AUTO_INCREMENT PRIMARY KEY, internacao_id INT NOT NULL, descricao TEXT NOT NULL,
 responsavel_id INT NOT NULL, prazo DATETIME NOT NULL, criado_por INT NOT NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, concluido_em DATETIME NULL,
 concluido_por INT NULL, INDEX caso (internacao_id, concluido_em, prazo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ge_historico (
 id INT AUTO_INCREMENT PRIMARY KEY, internacao_id INT NOT NULL, autor_id INT NOT NULL,
 acao VARCHAR(50) NOT NULL, dados LONGTEXT NOT NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX caso (internacao_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
