---
name: add-mcp-tool
description: Adds a Python MCP tool that calls the Laravel API with Bearer token env vars. Use when creating MCP tools, registering handlers, or wiring agents to backend endpoints in mcp/.
---

# Add MCP tool

## Checklist

```
- [ ] Tool name: verb_noun, clear description (no secrets)
- [ ] Input schema (Pydantic) with descriptions for the model
- [ ] Handler calls Laravel REST (same as SPA), not duplicate logic
- [ ] Env: LARAVEL_API_URL, LARAVEL_API_TOKEN documented in mcp/.env.example
- [ ] Timeout + handle 4xx/5xx with concise tool error text
- [ ] Unit test with mocked HTTP
- [ ] Register tool in server entrypoint
- [ ] Note in mcp/README.md (how to run, Cursor config snippet)
```

## HTTP client

```python
headers = {"Authorization": f"Bearer {token}", "Accept": "application/json"}
# GET/POST/PATCH to f"{base_url}/api/v1/..."
```

## Safety

- One tool ≈ one safe API operation; narrow scopes on the service token in Laravel.
- Writes: document side effects; use idempotency if agents may retry.

## Cursor MCP config

Document the command to start stdio server (e.g. `uv run python -m mcp_server`) in mcp/README when entrypoint exists.
