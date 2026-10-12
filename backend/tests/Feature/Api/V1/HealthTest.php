<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_returns_ok_json(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_health_allows_spa_origin_via_cors(): void
    {
        $response = $this->withHeader('Origin', 'http://localhost:5173')
            ->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }
}
