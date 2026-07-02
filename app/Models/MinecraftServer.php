<?php

namespace App\Models;

use App\MinecraftServerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder;

class MinecraftServer extends Model
{
    use HasFactory;

    public const MORPH_TYPE = 'minecraft';

    protected $fillable = [
        'server_name',
        'level_name',
        'motd',
        'difficulty',
        'minecraft_version_id',
        'force_gamemode',
        'allow_flight',
        'status',
        'last_error'
    ];
    
    protected $guarded = ['id', 'owner_id'];

    protected $casts = [
        'force_gamemode' => 'boolean',
        'allow_flight' => 'boolean',
        'difficulty' => 'integer',
        'minecraft_server_id' => 'integer',
        'status' => MinecraftServerStatus::class
    ];

    public function owner() 
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'minecraft_server_admins');
    }

    public function whitelist()
    {
        return $this->hasMany(MinecraftWhitelist::class);
    }

    public function version()
    {
        return $this->belongsTo(MinecraftVersion::class, 'minecraft_version_id');
    }

    public function executionSlot(): MorphOne
    {
        return $this->morphOne(
            ExecutionSlot::class,
            'server'
        );
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('owner_id', $user->id)
            ->orWhereHas('admins', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
    }

    public function getDeployName(): string
    {
        return "minecraft-{$this->id}";
    }

    public function getEnvName(): string
    {
        return "minecraft-env-{$this->id}";
    }
    
    public function getStorageName(): string
    {
        return "minecraft-{$this->id}-storage";
    }
}
