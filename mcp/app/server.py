import os

import httpx
from mcp.server.fastmcp import FastMCP

mcp = FastMCP("stack-mcp")


def _api_client() -> httpx.Client:
    base = os.environ.get("LARAVEL_API_URL", "http://backend:8000/api/v1").rstrip("/")
    token = os.environ.get("LARAVEL_API_TOKEN", "")
    headers = {"Accept": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    return httpx.Client(base_url=base, headers=headers, timeout=30.0)


@mcp.tool()
def health_check() -> str:
    """Call Laravel GET /api/v1/health once that route exists."""
    with _api_client() as client:
        try:
            r = client.get("/health")
            return f"{r.status_code}: {r.text[:500]}"
        except httpx.HTTPError as e:
            return f"HTTP error: {e}"


def main() -> None:
    mcp.run()


if __name__ == "__main__":
    main()
