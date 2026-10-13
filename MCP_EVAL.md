# MCP evaluation

Client: Cursor Agent · MCP server: `gethookd-ads` (Docker stdio)

## Fixed tasks

1. “Find active video ads in skincare that started in the last 30 days.”
2. “Which pet supplies brand gets the most impressions?”

## Custom tasks

3. “Show me five active carousel ads in kitchen — headlines only.”
4. “Any supplements ads mentioning protein? Give ids and start dates.”
5. “What’s working in beauty right now?” (ambiguous — no `beauty` slug)

## Results (first try: correct tool + args?)

| Task | Pass first try? | Notes                                                                                                                                                                            |
| ---- | --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1    | Yes             | `list_niches` → `search_ads(niche=skincare, format=video, status=active, started_after=2026-09-13, sort=newest)` → 14 hits; then many `get_ad` + canvas/charts (beyond the ask). |
| 2    | Yes             | `list_niches` → `search_ads(niche=pet-supplies, sort=impressions)` → explained within-brand `impression_rank` (no global “top brand”).                                           |
| 3    | No              | First MCP call only `search_ads(niche=kitchen, format=carousel, status=active, per_page=5)` — skipped `list_niches` (slug was correct). Headlines returned as requested.         |
| 4    | Yes             | First turn: `list_niches` + `search_ads(q=protein, niche=supplements, sort=newest)`; then multiple cursor pages to list ids/start dates.                                         |
| 5    | No              | First turn: `list_niches` then `search_ads(niche=beauty, …)` (invalid slug); recovered with `skincare` / `haircare` + `get_ad`.                                                  |

## Misuse → fix → retry

**Before (task 5):** After `list_niches`, model called `search_ads` with `niche=beauty` even though slugs are `skincare`, `haircare`, etc.

**Change made:** Tightened `list_niches` / `search_ads` docstrings in `mcp/app/server.py` (no `beauty` slug; map colloquial terms; call `list_niches` when slug uncertain).

**After:** Same prompt → `list_niches` → `search_ads(niche=skincare|haircare, …)` without `beauty`; brief note that “beauty” is not a slug.

**Also applied (task 3):** `search_ads` docstring requires `list_niches` first when slug is unfamiliar or colloquial.
