<?php

namespace App\Http\Resources\V1;

use App\Support\NicheCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Ad */
class AdSearchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brand_id,
            'brand_name' => $this->brand->name,
            'headline' => $this->headline,
            'body_text' => $this->body_text,
            'niche' => $this->niche,
            'niche_display' => NicheCatalog::displayName($this->niche),
            'format' => $this->format->value,
            'status' => $this->status->value,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'days_active' => $this->daysActive(),
            'impression_rank' => $this->impression_rank,
            'countries' => $this->countries,
        ];
    }
}
