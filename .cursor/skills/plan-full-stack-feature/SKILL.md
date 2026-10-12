---
name: plan-full-stack-feature
description: Plans and implements features across Laravel API, standalone React SPA, and Python MCP. Use when adding product behavior, designing endpoints, planning PRs, or when the user mentions full-stack, API + UI, or agent tools for this repo.
---

# Plan full-stack feature

## Output template

Produce this plan before large implementations (Plan mode or first reply):

```markdown
## Feature: [name]

### Scope
- In scope:
- Out of scope:

### Data model
- Entities, relations, migrations

### API (Laravel `/api/v1`)
- Routes, methods, auth (token abilities)
- Request/response examples
- Policies / validation

### Frontend (React)
- Routes/screens, components, hooks
- API client methods, error/loading states

### MCP (if needed)
- Tool names, inputs, which API endpoints they call
- Idempotency / side effects

### Security
- Who can call what; token scopes

### Test plan
- Backend feature tests
- Frontend (unit/integration)
- MCP handler tests

### Env / docs
- `.env.example` keys touched
```

## Implementation order

1. Migration + model + policy (backend)
2. Form Request + Action + Resource + route + tests
3. Frontend types/client + UI
4. MCP tool(s) only if agents need access
5. Update `AGENTS.md` or app README if runbooks change

## Constraints

- API tokens only; standalone React—no Inertia, no Sanctum cookie SPA flow unless user overrides.
- Laravel is authoritative for business rules.
