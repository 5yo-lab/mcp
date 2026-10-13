# AI notes

## Time per part

| Part      | Focus (assessment)                                     | Hours | Tools                           |
| --------- | ------------------------------------------------------ | ----- | ------------------------------- |
| 0         | Data model, seed, ingest, v0 + contract tests          | 1     | Cursor Agent (Composer), Docker |
| 1         | v1 search API, cursors, errors, OpenAPI, filter tests  | 1     | Cursor Agent                    |
| 2         | MCP tools, Docker MCP, eval chats, `MCP_EVAL.md`       | 3     | Cursor Agent, MCP eval chats    |
| 3         | API keys, rate limits, billing, idempotency tests      | 2     | Cursor Agent                    |
| 4         | React developer page, README, `DESIGN.md`, agent rules | 1     | Cursor Agent                    |
| **Total** |                                                        | **8** |                                 |

## When the AI was wrong

1. **What it produced:** Initial MCP eval used `search_ads(niche=beauty)` after `list_niches`, even though no `beauty` slug exists.
   **How you caught it:** v1 API validation / empty or error response; cross-check with niche list (skincare, haircare, …).
   **What you did instead:** Documented in `MCP_EVAL.md` and tightened tool docstrings in `mcp/app/server.py` so models map colloquial terms to slugs.

2. **What it produced:** v1 filter tests compared raw page counts at `per_page=20`, so format/status tests falsely failed when both pages returned 20 rows.
   **How you caught it:** Full `php artisan test` run showed “20 is less than 20”.
   **What you did instead:** Asserted mixed formats/statuses in unfiltered results and all matching values in filtered results.

3. **What it produced:** Docker entrypoint “no such file or directory” on Windows.
   **How you caught it:** `docker compose logs` on backend/frontend.
   **What you did instead:** CRLF strip in Dockerfiles, invoke entrypoint via `/bin/sh`, `git add --renormalize .`.

## Not delegated to AI

- **Initial project structure** — service folders, `.gitignore`, etc. created manually before using AI.
- **MCP eval execution** — five prompts run manually in separate Cursor chats; results recorded in `MCP_EVAL.md`.
- **Final submission hygiene** — private repo access, commit history, filling hours in this file.

## How you verified tests catch regressions

- **v0 contract tests** pin silent-ignore filter quirks; must stay green when v1 changes.
- **One test per v1 filter** in `SearchAdsTest` (fails if a filter is silently ignored).
- **Billing tests** for idempotency and revoked keys.
- Manually broke a filter in a branch once and confirmed the matching v1 filter test failed before fixing.
