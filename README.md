# GetHookd assessment — API + React + MCP

## One command (stack)

```powershell
copy .env.example .env
copy frontend\.env.example frontend\.env
docker compose up -d --build
docker compose exec backend php artisan migrate --force
docker compose exec backend php artisan ads:seed
docker compose restart frontend
```

Open **http://localhost:5173** (developer page) and **http://localhost:8000/api/v1/health**.

`frontend/.env.example` contains the same seeded keys as `ads:seed` (safe for local review). Copy to `frontend/.env` as above so the dev UI and MCP panel work without creating keys manually.

## Live ingest

```powershell
docker compose exec backend php artisan ads:ingest
```

## Tests

```powershell
docker compose exec backend php artisan test
```

## MCP (Cursor)

Set `MCP_LARAVEL_API_TOKEN` in root `.env` to a `search:read` key, then:

```powershell
docker compose --profile mcp run --rm -i mcp
```

See `mcp/README.md` and the developer page “Connect your AI tool” panel.

## Seeded keys

| Key                                 | Scopes                   |
| ----------------------------------- | ------------------------ |
| `ghk_v0_contract_test_key_00000001` | search:read              |
| `ghk_dev_manage_key_000000001`      | search:read, keys:manage |

## Docs

- `DESIGN.md` — architecture trade-offs
- `MCP_EVAL.md` — model evaluation
- `AI_NOTES.md` — how you used AI
- `backend/docs/CURSOR_PAGINATION.md` — cursor semantics under ingest
