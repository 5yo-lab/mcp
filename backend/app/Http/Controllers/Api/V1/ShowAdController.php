<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AdSearchResource;
use App\Http\Responses\ApiErrorResponse;
use App\Models\Ad;
use App\Support\ApiErrorCode;
use Illuminate\Http\JsonResponse;

class ShowAdController extends Controller
{
    public function __invoke(int $id): JsonResponse
    {
        $ad = Ad::query()->with('brand')->find($id);

        if ($ad === null) {
            return ApiErrorResponse::make(
                ApiErrorCode::NotFound,
                'Ad not found.',
                'Check the id from search_ads results.',
                false,
                404,
            );
        }

        return response()->json(['data' => new AdSearchResource($ad)]);
    }
}
