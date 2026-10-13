<?php

namespace App\Console\Commands;

use App\Enums\AdFormat;
use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\ApiKey;
use App\Models\Brand;
use App\Support\NicheCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdsSeedCommand extends Command
{
    protected $signature = 'ads:seed';

    protected $description = 'Deterministic seed: 300 brands, 20k+ ads, v0 test API key';

    public const V0_TEST_API_KEY = 'ghk_v0_contract_test_key_00000001';

    public function handle(): int
    {
        mt_srand(42);

        DB::transaction(function (): void {
            Ad::query()->delete();
            Brand::query()->delete();
            ApiKey::query()->delete();

            $brandIds = [];
            for ($i = 1; $i <= 300; $i++) {
                $brand = Brand::query()->create(['name' => sprintf('Brand %03d', $i)]);
                $brandIds[] = $brand->id;
            }

            $countryPool = ['US', 'GB', 'DE', 'FR', 'CA', 'AU', 'NL', 'ES', 'IT', 'SE'];
            $formats = AdFormat::cases();
            $nicheSlugs = NicheCatalog::slugs();
            $today = now()->startOfDay();

            $adsByBrand = array_fill_keys($brandIds, []);

            for ($n = 0; $n < 20000; $n++) {
                $brandId = $brandIds[mt_rand(0, count($brandIds) - 1)];
                $niche = $nicheSlugs[mt_rand(0, count($nicheSlugs) - 1)];
                $keywords = NicheCatalog::all()[$niche]['keywords'];
                $keyword = $keywords[mt_rand(0, count($keywords) - 1)];
                $active = mt_rand(1, 100) <= 60;
                $status = $active ? AdStatus::Active : AdStatus::Inactive;
                $startOffsetDays = mt_rand(0, 730);
                $startDate = $today->copy()->subDays($startOffsetDays);
                $endDate = $active ? null : $startDate->copy()->addDays(mt_rand(1, 180));
                $discoveredAt = $startDate->copy()->addDays(mt_rand(0, min(30, $startOffsetDays)));

                $ad = Ad::query()->create([
                    'brand_id' => $brandId,
                    'headline' => sprintf('%s offer %d', ucfirst($keyword), $n + 1),
                    'body_text' => sprintf('Discover our %s with %s today.', NicheCatalog::displayName($niche), $keyword),
                    'niche' => $niche,
                    'format' => $formats[mt_rand(0, count($formats) - 1)],
                    'status' => $status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'impression_rank' => null,
                    'countries' => $this->pickCountries($countryPool),
                    'discovered_at' => $discoveredAt,
                ]);

                $adsByBrand[$brandId][] = $ad->id;
            }

            foreach ($adsByBrand as $brandId => $adIds) {
                $rankable = [];
                foreach ($adIds as $adId) {
                    if (mt_rand(1, 100) > 40) {
                        $rankable[] = $adId;
                    }
                }
                shuffle($rankable);
                foreach ($rankable as $index => $adId) {
                    Ad::query()->whereKey($adId)->update(['impression_rank' => $index + 1]);
                }
            }

            ApiKey::query()->create([
                'prefix' => ApiKey::prefixFromPlainKey(self::V0_TEST_API_KEY),
                'key_hash' => ApiKey::hashPlainKey(self::V0_TEST_API_KEY),
                'scopes' => ['search:read', 'keys:manage'],
            ]);
        });

        $this->info('Seeded '.Brand::count().' brands, '.Ad::count().' ads, 1 API key for v0 tests.');

        return self::SUCCESS;
    }

    /** @param list<string> $pool */
    private function pickCountries(array $pool): array
    {
        $count = mt_rand(1, 3);
        $keys = array_rand(array_flip($pool), $count);
        if (! is_array($keys)) {
            return [$keys];
        }

        return array_values($keys);
    }
}
