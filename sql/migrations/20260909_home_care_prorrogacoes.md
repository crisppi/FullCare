# Prorrogações de home care

Instalação (raiz do projeto):

```sh
php scripts/migrate_home_care_prorrogacoes.php
php scripts/validate_home_care_prorrogacoes.php --database
```

A migração idempotente cria `tb_hc_plano` e `tb_hc_prorrogacao`, preservando as avaliações existentes. Também está integrada a `scripts/run_schema_maintenance.php`. A nova página não executa DDL.

Acesso: Cuidado Continuado → Prorrogações de Home Care, também pela gestão e pela avaliação de um caso. Rota `/cuidado-continuado/home-care/prorrogacoes`. Os casos são as internações com avaliação de home care existente; não são criados pacientes duplicados. Apenas casos implantados ou em implantação aceitam novos planos e pedidos.

Fluxo:

1. Registrar o plano inicial já autorizado, com protocolo, início/fim, fornecedor, modalidade, serviços, equipe/frequência, materiais/medicamentos e equipamentos.
2. Solicitar prorrogação a partir do último plano autorizado, com novo período e justificativa. O conteúdo do plano é pré-preenchido, mas a data final solicitada deve ser informada. Só é permitido um pedido pendente por plano.
3. Aprovar integralmente, parcialmente ou negar. Parecer é obrigatório. Aprovação exige protocolo. Aprovação parcial permite modificar período e recursos; o período autorizado deve estar contido no pedido.
4. Cada aprovação gera um novo plano imutável. A solicitação original e as decisões anteriores continuam no histórico. A negativa não altera as autorizações. Nova solicitação após negativa é permitida.

Os períodos são inclusivos: a nova solicitação começa após o fim do plano de referência. O sistema não infere autorização de equipamentos, atendimento ou prazo. Registros com períodos vencidos continuam visíveis.

Permissões: `cuidado_continuado.view` para consultar; `create` para registrar plano inicial e solicitar; `edit` para decidir. O plano inicial registra uma autorização já existente; essa operação não substitui a autorização da operadora. Escopo por hospital e seguradora usa o mesmo helper assistencial. CSRF, transações, bloqueio por caso e controle de versão impedem gravações concorrentes e decisões duplicadas.

Os testes de banco usam um caso existente apenas como referência dentro de uma transação, com avaliação e planos sintéticos; todas as alterações são revertidas no final. A versão inicial não permite editar ou excluir decisões finalizadas, nem recebe solicitações por portal externo de prestadores.
