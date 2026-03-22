# FullCare Mobile API

API para o app mobile nativo, usando o mesmo banco de dados do FullCare web.

## Endpoints iniciais

- `POST index.php?action=login`
- `GET index.php?action=me`
- `GET index.php?action=patients&query=...`
- `GET index.php?action=admissions&query=...`
- `GET index.php?action=admission&id=...`
- `GET index.php?action=tuss-catalog&query=...`
- `POST index.php?action=admission-tuss`
- `POST index.php?action=admission-extension`

## Autenticacao

Use `Authorization: Bearer <token>`.

## Observacao

Para producao, defina `MOBILE_API_SECRET` no ambiente para substituir o segredo padrao de desenvolvimento.
