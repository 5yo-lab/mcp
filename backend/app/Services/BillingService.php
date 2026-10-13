<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\BillableRequest;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function usageThisMonth(ApiKey $key): int
    {
        $start = now()->startOfMonth();

        return BillableRequest::query()
            ->where('api_key_id', $key->id)
            ->where('created_at', '>=', $start)
            ->count();
    }

    public function quotaExceeded(ApiKey $key): bool
    {
        return $this->usageThisMonth($key) >= config('billing.monthly_quota');
    }

    public function quotaResetsAt(): string
    {
        return now()->startOfMonth()->addMonth()->toIso8601String();
    }

    /**
     * Record a billable call. Returns false if idempotency duplicate (not billed again).
     */
    public function recordBillable(ApiKey $key, ?string $idempotencyKey): bool
    {
        return (bool) DB::transaction(function () use ($key, $idempotencyKey): bool {
            ApiKey::query()->whereKey($key->id)->lockForUpdate()->first();

            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $since = now()->subHours(config('billing.idempotency_ttl_hours'));
                $exists = BillableRequest::query()
                    ->where('api_key_id', $key->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->where('created_at', '>=', $since)
                    ->exists();
                if ($exists) {
                    return false;
                }
            }

            BillableRequest::query()->create([
                'api_key_id' => $key->id,
                'idempotency_key' => $idempotencyKey ?: null,
                'created_at' => now(),
            ]);

            return true;
        });
    }
}
