<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinecraftServer extends Model
{
    
    protected $fillable = [
        'server_name',
        'level_name',
        'motd',
        'difficulty',
        'force_gamemode',
        'allow_flight',
    ];
    
    protected $guarded = ['id', 'owner'];

    protected $casts = [
        'force_gamemode' => 'boolean',
        'allow_flight' => 'boolean',
        'difficulty' => 'integer',
    ];

    public function owner() 
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'minecraft_server_admins');
    }

}
