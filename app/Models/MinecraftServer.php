<?php

namespace App\Models;

use App\MinecraftServerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MinecraftServer extends Model
{
    use HasFactory;

    public const MORPH_TYPE = 'minecraft';

    protected $fillable = [
        'server_name',
        'level_name',
        'motd',
        'difficulty',
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

    public function executionSlot(): MorphOne
    {
        return $this->morphOne(
            ExecutionSlot::class,
            'server'
        );
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
