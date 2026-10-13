<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiErrorResponse;
use App\Models\ApiKey;
use App\Support\ApiErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiKey
{
    public function handle(Request $request, Closure $next, string $scope = 'search:read'): Response
    {
        $plain = null;

        if ($request->is('api/v0/*')) {
            $queryKey = $request->query('api_key');
            $plain = is_string($queryKey) ? $queryKey : null;
            if ($plain === null || $plain === '') {
                return response()->json(['error' => 'Invalid request'], 400);
            }
        } else {
            $header = $request->header('Authorization', '');
            if (str_starts_with($header, 'Bearer ')) {
                $plain = trim(substr($header, 7));
            }
            if ($plain === null || $plain === '') {
                return ApiErrorResponse::make(
                    ApiErrorCode::Unauthorized,
                    'Missing or invalid Authorization header.',
                    'Send Authorization: Bearer <your_api_key>.',
                    false,
                    401,
                );
            }
        }

        $key = ApiKey::findValidByPlainKey($plain);
        if ($key === null) {
            if ($request->is('api/v0/*')) {
                return response()->json(['error' => 'Invalid request'], 400);
            }

            return ApiErrorResponse::make(
                ApiErrorCode::Unauthorized,
                'API key is invalid or revoked.',
                'Use a valid key or create one on the developer page.',
                false,
                401,
            );
        }

        if ($scope !== '' && ! $key->hasScope($scope)) {
            return ApiErrorResponse::make(
                ApiErrorCode::Unauthorized,
                'API key lacks required scope.',
                'Use a key with the '.$scope.' scope.',
                false,
                403,
            );
        }

        $key->touchLastUsed();
        $request->attributes->set('api_key', $key);

        return $next($request);
    }
}
