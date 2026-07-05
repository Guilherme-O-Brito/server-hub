<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinecraftVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled', 
        'version',
        'sort_order'
    ];

    protected $casts = [
        'is_enabled' => 'boolean'
    ];

    protected static function booted(): void
    {
        static::saving(function (MinecraftVersion $minecraftVersion) {
            $minecraftVersion->sort_order = self::makeSortOrder($minecraftVersion->version);
        });
    }

    public function minecraftServers()
    {
        return $this->hasMany(MinecraftServer::class, 'minecraft_version_id');
    }

    protected static function makeSortOrder(string $version): int
    {
        $parts = array_map('intval', explode('.', $version));

        $major = $parts[0] ?? 0;
        $minor = $parts[1] ?? 0;
        $patch = $parts[2] ?? 0;

        return ($major * 10000) + ($minor * 100) + $patch;
    }

    public function scopeOrderedDesc(Builder $query): Builder
    {
        return $query->orderByDesc('sort_order');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
