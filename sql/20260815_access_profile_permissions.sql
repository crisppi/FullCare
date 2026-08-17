-- Matriz inicial de acesso por perfil, módulo e ação.
-- Seguro para reaplicação: recompõe apenas os perfis protegidos do FullCare.

DELETE FROM tb_access_profile_permission
WHERE profile_id IN (1, 2, 3, 4, 5, 6, 9);

INSERT INTO tb_access_profile_permission (profile_id, module, action, allowed)
SELECT p.id_access_profile, m.module, a.action, 0
FROM tb_access_profile p
CROSS JOIN (
    SELECT 'dashboard' module UNION ALL SELECT 'manual' UNION ALL
    SELECT 'pacientes' UNION ALL SELECT 'hospitais' UNION ALL
    SELECT 'seguradoras' UNION ALL SELECT 'estipulantes' UNION ALL
    SELECT 'censo' UNION ALL SELECT 'internacoes' UNION ALL
    SELECT 'visitas' UNION ALL SELECT 'gestao' UNION ALL
    SELECT 'contas' UNION ALL SELECT 'altas' UNION ALL
    SELECT 'usuarios' UNION ALL SELECT 'permissoes' UNION ALL
    SELECT 'cadastros_clinicos' UNION ALL SELECT 'cuidado_continuado' UNION ALL
    SELECT 'bi_operacional' UNION ALL SELECT 'bi_estrategico' UNION ALL
    SELECT 'solicitacoes'
) m
CROSS JOIN (
    SELECT 'view' action UNION ALL SELECT 'create' UNION ALL SELECT 'edit' UNION ALL
    SELECT 'delete' UNION ALL SELECT 'discharge' UNION ALL SELECT 'revert_discharge' UNION ALL
    SELECT 'close_management' UNION ALL SELECT 'reopen_management' UNION ALL
    SELECT 'finalize_account' UNION ALL SELECT 'reopen_account' UNION ALL
    SELECT 'generate_pdf' UNION ALL SELECT 'export' UNION ALL SELECT 'manage'
) a
WHERE p.id_access_profile IN (1, 2, 3, 4, 5, 6, 9);

-- Todos os perfis autenticados podem acessar início, dashboard básico, manual e solicitações.
UPDATE tb_access_profile_permission
SET allowed = 1
WHERE module IN ('dashboard', 'manual', 'solicitacoes')
  AND action IN ('view', 'create');

-- CONSULTA: leitura operacional e BI operacional limitado.
UPDATE tb_access_profile_permission
SET allowed = 1
WHERE profile_id = 1
  AND action = 'view'
  AND module IN (
      'pacientes', 'hospitais', 'seguradoras', 'estipulantes', 'censo',
      'internacoes', 'visitas', 'gestao', 'contas', 'altas',
      'cadastros_clinicos', 'cuidado_continuado', 'bi_operacional'
  );

-- SECRETARIA: cadastro e movimentação operacional, sem exclusões e sem decisões sensíveis.
UPDATE tb_access_profile_permission
SET allowed = 1
WHERE profile_id = 2
  AND (
      (module IN ('pacientes', 'hospitais', 'seguradoras', 'estipulantes', 'censo', 'internacoes', 'visitas')
       AND action IN ('view', 'create', 'edit'))
      OR (module IN ('gestao', 'contas', 'altas', 'cadastros_clinicos', 'cuidado_continuado') AND action = 'view')
      OR (module IN ('visitas', 'contas') AND action = 'generate_pdf')
  );

-- ASSISTENCIAL: médicos e enfermeiros com o mesmo acesso sistêmico.
UPDATE tb_access_profile_permission
SET allowed = 1
WHERE profile_id = 3
  AND (
      (module IN ('censo', 'internacoes', 'visitas', 'gestao', 'contas', 'cuidado_continuado')
       AND action IN ('view', 'create', 'edit'))
      OR (module IN ('pacientes', 'hospitais', 'seguradoras', 'estipulantes', 'altas', 'cadastros_clinicos') AND action = 'view')
      OR (module = 'altas' AND action = 'discharge')
      OR (module IN ('visitas', 'contas', 'internacoes') AND action = 'generate_pdf')
  );

-- ADMINISTRATIVO: cadastros, internações e contas, sem exclusões ou administração de usuários.
UPDATE tb_access_profile_permission
SET allowed = 1
WHERE profile_id = 4
  AND (
      (module IN ('pacientes', 'hospitais', 'seguradoras', 'estipulantes', 'censo', 'internacoes', 'contas', 'cadastros_clinicos')
       AND action IN ('view', 'create', 'edit'))
      OR (module IN ('visitas', 'gestao', 'altas', 'cuidado_continuado') AND action = 'view')
      OR (module = 'contas' AND action IN ('finalize_account', 'reopen_account', 'generate_pdf', 'export'))
      OR (module IN ('visitas', 'internacoes') AND action = 'generate_pdf')
      OR (module = 'bi_operacional' AND action = 'view')
  );

-- GERENCIAL: operação completa sem exclusão definitiva nem administração de usuários/perfis.
UPDATE tb_access_profile_permission
SET allowed = 1
WHERE profile_id = 5
  AND (
      (module IN ('pacientes', 'hospitais', 'seguradoras', 'estipulantes', 'censo', 'internacoes',
                  'visitas', 'gestao', 'contas', 'cadastros_clinicos', 'cuidado_continuado')
       AND action IN ('view', 'create', 'edit', 'generate_pdf', 'export'))
      OR (module = 'altas' AND action IN ('view', 'discharge', 'revert_discharge'))
      OR (module = 'gestao' AND action IN ('close_management', 'reopen_management'))
      OR (module = 'contas' AND action IN ('finalize_account', 'reopen_account'))
      OR (module IN ('bi_operacional', 'bi_estrategico') AND action = 'view')
  );

-- DIRETORIA: visão e operação amplas; gestão de perfis continua exclusiva do Superadministrador.
UPDATE tb_access_profile_permission
SET allowed = 1
WHERE profile_id = 6
  AND (
      (module IN ('pacientes', 'hospitais', 'seguradoras', 'estipulantes', 'censo', 'internacoes',
                  'visitas', 'gestao', 'contas', 'cadastros_clinicos', 'cuidado_continuado')
       AND action IN ('view', 'create', 'edit', 'generate_pdf', 'export'))
      OR (module = 'altas' AND action IN ('view', 'discharge', 'revert_discharge'))
      OR (module = 'gestao' AND action IN ('close_management', 'reopen_management'))
      OR (module = 'contas' AND action IN ('finalize_account', 'reopen_account'))
      OR (module IN ('bi_operacional', 'bi_estrategico') AND action = 'view')
      OR (module = 'usuarios' AND action = 'view')
  );

-- SUPERADMINISTRADOR: acesso integral.
UPDATE tb_access_profile_permission
SET allowed = 1
WHERE profile_id = 9;
