# App Store Review - plano de correcao

Submissao: `2aa8de3e-4382-4744-8a47-567024893250`
Data da revisao: `2026-06-15`
Versao revisada: `1.0 (2)`

## 1. Guideline 2.3.10 - Screenshots

Problema: as screenshots enviadas para a App Store usam mockups/artes com barra de status que nao e iOS. A Apple pediu remover imagens de status bar nao-iOS.

Acao:

- Substituir as imagens em `mobile_app/app_store_screenshots/` por capturas reais do app em simulador iOS/iPadOS.
- Usar capturas do iPhone 6.9, iPhone 6.5 e iPad 13 conforme os tamanhos ja organizados nas pastas.
- Evitar moldura de aparelho desenhada, barra Android, barra de navegador, ou mockup web.
- Mostrar funcionalidades principais na maioria das imagens: login/acesso, hub, internacoes, detalhes, registros TUSS/prorrogacao/alta/evolucao.
- No App Store Connect, abrir `View All Sizes in Media Manager` e trocar todas as imagens pendentes.

## 2. Guideline 1.5 - Support URL

Problema: `https://accertconsult.com.br/suporte-fullcare.html` retorna `404`.

Acao:

- Publicar o arquivo local `suporte-fullcare.html` no dominio `accertconsult.com.br`, no caminho exato `/suporte-fullcare.html`.
- Validar antes de reenviar: `curl -I https://accertconsult.com.br/suporte-fullcare.html` precisa retornar `200`.
- Se preferir usar outro endereco ja funcional, atualizar o campo `Support URL` no App Store Connect.

## 3. Guideline 3.2 - Business

Problema: a Apple entendeu que o app e para uma empresa/grupo fechado, mas a distribuicao escolhida foi publica.

Acao se o app for para clientes especificos:

- Considerar Custom App Distribution via Apple Business Manager, ou distribuicao unlisted, conforme o caso.

Acao se o app deve continuar publico:

- Responder a Apple explicando que o app e B2B/SaaS, disponivel para qualquer organizacao elegivel que contrate a Accert Consult, nao para uma unica empresa.
- Informar que o acesso exige conta porque o app manipula dados operacionais e assistenciais sensiveis.
- Ajustar metadados para deixar claro que e um app profissional com login para clientes autorizados.

## 4. Guideline 2.1 - Demo Account

Problema: o login informado no App Store Connect nao autentica na API mobile de producao.

Acao:

- Criar ou corrigir uma conta demo ativa no mesmo ambiente usado pelo app: `https://sistema.fullcareaudit.com.br/api/mobile/index.php`.
- A conta precisa ter permissao para acessar todos os modulos submetidos.
- Desativar MFA na conta demo, se estiver ativo.
- Popular dados demonstrativos sem dados reais de pacientes.
- Atualizar `App Review Information > Sign-In Information` com usuario e senha validos.
- Testar o login em um dispositivo/simulador limpo antes de reenviar.

## Rascunho de resposta para a App Review

Hello App Review team,

Thank you for the review. We have addressed the reported items:

1. We replaced the App Store screenshots with iOS/iPadOS screenshots that accurately show the app in use and removed non-iOS status bar imagery.
2. We fixed the Support URL so it now opens a functional public support page with contact and support information.
3. FullCare Audit is a professional B2B app for authorized users of organizations that contract Accert Consult services. It is not restricted to a single company. Any eligible healthcare-related organization may become a client. Access is account-based because the app handles operational, audit, and healthcare-related administrative information.
4. We updated the demo credentials in App Store Connect with an active review account that has access to the submitted features and uses demonstration data only.

Answers to Guideline 3.2 questions:

1. The app is not restricted to users from a single company or organization. It is used by authorized users from client organizations that contract Accert Consult services.
2. The app is designed for professional use by healthcare-related organizations that become Accert Consult clients. Any eligible organization can become a client and use the app after onboarding.
3. The app does not provide consumer-facing public features. It is a professional operational tool, and account access is required to protect sensitive operational and healthcare-related information.
4. Users obtain an account through onboarding by Accert Consult or the client organization administrator. Accounts are not self-created inside the app.
5. There is no paid digital content sold in the app. Users do not pay in the app to open an account or unlock features. Access is part of the contracted B2B service.

Best regards,
Accert Consult
