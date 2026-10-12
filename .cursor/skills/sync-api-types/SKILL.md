---
name: sync-api-types
description: Updates frontend TypeScript types and API client after Laravel API Resource or route changes. Use when API shape changes, after scaffolding endpoints, or when user mentions OpenAPI, types, or contract sync.
---

# Sync API types

## When to run

- New or changed API Resources, query params, or error shapes in `backend/`.
- Before merging frontend that consumes those endpoints.

## Steps

1. List changed endpoints and example JSON responses (from tests or Scramble/OpenAPI if present).
2. Update `frontend/src/api/types` (or generated output) to match **snake_case** from Laravel unless project uses a camelCase mapper—stay consistent.
3. Update client functions (paths, methods, body types).
4. Fix TypeScript errors in features that import those types.
5. If MCP tools use the same payloads, align Pydantic models in `mcp/`.

## Validation

- Prefer deriving types from OpenAPI when the project adds generation; until then, manual types must match feature tests' `assertJsonStructure`.

## Do not

- Change API field names only in the frontend without a Laravel Resource change (unless explicit mapping layer exists).
