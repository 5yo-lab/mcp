# Cursor pagination (v1)

## Ordering

- **newest:** `start_date DESC`, `id DESC`
- **impressions:** ads with `impression_rank` first (ascending rank), then ads with null rank, tie-break `id ASC`

## Cursor promise (snapshot-ish)

The cursor encodes the last row seen for the chosen sort. Pages are stable for ads that **match the query for the whole walk** and whose sort keys do not change mid-walk.

**While live ingest runs:**

- **Inserted** ads that sort *before* the cursor may appear on a later page (they were not in the snapshot).
- **Inserted** ads that sort *after* the cursor will not appear until a new search.
- **Deactivated** ads remain visible if they still match filters; if status filter is `active`, an ad deactivated mid-walk may disappear on later pages (it no longer matches).

We do **not** guarantee a strict point-in-time snapshot; we guarantee **no duplicate ids** across pages for a single cursor walk and **no skipped ids** among ads that continuously matched the query and kept stable sort keys from first page fetch through the walk.
