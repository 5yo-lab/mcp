<?php

namespace App\Models;

use App\Enums\AdFormat;
use App\Enums\AdStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    protected $fillable = [
        'brand_id',
        'headline',
        'body_text',
        'niche',
        'format',
        'status',
        'start_date',
        'end_date',
        'impression_rank',
        'countries',
        'discovered_at',
    ];

    protected function casts(): array
    {
        return [
            'format' => AdFormat::class,
            'status' => AdStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'countries' => 'array',
            'discovered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function daysActive(): int
    {
        if ($this->status === AdStatus::Active) {
            return (int) $this->start_date->diffInDays(now()->startOfDay(), false);
        }

        return (int) $this->start_date->diffInDays($this->end_date, false);
    }
}
