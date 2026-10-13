<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiErrorResponse;
use App\Models\ApiKey;
use App\Support\ApiErrorCode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ApiKey|null $key */
        $key = $request->attributes->get('api_key');
        if ($key === null) {
            return $next($request);
        }

        $limit = config('billing.rate_limit_per_minute');
        $bucket = now()->format('Y-m-d-H-i');
        $cacheKey = "rate:{$key->id}:{$bucket}";

        $count = (int) Cache::increment($cacheKey);
        if ($count === 1) {
            Cache::put($cacheKey, 1, now()->addMinutes(2));
        }

        if ($count > $limit) {
            $retryAfter = 60 - (int) now()->format('s');

            return ApiErrorResponse::make(
                ApiErrorCode::RateLimited,
                'Rate limit exceeded.',
                'Wait before retrying. Respect Retry-After.',
                true,
                429,
            )->withHeaders(['Retry-After' => (string) max(1, $retryAfter)]);
        }

        return $next($request);
    }
}
