import json
from typing import Any

from mcp.server.fastmcp import FastMCP

from app.client import LaravelApiError, compact_ad, request

mcp = FastMCP("gethookd-ads")

# ~400–600 chars budget per ad in list tools; full detail via get_ad.


@mcp.tool()
def list_niches() -> str:
    """List valid niche slugs and display names. Call this before search_ads when filtering by niche."""
    try:
        payload = request("GET", "/niches")
    except LaravelApiError as e:
        return _format_error(e)
    return json.dumps(payload.get("data", []), separators=(",", ":"))


@mcp.tool()
def search_ads(
    q: str | None = None,
    niche: str | None = None,
    format: str | None = None,
    status: str | None = None,
    started_after: str | None = None,
    sort: str = "newest",
    cursor: str | None = None,
    per_page: int = 10,
) -> str:
    """Search ads via v1 API. Use list_niches for slug values. sort: newest|impressions. Use cursor from meta.next_cursor for more."""
    params: dict[str, Any] = {"sort": sort, "per_page": min(max(per_page, 1), 25)}
    if q:
        params["q"] = q
    if niche:
        params["niche"] = niche
    if format:
        params["format"] = format
    if status:
        params["status"] = status
    if started_after:
        params["started_after"] = started_after
    if cursor:
        params["cursor"] = cursor
    try:
        payload = request("GET", "/ads", params=params)
    except LaravelApiError as e:
        return _format_error(e)

    rows = [compact_ad(item) for item in payload.get("data", [])]
    out = {
        "data": rows,
        "meta": payload.get("meta", {}),
        "note": "Use get_ad(id) for full body_text. impression_rank is within-brand only.",
    }
    return json.dumps(out, separators=(",", ":"))


@mcp.tool()
def get_ad(ad_id: int) -> str:
    """Fetch one ad by id from search_ads results. Use for full copy and metadata."""
    try:
        payload = request("GET", f"/ads/{ad_id}")
    except LaravelApiError as e:
        return _format_error(e)
    return json.dumps(payload.get("data", {}), separators=(",", ":"))


def _format_error(err: LaravelApiError) -> str:
    p = err.payload
    return json.dumps(
        {
            "error": True,
            "code": p.get("code"),
            "message": p.get("message"),
            "next_step": p.get("next_step"),
            "retryable": p.get("retryable"),
            "details": p.get("details"),
            "http_status": err.status,
        },
        separators=(",", ":"),
    )


def main() -> None:
    mcp.run()


if __name__ == "__main__":
    main()
