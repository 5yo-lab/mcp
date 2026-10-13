<?php

return [
    /*
    | Billable call: one row in billable_requests per counted call.
    | Same Idempotency-Key on the same API key within 24 hours is billed once.
    | MCP retries should send the same Idempotency-Key to avoid double billing.
    */
    'monthly_quota' => (int) env('API_MONTHLY_QUOTA', 1000),
    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
    'idempotency_ttl_hours' => 24,
];
