<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiErrorResponse;
use App\Models\ApiKey;
use App\Support\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keys = ApiKey::query()
            ->orderByDesc('created_at')
            ->get(['id', 'prefix', 'scopes', 'created_at', 'last_used_at', 'revoked_at']);

        return response()->json([
            'data' => $keys->map(fn (ApiKey $key) => [
                'id' => $key->id,
                'prefix' => $key->prefix,
                'scopes' => $key->scopes,
                'created_at' => $key->created_at?->toIso8601String(),
                'last_used_at' => $key->last_used_at?->toIso8601String(),
                'revoked' => $key->revoked_at !== null,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'in:search:read,keys:manage'],
        ]);

        $plain = 'ghk_'.Str::lower(Str::random(32));
        $record = ApiKey::query()->create([
            'prefix' => ApiKey::prefixFromPlainKey($plain),
            'key_hash' => ApiKey::hashPlainKey($plain),
            'scopes' => array_values(array_unique($validated['scopes'])),
        ]);

        return response()->json([
            'data' => [
                'id' => $record->id,
                'prefix' => $record->prefix,
                'secret' => $plain,
                'scopes' => $record->scopes,
                'created_at' => $record->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        if ($apiKey->revoked_at !== null) {
            return ApiErrorResponse::make(
                ApiErrorCode::ValidationError,
                'Key is already revoked.',
                'No action needed.',
                false,
                422,
            );
        }

        $apiKey->forceFill(['revoked_at' => now()])->save();

        return response()->json(['data' => ['id' => $apiKey->id, 'revoked' => true]]);
    }
}
