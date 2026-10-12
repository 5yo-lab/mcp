# MCP server (Docker)

Runs in Docker; talks to Laravel at `http://backend:8000/api/v1` on the Compose network.

## Run with Compose (stdio for Cursor)

From repo root:

```powershell
docker compose --profile mcp run --rm -i mcp
```

## Cursor MCP config (example)

Use absolute path to your repo `docker-compose.yml`:

```json
{
  "mcpServers": {
    "stack-mcp": {
      "command": "docker",
      "args": [
        "compose",
        "-f",
        "C:\\Users\\petyo\\Projects\\mcp\\docker-compose.yml",
        "--profile",
        "mcp",
        "run",
        "--rm",
        "-i",
        "mcp"
      ]
    }
  }
}
```

Set `MCP_LARAVEL_API_TOKEN` in the root `.env` after Sanctum is configured.

## Tools

- `health_check` — probes Laravel health route (add `/api/v1/health` in Laravel when scaffolding).
