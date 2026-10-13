<?php

namespace App\Support;

class NicheCatalog
{
    /** @return array<string, array{display_name: string, keywords: list<string>}> */
    public static function all(): array
    {
        return config('niches');
    }

    public static function slugs(): array
    {
        return array_keys(self::all());
    }

    public static function displayName(string $slug): string
    {
        return self::all()[$slug]['display_name'] ?? $slug;
    }

    public static function resolveSlug(?string $input): ?string
    {
        if ($input === null || $input === '') {
            return null;
        }

        $needle = strtolower(trim($input));

        foreach (self::all() as $slug => $meta) {
            if ($needle === strtolower($slug) || $needle === strtolower($meta['display_name'])) {
                return $slug;
            }
        }

        return null;
    }
}
