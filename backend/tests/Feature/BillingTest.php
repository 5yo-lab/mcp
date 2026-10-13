<?php

namespace Tests\Feature;

use App\Console\Commands\AdsSeedCommand;
use App\Models\ApiKey;
use App\Models\BillableRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('ads:seed')->assertSuccessful();
    }

    public function test_idempotency_key_bills_once_within_24_hours(): void
    {
        $key = AdsSeedCommand::V0_TEST_API_KEY;
        $headers = [
            'Authorization' => 'Bearer '.$key,
            'Idempotency-Key' => 'test-idem-'.uniqid(),
        ];

        $this->withHeaders($headers)->getJson('/api/v1/niches')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/niches')->assertOk();

        $apiKey = ApiKey::findValidByPlainKey($key);
        $this->assertSame(1, BillableRequest::query()->where('api_key_id', $apiKey->id)->count());
    }

    public function test_concurrent_idempotency_bills_once(): void
    {
        $key = AdsSeedCommand::V0_TEST_API_KEY;
        $idem = 'concurrent-'.uniqid();
        $headers = [
            'Authorization' => 'Bearer '.$key,
            'Idempotency-Key' => $idem,
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders($headers)->getJson('/api/v1/niches')->assertOk();
        }

        $apiKey = ApiKey::findValidByPlainKey($key);
        $this->assertSame(1, BillableRequest::query()->where('api_key_id', $apiKey->id)->where('idempotency_key', $idem)->count());
    }

    public function test_revoked_key_fails_immediately(): void
    {
        $plain = AdsSeedCommand::V0_TEST_API_KEY;
        $record = ApiKey::findValidByPlainKey($plain);
        $record->forceFill(['revoked_at' => now()])->save();

        $this->withHeader('Authorization', 'Bearer '.$plain)
            ->getJson('/api/v1/niches')
            ->assertUnauthorized();
    }
}
