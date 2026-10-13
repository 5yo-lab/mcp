<?php

namespace App\Http\Responses;

use App\Support\ApiErrorCode;
use Illuminate\Http\JsonResponse;

class ApiErrorResponse
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    public static function make(
        ApiErrorCode $code,
        string $message,
        string $nextStep,
        bool $retryable,
        int $status,
        ?array $details = null,
    ): JsonResponse {
        $body = [
            'code' => $code->value,
            'message' => $message,
            'next_step' => $nextStep,
            'retryable' => $retryable,
        ];

        if ($details !== null) {
            $body['details'] = $details;
        }

        return response()->json($body, $status);
    }
}
