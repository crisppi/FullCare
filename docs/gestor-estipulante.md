# Gestão de casos dos estipulantes

Entrada: `/gestor_estipulante.php`. O login dos novos perfis abre essa tela automaticamente.

Perfis cadastrados na matriz existente:
- `gestor_estipulante_med`: médico, pode registrar.
- `gestor_estipulante_enf`: enfermeiro, pode registrar.
- `gerente_estipulante`: somente consulta; não visualiza rascunhos de terceiros.

A conta solicitada `gestor@fullcare.com.br` foi criada no perfil médico. A profissão pode ser ajustada para o perfil enfermeiro pelo administrador. Não foi inventado registro profissional. Sem vínculo explícito, a conta não vê pacientes.

## Escopo

As permissões de escrita são verificadas pelo perfil ativo, e a consulta e cada operação verificam o vínculo usuário/hospital/estipulante. Acesso por ID de outro hospital ou estipulante é bloqueado. O perfil usa os cadastros nativos de pacientes e hospitais, internações, censo, altas e BI operacional (tipo de internação e longa permanência), com o mesmo escopo aplicado no servidor. Gerentes consultam; médicos e enfermeiros registram. Os demais módulos e a API móvel de auditoria permanecem bloqueados.

Configure um vínculo após identificar os IDs corretos:

```sh
php scripts/configure_gestor_estipulante.php gestor@fullcare.com.br HOSPITAL_ID ESTIPULANTE_ID
```

O argumento opcional `--todos-pacientes` autoriza todos os pacientes daquele hospital, inclusive outros estipulantes. Só usar quando esse alcance for explicitamente definido. Repetir para cada hospital autorizado. O script registra a concessão em `tb_access_audit`.

A pedido do usuário, `gestor@fullcare.com.br` usa a base compartilhada completa do FullCare, sem duplicar pacientes ou hospitais. Concessão explícita por conta:

```sh
php scripts/configure_gestor_estipulante.php gestor@fullcare.com.br --base-fullcare
```

O vínculo especial `hospital_id=0, estipulante_id=0, todos_pacientes=1` em `ge_escopo` representa essa concessão e abrange cadastros atuais e futuros, internações e BI operacional. Outros usuários continuam restritos aos seus vínculos. O script salva o escopo anterior em `checkpoints/gestor-base-fullcare` e registra a concessão em auditoria. Para revogar apenas o acesso completo, remover esse vínculo especial da conta; preservar os vínculos específicos. A concessão não altera permissões de TUSS, prorrogação ou o papel de consulta do gerente.

## Operação

O censo lê as internações existentes no FullCare, não importa diretamente do hospital. O horário exibido é a última alteração das internações na base por hospital, não uma garantia de sincronização externa. A cobertura total depende da atualização dessa base. Alertas são calculados na abertura da página: evolução ausente ou finalizada há mais de 48 horas, tarefa aberta vencida e ausência de previsão de alta. Permanência média considera casos em acompanhamento.

A evolução tem 12 áreas clínicas. Origem, situação e plano assistencial são obrigatórios na finalização. Rascunhos só podem ser continuados pelo autor. Evoluções finalizadas são imutáveis; complementos mantêm o vínculo com a original. Plano do caso usa controle de versão para evitar sobrescrita concorrente. Pendências exigem responsável assistencial com escopo compatível.

A tela do caso encaminha para a alta nativa do FullCare, que atualiza a internação e o registro de alta na base original. O encerramento apenas do acompanhamento, mantido no serviço do módulo, exige resolução das pendências abertas. Casos encerrados na origem também ficam somente para consulta. Não há emissão de prorrogações ou liberação de TUSS. A admissão pelo perfil estipulante também não gera capeante nem prorrogação inicial automática. O cabeçalho, menus, cores e cadastros reutilizam o padrão visual FullCare.

Snapshot adicional antes da integração visual e dos módulos nativos: `checkpoints/gestor-integracao-fullcare-20260917/antes-integracao.tar.gz`.

## Checkpoint anterior

Criado antes da alteração, a partir do commit registrado em `checkpoints/gestor-estipulante-20260917/commit.txt`:
- `codigo-antes.tar.gz`: todos os arquivos versionados anteriores.
- `banco-antes.json`: DDL e dados de `tb_user`, `tb_access_profile`, `tb_access_profile_permission` e `schema_version`, tabelas compartilhadas tocadas pela instalação.

O diretório está ignorado pelo Git e bloqueado no Apache por `checkpoints/.htaccess`. O JSON tem permissão local 0600. Não publicar esses arquivos nem servi-los em servidores que não respeitam `.htaccess`.

Para voltar: guardar uma cópia do estado atual; restaurar os arquivos modificados a partir do arquivo de código; remover os arquivos novos deste módulo. No banco, desativar a conta criada e retirar os perfis/permissões e a versão `20260917_gestor_estipulante`, apenas após confirmar que não há novos usuários vinculados. Se já houver acompanhamento, exportar/preservar as tabelas `ge_*` antes de qualquer remoção. Não restaurar tabelas inteiras sobre mudanças de outros usuários posteriores ao checkpoint. O snapshot permite restauração seletiva dos registros anteriores; nenhuma tabela clínica legada foi modificada pela instalação.

## Validação

```sh
php scripts/validate_gestor_estipulante.php
php scripts/validate_gestor_native.php
php scripts/validate_session_guard.php
```

O teste do módulo cria tabelas TEMPORARY na conexão, sem copiar pacientes reais, cobre escopo, gerente, autoria, complementos, concorrência, pendências, alta e redirecionamento. Nenhuma fixture permanece no banco. Validar a operação real com a equipe após configurar o hospital e o estipulante.
