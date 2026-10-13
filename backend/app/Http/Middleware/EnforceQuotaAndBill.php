<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiErrorResponse;
use App\Models\ApiKey;
use App\Services\BillingService;
use App\Support\ApiErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceQuotaAndBill
{
    public function __construct(private readonly BillingService $billing) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var ApiKey|null $key */
        $key = $request->attributes->get('api_key');
        if ($key === null) {
            return $next($request);
        }

        if ($this->billing->quotaExceeded($key)) {
            return ApiErrorResponse::make(
                ApiErrorCode::QuotaExceeded,
                'Monthly billable quota exceeded.',
                'Quota resets at '.$this->billing->quotaResetsAt().'. Do not retry until then.',
                false,
                402,
                ['quota' => config('billing.monthly_quota'), 'resets_at' => $this->billing->quotaResetsAt()],
            );
        }

        $response = $next($request);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $idempotencyKey = $request->header('Idempotency-Key');
            $this->billing->recordBillable($key, is_string($idempotencyKey) ? $idempotencyKey : null);
        }

        return $response;
    }
}
