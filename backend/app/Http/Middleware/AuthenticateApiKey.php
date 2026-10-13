<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiErrorResponse;
use App\Models\ApiKey;
use App\Support\ApiErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next, string $scope = 'search:read'): Response
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return ApiErrorResponse::make(
                ApiErrorCode::Unauthorized,
                'Missing or invalid Authorization header.',
                'Send Authorization: Bearer <your_api_key>.',
                false,
                401,
            );
        }

        $plain = trim(substr($header, 7));
        $key = ApiKey::findValidByPlainKey($plain);

        if ($key === null) {
            return ApiErrorResponse::make(
                ApiErrorCode::Unauthorized,
                'API key is invalid or revoked.',
                'Create a new key on the developer page or use a valid key.',
                false,
                401,
            );
        }

        if (! $key->hasScope($scope)) {
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
