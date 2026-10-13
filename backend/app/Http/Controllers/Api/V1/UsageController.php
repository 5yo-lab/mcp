<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function __invoke(Request $request, BillingService $billing): JsonResponse
    {
        /** @var ApiKey $key */
        $key = $request->attributes->get('api_key');

        $used = $billing->usageThisMonth($key);
        $quota = config('billing.monthly_quota');

        return response()->json([
            'data' => [
                'period_start' => now()->startOfMonth()->toDateString(),
                'billable_calls' => $used,
                'quota' => $quota,
                'remaining' => max(0, $quota - $used),
                'resets_at' => $billing->quotaResetsAt(),
            ],
        ]);
    }
}
