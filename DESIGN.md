# Design notes (GetHookd assessment)

## Trade-offs

- **Cursor vs offset (v1):** Keyset cursors keep pages stable under ingest; v0 keeps offset for backward compatibility.
- **Billing row per billable call:** Simple exact counting; idempotency dedupes MCP/HTTP retries within 24h.
- **Single manage key for dev UI:** No login; local demo only.

## v0 and v1 coexist

- v0 `/api/v0/ads` unchanged behavior (query `api_key`, silent filter drops).
- v1 `/api/v1/ads` strict validation, Bearer auth, cursors, shared rate limit and meter.

## Breaking change to v1

Ship `/api/v2` with migration guide; keep v1 until clients move. OpenAPI + contract tests gate changes.

## Scale (110M ads, ~5–6M active)

1. **Offset-free search index:** Composite indexes on `(niche, status, start_date, id)` and impression sort keys; consider Postgres partitioning by niche or date.
2. **Ingest write path:** Move live ingest to queue/worker; avoid synchronous rank updates per brand at 110M scale.

Next steps: read replicas for search, precomputed brand impression leaders for analytics queries.
