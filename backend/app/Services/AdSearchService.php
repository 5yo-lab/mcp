<?php

namespace App\Services;

use App\Enums\AdFormat;
use App\Enums\AdStatus;
use App\Models\Ad;
use App\Support\NicheCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AdSearchService
{
    public const SORT_NEWEST = 'newest';

    public const SORT_IMPRESSIONS = 'impressions';

    /**
     * @param  array{
     *   q?: string|null,
     *   niche?: list<string>,
     *   format?: string|null,
     *   status?: string|null,
     *   started_after?: string|null,
     *   sort?: string,
     *   cursor?: string|null,
     *   per_page?: int,
     * }  $filters
     * @return array{data: \Illuminate\Support\Collection, next_cursor: ?string, applied: array<string, mixed>}
     */
    public function search(array $filters): array
    {
        $perPage = min(max($filters['per_page'] ?? 20, 1), 100);
        $sort = $filters['sort'] ?? self::SORT_NEWEST;

        $query = Ad::query()->with('brand');

        if (! empty($filters['q'])) {
            $like = '%'.strtolower($filters['q']).'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->whereRaw('LOWER(headline) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(body_text) LIKE ?', [$like]);
            });
        }

        if (! empty($filters['niche'])) {
            $query->whereIn('niche', $filters['niche']);
        }

        if (! empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['started_after'])) {
            $query->whereDate('start_date', '>=', $filters['started_after']);
        }

        $this->applySort($query, $sort);
        $this->applyCursor($query, $sort, $filters['cursor'] ?? null);

        $rows = $query->limit($perPage + 1)->get();
        $hasMore = $rows->count() > $perPage;
        if ($hasMore) {
            $rows = $rows->take($perPage);
        }

        $nextCursor = $hasMore && $rows->isNotEmpty()
            ? $this->encodeCursor($rows->last(), $sort)
            : null;

        return [
            'data' => $rows,
            'next_cursor' => $nextCursor,
            'applied' => $this->appliedFilters($filters),
        ];
    }

    private function applySort(Builder $query, string $sort): void
    {
        if ($sort === self::SORT_IMPRESSIONS) {
            $query->orderByRaw('CASE WHEN impression_rank IS NULL THEN 1 ELSE 0 END')
                ->orderBy('impression_rank')
                ->orderBy('id');

            return;
        }

        $query->orderByDesc('start_date')->orderByDesc('id');
    }

    private function applyCursor(Builder $query, string $sort, ?string $cursor): void
    {
        if ($cursor === null || $cursor === '') {
            return;
        }

        $payload = json_decode(base64_decode($cursor, true) ?: '', true);
        if (! is_array($payload) || ($payload['sort'] ?? null) !== $sort) {
            return;
        }

        if ($sort === self::SORT_IMPRESSIONS) {
            $rank = array_key_exists('impression_rank', $payload) ? $payload['impression_rank'] : null;
            $id = (int) $payload['id'];
            if ($rank === null) {
                $query->whereNull('impression_rank')->where('id', '>', $id);
            } else {
                $query->where(function (Builder $outer) use ($rank, $id): void {
                    $outer->where(function (Builder $ranked) use ($rank, $id): void {
                        $ranked->whereNotNull('impression_rank')
                            ->where(function (Builder $inner) use ($rank, $id): void {
                                $inner->where('impression_rank', '>', $rank)
                                    ->orWhere(function (Builder $tie) use ($rank, $id): void {
                                        $tie->where('impression_rank', $rank)->where('id', '>', $id);
                                    });
                            });
                    })->orWhereNull('impression_rank');
                });
            }

            return;
        }

        $startDate = Carbon::parse($payload['start_date'])->toDateString();
        $id = (int) $payload['id'];
        $query->where(function (Builder $q) use ($startDate, $id): void {
            $q->whereDate('start_date', '<', $startDate)
                ->orWhere(function (Builder $tie) use ($startDate, $id): void {
                    $tie->whereDate('start_date', $startDate)->where('id', '<', $id);
                });
        });
    }

    private function encodeCursor(Ad $ad, string $sort): string
    {
        if ($sort === self::SORT_IMPRESSIONS) {
            $payload = [
                'sort' => $sort,
                'impression_rank' => $ad->impression_rank,
                'id' => $ad->id,
            ];
        } else {
            $payload = [
                'sort' => $sort,
                'start_date' => $ad->start_date->toDateString(),
                'id' => $ad->id,
            ];
        }

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function appliedFilters(array $filters): array
    {
        $applied = [
            'sort' => $filters['sort'] ?? self::SORT_NEWEST,
            'per_page' => min(max($filters['per_page'] ?? 20, 1), 100),
        ];

        if (! empty($filters['q'])) {
            $applied['q'] = $filters['q'];
        }
        if (! empty($filters['niche'])) {
            $applied['niche'] = array_values($filters['niche']);
        }
        if (! empty($filters['format'])) {
            $applied['format'] = $filters['format'];
        }
        if (! empty($filters['status'])) {
            $applied['status'] = $filters['status'];
        }
        if (! empty($filters['started_after'])) {
            $applied['started_after'] = $filters['started_after'];
        }

        return $applied;
    }

    /** @return list<string> */
    public static function normalizeNicheInputs(array|string|null $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $items = is_array($raw) ? $raw : explode(',', (string) $raw);
        $slugs = [];
        foreach ($items as $item) {
            $slug = NicheCatalog::resolveSlug(trim($item));
            if ($slug !== null) {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    public static function suggestParameter(string $unknown): ?string
    {
        $allowed = ['q', 'niche', 'format', 'status', 'started_after', 'sort', 'cursor', 'per_page'];
        $best = null;
        $bestDist = PHP_INT_MAX;
        foreach ($allowed as $candidate) {
            $dist = levenshtein(strtolower($unknown), $candidate);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $candidate;
            }
        }

        return $bestDist <= 3 ? $best : null;
    }
}
