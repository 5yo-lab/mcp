<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchAdsRequest;
use App\Http\Resources\V1\AdSearchResource;
use App\Services\AdSearchService;
use Illuminate\Http\JsonResponse;

class SearchAdsController extends Controller
{
    public function __construct(private readonly AdSearchService $search) {}

    public function __invoke(SearchAdsRequest $request): JsonResponse
    {
        $result = $this->search->search($request->validatedFilters());

        return response()->json([
            'data' => AdSearchResource::collection($result['data']),
            'meta' => [
                'next_cursor' => $result['next_cursor'],
                'applied_filters' => $result['applied'],
            ],
        ]);
    }
}
