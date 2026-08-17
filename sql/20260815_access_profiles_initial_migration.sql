-- FullCare: estrutura inicial de cargos, perfis e permissões.
-- Esta migração associa os usuários atuais, mas NÃO ativa a nova autorização.
-- O campo legado tb_user.nivel_user é preservado durante a transição.

CREATE TABLE IF NOT EXISTS tb_access_profile (
    id_access_profile INT NOT NULL,
    nome VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    descricao VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    protegido TINYINT(1) NOT NULL DEFAULT 0,
    criado_por INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_access_profile),
    UNIQUE KEY uk_access_profile_slug (slug),
    UNIQUE KEY uk_access_profile_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_access_profile_permission (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    profile_id INT NOT NULL,
    module VARCHAR(80) NOT NULL,
    action VARCHAR(80) NOT NULL,
    allowed TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_profile_module_action (profile_id, module, action),
    CONSTRAINT fk_profile_permission_profile
        FOREIGN KEY (profile_id) REFERENCES tb_access_profile (id_access_profile)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_user_permission_override (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    module VARCHAR(80) NOT NULL,
    action VARCHAR(80) NOT NULL,
    allowed TINYINT(1) NOT NULL,
    motivo VARCHAR(255) NULL,
    concedido_por INT NULL,
    concedido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_user_module_action (user_id, module, action),
    KEY idx_override_actor (concedido_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_access_audit (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id INT NULL,
    target_user_id INT NULL,
    evento VARCHAR(100) NOT NULL,
    valor_anterior LONGTEXT NULL,
    valor_novo LONGTEXT NULL,
    motivo VARCHAR(255) NULL,
    ip VARCHAR(45) NULL,
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_access_audit_actor (actor_user_id),
    KEY idx_access_audit_target (target_user_id),
    KEY idx_access_audit_evento (evento),
    KEY idx_access_audit_data (data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE tb_user
    ADD COLUMN IF NOT EXISTS fk_access_profile INT NULL AFTER nivel_user,
    ADD KEY IF NOT EXISTS idx_user_access_profile (fk_access_profile);

INSERT INTO tb_access_profile
    (id_access_profile, nome, slug, descricao, ativo, protegido)
VALUES
    (1, 'Consulta', 'consulta', 'Somente leitura limitada.', 1, 1),
    (2, 'Secretaria', 'secretaria', 'Operação cadastral e administrativa básica.', 1, 1),
    (3, 'Assistencial', 'assistencial', 'Perfil comum para médicos e enfermeiros.', 1, 1),
    (4, 'Administrativo', 'administrativo', 'Contas, capeantes, RAH e operação administrativa.', 1, 1),
    (5, 'Gerencial', 'gerencial', 'Supervisão e decisões operacionais.', 1, 1),
    (6, 'Diretoria', 'diretoria', 'Visão estratégica e financeira.', 1, 1),
    (9, 'Superadministrador', 'superadministrador', 'Administração completa do sistema e das permissões.', 1, 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    slug = VALUES(slug),
    descricao = VALUES(descricao),
    ativo = VALUES(ativo),
    protegido = VALUES(protegido);

-- Perfil padrão seguro para qualquer usuário ainda não classificado.
UPDATE tb_user
SET fk_access_profile = 1
WHERE fk_access_profile IS NULL;

-- Assistencial: médicos, enfermeiros e variações de auditoria.
UPDATE tb_user
SET fk_access_profile = 3
WHERE LOWER(TRIM(cargo_user)) IN (
    'médico', 'medico', 'med_auditor',
    'enfermeiro', 'enfermeira', 'enf_auditor'
)
OR (TRIM(COALESCE(cargo_user, '')) = '' AND LOWER(usuario_user) LIKE 'medico%');

-- Administrativo operacional.
UPDATE tb_user
SET fk_access_profile = 4
WHERE LOWER(TRIM(cargo_user)) = 'administrativo';

-- Analistas atuais passam ao perfil gerencial.
UPDATE tb_user
SET fk_access_profile = 5
WHERE LOWER(TRIM(cargo_user)) = 'analista';

-- Diretoria e Administrador mantêm o alcance institucional atual,
-- agora de forma explícita no perfil, sem depender do texto do cargo.
UPDATE tb_user
SET fk_access_profile = 6
WHERE LOWER(TRIM(cargo_user)) IN ('diretoria', 'diretor', 'administrador');

-- Primeiro Superadministrador da base de testes.
UPDATE tb_user
SET fk_access_profile = 9
WHERE id_usuario = 1;

-- Registra a migração inicial uma única vez por usuário.
INSERT INTO tb_access_audit
    (actor_user_id, target_user_id, evento, valor_anterior, valor_novo, motivo)
SELECT
    1,
    u.id_usuario,
    'migracao_inicial_perfil',
    JSON_OBJECT('nivel_user_legado', u.nivel_user, 'cargo_user', u.cargo_user),
    JSON_OBJECT('fk_access_profile', u.fk_access_profile, 'perfil', p.nome),
    'Migração inicial para a arquitetura de cargo e nível de acesso.'
FROM tb_user u
JOIN tb_access_profile p ON p.id_access_profile = u.fk_access_profile
WHERE NOT EXISTS (
    SELECT 1
    FROM tb_access_audit a
    WHERE a.target_user_id = u.id_usuario
      AND a.evento = 'migracao_inicial_perfil'
);

