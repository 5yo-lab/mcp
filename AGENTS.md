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

## Local development (target)

```bash
# Backend
cd backend && cp .env.example .env && composer install && php artisan migrate

# Frontend
cd frontend && cp .env.example .env && npm install && npm run dev

# MCP (from repo root or mcp/)
cd mcp && uv sync  # or pip install -e .
# Configure CURSOR MCP to run the server entrypoint (see mcp/README.md when present)
```

Environment variables: each app has its own `.env`. Document new vars in that app’s `.env.example`. Never commit secrets.

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
