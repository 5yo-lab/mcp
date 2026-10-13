<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class OpenApiContractTest extends TestCase
{
    public function test_openapi_paths_match_registered_routes(): void
    {
        $contents = file_get_contents(base_path('docs/openapi.yaml'));
        preg_match_all('/^\s+(\/api\/v1\/[^\s:]+):/m', $contents, $matches);
        $paths = $matches[1];
        sort($paths);

        $expected = [
            '/api/v1/ads',
            '/api/v1/health',
            '/api/v1/niches',
        ];
        sort($expected);

        $this->assertSame($expected, $paths);
    }
}
