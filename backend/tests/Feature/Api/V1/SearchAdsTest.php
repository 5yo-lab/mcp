<?php

namespace Tests\Feature\Api\V1;

use App\Console\Commands\AdsSeedCommand;
use App\Enums\AdFormat;
use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\Brand;
use App\Services\AdSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAdsTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = AdsSeedCommand::V0_TEST_API_KEY;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('ads:seed')->assertSuccessful();
    }

    private function search(array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer '.self::KEY)
            ->getJson('/api/v1/ads?'.http_build_query($query));
    }

    public function test_unknown_parameter_is_rejected(): void
    {
        $this->search(['nich' => 'skincare'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'unknown_parameter');
    }

    public function test_filter_q_reduces_results(): void
    {
        $total = $this->search()->json('meta.applied_filters');
        $this->assertNotEmpty($total);

        $unique = 'xyzzy_unique_term_'.uniqid();
        $brand = Brand::query()->first();
        Ad::query()->create([
            'brand_id' => $brand->id,
            'headline' => $unique,
            'body_text' => 'body',
            'niche' => 'skincare',
            'format' => AdFormat::Image,
            'status' => AdStatus::Active,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'impression_rank' => null,
            'countries' => ['US'],
            'discovered_at' => now(),
        ]);

        $all = count($this->search()->json('data'));
        $filtered = count($this->search(['q' => $unique])->json('data'));

        $this->assertSame(1, $filtered);
        $this->assertGreaterThan($filtered, $all);
    }

    public function test_filter_niche_reduces_results(): void
    {
        $before = $this->search()->json('data');
        $after = $this->search(['niche' => 'skincare'])->json('data');
        $beforeNiches = array_unique(array_column($before, 'niche'));
        $this->assertGreaterThan(1, count($beforeNiches));
        foreach ($after as $row) {
            $this->assertSame('skincare', $row['niche']);
        }
    }

    public function test_filter_format_reduces_results(): void
    {
        $unfiltered = $this->search(['per_page' => 100])->json('data');
        $formats = array_unique(array_column($unfiltered, 'format'));
        $this->assertGreaterThan(1, count($formats));

        $this->assertTrue(
            collect($unfiltered)->contains(fn (array $row): bool => $row['format'] !== 'video'),
            'Seed must include non-video ads so this filter can be proven.',
        );

        $filtered = $this->search(['format' => 'video', 'per_page' => 100])->json('data');
        $this->assertNotEmpty($filtered);
        $this->assertFalse(
            collect($filtered)->contains(fn (array $row): bool => $row['format'] !== 'video'),
            'format=video must not return image or carousel ads.',
        );
    }

    public function test_filter_status_reduces_results(): void
    {
        $unfiltered = $this->search(['per_page' => 100])->json('data');
        $statuses = array_unique(array_column($unfiltered, 'status'));
        $this->assertGreaterThan(1, count($statuses));

        $this->assertTrue(
            collect($unfiltered)->contains(fn (array $row): bool => $row['status'] !== 'inactive'),
            'Seed must include active ads so this filter can be proven.',
        );

        $filtered = $this->search(['status' => 'inactive', 'per_page' => 100])->json('data');
        $this->assertNotEmpty($filtered);
        $this->assertFalse(
            collect($filtered)->contains(fn (array $row): bool => $row['status'] !== 'inactive'),
            'status=inactive must not return active ads.',
        );
    }

    public function test_filter_started_after_reduces_results(): void
    {
        $future = now()->addYear()->toDateString();
        $after = $this->search(['started_after' => $future])->json('data');
        $this->assertSame([], $after);

        $past = now()->subYears(3)->toDateString();
        $many = count($this->search(['started_after' => $past])->json('data'));
        $this->assertGreaterThan(0, $many);
    }

    public function test_impressions_sort_puts_null_rank_last(): void
    {
        $marker = 'ImpressionsSortMarker'.uniqid();
        $brand = Brand::query()->first();
        Ad::query()->create([
            'brand_id' => $brand->id,
            'headline' => $marker.' Ranked',
            'body_text' => 'x',
            'niche' => 'skincare',
            'format' => AdFormat::Image,
            'status' => AdStatus::Active,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => null,
            'impression_rank' => 1,
            'countries' => ['US'],
            'discovered_at' => now(),
        ]);
        Ad::query()->create([
            'brand_id' => $brand->id,
            'headline' => $marker.' Unranked',
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

        $data = $this->search([
            'q' => $marker,
            'sort' => AdSearchService::SORT_IMPRESSIONS,
            'per_page' => 10,
        ])->json('data');

        $this->assertCount(2, $data);
        $this->assertStringContainsString('Ranked', $data[0]['headline']);
        $this->assertStringContainsString('Unranked', $data[1]['headline']);
    }

    public function test_cursor_walk_has_no_duplicates(): void
    {
        Ad::query()->delete();
        $brand = Brand::query()->first();
        for ($i = 0; $i < 25; $i++) {
            Ad::query()->create([
                'brand_id' => $brand->id,
                'headline' => 'Ad '.$i,
                'body_text' => 'x',
                'niche' => 'skincare',
                'format' => AdFormat::Image,
                'status' => AdStatus::Active,
                'start_date' => now()->subDays($i)->toDateString(),
                'end_date' => null,
                'impression_rank' => null,
                'countries' => ['US'],
                'discovered_at' => now(),
            ]);
        }

        $seen = [];
        $cursor = null;
        do {
            $query = ['per_page' => 5, 'sort' => 'newest'];
            if ($cursor) {
                $query['cursor'] = $cursor;
            }
            $response = $this->search($query)->assertOk();
            foreach ($response->json('data') as $row) {
                $this->assertNotContains($row['id'], $seen);
                $seen[] = $row['id'];
            }
            $cursor = $response->json('meta.next_cursor');
        } while ($cursor !== null);

        $this->assertCount(25, $seen);
    }

    public function test_applied_filters_echoed_on_success(): void
    {
        $this->search(['niche' => 'skincare', 'sort' => 'newest'])
            ->assertOk()
            ->assertJsonPath('meta.applied_filters.niche', ['skincare'])
            ->assertJsonPath('meta.applied_filters.sort', 'newest');
    }
}
