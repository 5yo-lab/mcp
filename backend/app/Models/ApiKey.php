<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'prefix',
        'key_hash',
        'scopes',
        'revoked_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public static function hashPlainKey(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    public static function findValidByPlainKey(string $plainKey): ?self
    {
        $hash = self::hashPlainKey($plainKey);

        return self::query()
            ->where('key_hash', $hash)
            ->whereNull('revoked_at')
            ->first();
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }

    public static function prefixFromPlainKey(string $plainKey): string
    {
        return Str::substr($plainKey, 0, 8);
    }
}
