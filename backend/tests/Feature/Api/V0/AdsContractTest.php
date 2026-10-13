<?php

namespace Tests\Feature\Api\V0;

use App\Console\Commands\AdsSeedCommand;
use App\Enums\AdFormat;
use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsContractTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = AdsSeedCommand::V0_TEST_API_KEY;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('ads:seed')->assertSuccessful();
    }

    public function test_bad_api_key_returns_400_invalid_request(): void
    {
        $this->getJson('/api/v0/ads?api_key=not-a-real-key')
            ->assertStatus(400)
            ->assertExactJson(['error' => 'Invalid request']);
    }

    public function test_missing_api_key_returns_400_invalid_request(): void
    {
        $this->getJson('/api/v0/ads')
            ->assertStatus(400)
            ->assertExactJson(['error' => 'Invalid request']);
    }

    public function test_success_response_shape_and_days_active(): void
    {
        $brand = Brand::query()->first();
        Ad::query()->delete();
        Ad::query()->create([
            'brand_id' => $brand->id,
            'headline' => 'Contract shape headline',
            'body_text' => 'Body',
            'niche' => 'skincare',
            'format' => AdFormat::Video,
            'status' => AdStatus::Active,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => null,
            'impression_rank' => null,
            'countries' => ['US'],
            'discovered_at' => now(),
        ]);

        $response = $this->getJson('/api/v0/ads?api_key='.self::KEY);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'brand', 'headline', 'niche', 'format', 'status', 'start_date', 'days_active']],
                'page',
                'total',
            ]);

        $row = $response->json('data.0');
        $this->assertSame('Skincare', $row['niche']);
        $this->assertSame(10, $row['days_active']);
    }

    public function test_invalid_niche_filter_is_ignored_not_applied(): void
    {
        Ad::query()->delete();
        $brand = Brand::query()->first();
        Ad::query()->create([
            'brand_id' => $brand->id,
            'headline' => 'Only skincare',
            'body_text' => 'x',
            'niche' => 'skincare',
            'format' => AdFormat::Image,
            'status' => AdStatus::Active,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'impression_rank' => null,
            'countries' => ['US'],
            'discovered_at' => now(),
        ]);

        $withBadNiche = $this->getJson('/api/v0/ads?api_key='.self::KEY.'&niche=not-a-niche')->json('total');
        $without = $this->getJson('/api/v0/ads?api_key='.self::KEY)->json('total');

        $this->assertSame($without, $withBadNiche);
    }

    public function test_niche_accepts_display_name_case_insensitive(): void
    {
        Ad::query()->delete();
        $brand = Brand::query()->first();
        Ad::query()->create([
            'brand_id' => $brand->id,
            'headline' => 'Pet ad',
            'body_text' => 'kibble',
            'niche' => 'pet-supplies',
            'format' => AdFormat::Image,
            'status' => AdStatus::Active,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'impression_rank' => null,
            'countries' => ['US'],
            'discovered_at' => now(),
        ]);
        Ad::query()->create([
            'brand_id' => $brand->id,
            'headline' => 'Other',
            'body_text' => 'x',
            'niche' => 'skincare',
            'format' => AdFormat::Image,
            'status' => AdStatus::Active,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'impression_rank' => null,
            'countries' => ['US'],
            'discovered_at' => now(),
        ]);

        $response = $this->getJson('/api/v0/ads?api_key='.self::KEY.'&niche='.urlencode('pet supplies'));

        $response->assertOk();
        $this->assertSame(1, $response->json('total'));
        $this->assertSame('Pet Supplies', $response->json('data.0.niche'));
    }

    public function test_per_page_above_100_is_silently_clamped(): void
    {
        $response = $this->getJson('/api/v0/ads?api_key='.self::KEY.'&per_page=500');

        $response->assertOk();
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    public function test_unknown_query_parameters_are_ignored(): void
    {
        $base = $this->getJson('/api/v0/ads?api_key='.self::KEY)->json('total');
        $withUnknown = $this->getJson('/api/v0/ads?api_key='.self::KEY.'&foo=bar&sort=hack')->json('total');

        $this->assertSame($base, $withUnknown);
    }

    public function test_invalid_format_filter_is_ignored(): void
    {
        $all = $this->getJson('/api/v0/ads?api_key='.self::KEY)->json('total');
        $filtered = $this->getJson('/api/v0/ads?api_key='.self::KEY.'&format=not-valid')->json('total');

        $this->assertSame($all, $filtered);
    }

    public function test_q_is_case_insensitive_substring_on_headline_or_body(): void
    {
        Ad::query()->delete();
        $brand = Brand::query()->first();
        Ad::query()->create([
            'brand_id' => $brand->id,
            'headline' => 'UPPER serum',
            'body_text' => 'plain',
            'niche' => 'skincare',
            'format' => AdFormat::Image,
            'status' => AdStatus::Active,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'impression_rank' => null,
            'countries' => ['US'],
            'discovered_at' => now(),
        ]);

        $this->getJson('/api/v0/ads?api_key='.self::KEY.'&q=serum')
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->getJson('/api/v0/ads?api_key='.self::KEY.'&q=SERUM')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }
}
