import json
import os
from typing import Any

import httpx


class LaravelApiError(Exception):
    def __init__(self, payload: dict[str, Any], status: int):
        self.payload = payload
        self.status = status
        super().__init__(payload.get("message", "API error"))


def api_client() -> httpx.Client:
    base = os.environ.get("LARAVEL_API_URL", "http://backend:8000/api/v1").rstrip("/")
    token = os.environ.get("LARAVEL_API_TOKEN", "")
    headers = {"Accept": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    return httpx.Client(base_url=base, headers=headers, timeout=30.0)


def request(
    method: str,
    path: str,
    *,
    params: dict[str, Any] | None = None,
    idempotency_key: str | None = None,
) -> Any:
    headers = {}
    if idempotency_key:
        headers["Idempotency-Key"] = idempotency_key
    with api_client() as client:
        r = client.request(method, path, params=params, headers=headers)
        if r.status_code >= 400:
            try:
                payload = r.json()
            except json.JSONDecodeError:
                payload = {"message": r.text[:500], "code": "http_error"}
            raise LaravelApiError(payload, r.status_code)
        return r.json()


def compact_ad(row: dict[str, Any]) -> dict[str, Any]:
    body = row.get("body_text") or ""
    if len(body) > 120:
        body = body[:117] + "..."
    return {
        "id": row.get("id"),
        "brand_name": row.get("brand_name"),
        "headline": row.get("headline"),
        "body_text": body,
        "niche": row.get("niche"),
        "format": row.get("format"),
        "status": row.get("status"),
        "start_date": row.get("start_date"),
        "days_active": row.get("days_active"),
        "impression_rank": row.get("impression_rank"),
    }
