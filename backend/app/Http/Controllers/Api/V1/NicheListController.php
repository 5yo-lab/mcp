<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\NicheCatalog;
use Illuminate\Http\JsonResponse;

class NicheListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $data = [];
        foreach (NicheCatalog::all() as $slug => $meta) {
            $data[] = [
                'slug' => $slug,
                'display_name' => $meta['display_name'],
            ];
        }

        return response()->json(['data' => $data]);
    }
}
