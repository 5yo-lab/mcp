<?php

namespace App\Http\Controllers\Api\V0;

use App\Enums\AdFormat;
use App\Enums\AdStatus;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Support\NicheCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $apiKey = $request->query('api_key');
        if (! is_string($apiKey) || ApiKey::findValidByPlainKey($apiKey) === null) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $query = DB::table('ads')
            ->join('brands', 'ads.brand_id', '=', 'brands.id')
            ->select('ads.*', 'brands.name as brand_name');

        if ($request->filled('q') && is_string($request->query('q'))) {
            $q = $request->query('q');
            $like = '%'.strtolower($q).'%';
            $query->where(function ($builder) use ($like): void {
                $builder->whereRaw('LOWER(ads.headline) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(ads.body_text) LIKE ?', [$like]);
            });
        }

        if ($request->filled('niche') && is_string($request->query('niche'))) {
            $slug = NicheCatalog::resolveSlug($request->query('niche'));
            if ($slug !== null) {
                $query->where('ads.niche', $slug);
            }
        }

        if ($request->filled('format') && is_string($request->query('format'))) {
            $format = $request->query('format');
            if (AdFormat::tryFrom($format) !== null) {
                $query->where('ads.format', $format);
            }
        }

        if ($request->filled('status') && is_string($request->query('status'))) {
            $status = $request->query('status');
            if (AdStatus::tryFrom($status) !== null) {
                $query->where('ads.status', $status);
            }
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 20);
        if ($perPage > 100) {
            $perPage = 100;
        }

        $total = (clone $query)->count();

        $rows = $query
            ->orderByDesc('ads.start_date')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $today = now()->startOfDay();

        $data = $rows->map(function ($row) use ($today) {
            $startDate = \Illuminate\Support\Carbon::parse($row->start_date)->startOfDay();
            $status = AdStatus::from($row->status);
            if ($status === AdStatus::Active) {
                $daysActive = (int) $startDate->diffInDays($today);
            } else {
                $endDate = \Illuminate\Support\Carbon::parse($row->end_date)->startOfDay();
                $daysActive = (int) $startDate->diffInDays($endDate);
            }

            return [
                'id' => $row->id,
                'brand' => $row->brand_name,
                'headline' => $row->headline,
                'niche' => NicheCatalog::displayName($row->niche),
                'format' => $row->format,
                'status' => $row->status,
                'start_date' => $startDate->toDateString(),
                'days_active' => $daysActive,
            ];
        });

        return response()->json([
            'data' => $data,
            'page' => $page,
            'total' => $total,
        ]);
    }
}
