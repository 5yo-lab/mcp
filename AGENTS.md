# Agent guide

Monorepo: **Laravel JSON API** (`backend/`), **standalone React SPA** (`frontend/`), **Python MCP server** (`mcp/`).

## Architecture

- **Laravel** owns domain logic, persistence, authorization, and all mutating business rules.
- **React** is a separate SPA; it only talks to Laravel over HTTP JSON (`/api/...`).
- **MCP** exposes agent tools; handlers call Laravel with **Bearer API tokens** (service or scoped tokens)—never embed secrets in tool descriptions.

Auth model: **API tokens only** (Sanctum personal access tokens or equivalent). No cookie/CSRF SPA flow for the React app unless explicitly added later.

## Repository layout

```text
backend/     Laravel application
frontend/    Vite + React SPA
mcp/         Python MCP server (stdio for Cursor)
```

## Development (Docker only)

Host machine does not need PHP, Node, or Python. Use Compose from repo root.

```powershell
copy .env.example .env
sh scripts/bootstrap.sh   # first time: Laravel + React into backend/ & frontend/
docker compose up          # postgres + backend + frontend
```

- **Postgres**: service `postgres`; credentials in root `.env` (`POSTGRES_*`).
- **Backend**: `docker compose run --rm backend php artisan …` / `composer …`
- **Frontend**: Vite on port `5173`; `VITE_API_URL` in root `.env` (browser → `localhost:8000`).
- **MCP**: `docker compose --profile mcp run --rm -i mcp` — see `mcp/README.md` for Cursor config.

`backend/.env` must use `DB_HOST=postgres` for in-container runs. Document new vars in `.env.example` files. Never commit secrets.

## Workflows (skills)

| Task | Skill |
|------|--------|
| Plan or implement a feature across layers | `plan-full-stack-feature` |
| New Laravel API endpoint | `scaffold-laravel-endpoint` |
| New React feature / page | `scaffold-react-feature` |
| New agent tool | `add-mcp-tool` |
| After API shape changes | `sync-api-types` |

Skills live in `.cursor/skills/<name>/SKILL.md`.

## API contract

- Base path: `/api/v1` (version when breaking changes are expected).
- JSON from Laravel uses **snake_case** in API Resources; frontend may map to camelCase at the client boundary.
- Errors: consistent envelope (e.g. `message`, optional `errors` object, HTTP status).
- Pagination: document standard query params and response shape in `backend` once implemented.

## MCP ↔ Laravel

- MCP uses `LARAVEL_API_URL` + `LARAVEL_API_TOKEN` (or per-tool scoped tokens if added).
- Tools should map to safe, idempotent-or-documented API operations; validate inputs in MCP and again in Laravel.
- Timeouts and clear error messages for the model; no PII in logs.

## Rules

Cursor rules in `.cursor/rules/*.mdc` apply by file glob or always (`project-architecture.mdc`).

## Before merging

- Migrations and `.env.example` updated if needed
- API change → update frontend client/types when applicable
- New MCP tool → test + document in `mcp/README.md`
