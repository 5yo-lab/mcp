<?php

namespace App\Console\Commands;

use App\Enums\AdFormat;
use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\Brand;
use App\Support\NicheCatalog;
use Illuminate\Console\Command;

class AdsIngestCommand extends Command
{
    protected $signature = 'ads:ingest';

    protected $description = 'Live ingest: runs until stopped (1–5 new ads/s, deactivates 1–2 active ads/s)';

    public function handle(): int
    {
        if (Brand::count() === 0) {
            $this->error('Run ads:seed first.');

            return self::FAILURE;
        }

        $this->info('Ingest running. Press Ctrl+C to stop.');

        $brandIds = Brand::query()->pluck('id')->all();
        $nicheSlugs = NicheCatalog::slugs();
        $formats = AdFormat::cases();
        $countryPool = ['US', 'GB', 'DE', 'FR', 'CA', 'AU', 'NL', 'ES', 'IT', 'SE'];
        $counter = Ad::query()->max('id') ?? 0;

        while (true) {
            sleep(1);

            $newCount = random_int(1, 5);
            for ($i = 0; $i < $newCount; $i++) {
                $counter++;
                $niche = $nicheSlugs[array_rand($nicheSlugs)];
                $keywords = NicheCatalog::all()[$niche]['keywords'];
                $keyword = $keywords[array_rand($keywords)];
                $today = now()->startOfDay();
                $backdated = random_int(1, 100) <= 30;
                $startDate = $backdated ? $today->copy()->subDays(random_int(0, 90)) : $today;

                Ad::query()->create([
                    'brand_id' => $brandIds[array_rand($brandIds)],
                    'headline' => sprintf('Live %s ingest %d', $keyword, $counter),
                    'body_text' => sprintf('New live ad featuring %s.', $keyword),
                    'niche' => $niche,
                    'format' => $formats[array_rand($formats)],
                    'status' => AdStatus::Active,
                    'start_date' => $startDate,
                    'end_date' => null,
                    'impression_rank' => random_int(1, 100) > 40 ? random_int(1, 50) : null,
                    'countries' => [$countryPool[array_rand($countryPool)]],
                    'discovered_at' => now(),
                ]);
            }

            $deactivateCount = random_int(1, 2);
            $toDeactivate = Ad::query()
                ->where('status', AdStatus::Active)
                ->inRandomOrder()
                ->limit($deactivateCount)
                ->get();

            foreach ($toDeactivate as $ad) {
                $ad->update([
                    'status' => AdStatus::Inactive,
                    'end_date' => now()->toDateString(),
                ]);
            }
        }
    }
}
