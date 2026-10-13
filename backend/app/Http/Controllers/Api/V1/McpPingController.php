<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class McpPingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\ApiKey $key */
        $key = $request->attributes->get('api_key');
        Cache::put('mcp:first_ping:'.$key->id, now()->toIso8601String(), now()->addDays(7));

        return response()->json(['data' => ['received_at' => now()->toIso8601String()]]);
    }

    public static function firstPingAt(int $apiKeyId): ?string
    {
        return Cache::get('mcp:first_ping:'.$apiKeyId);
    }
}
