<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinecraftVersion extends Model
{
    use HasFactory;

    protected $fillable = ['is_enabled'];

    protected $guarded = ['version'];

    protected $casts = [
        'is_enabled' => 'boolean'
    ];

    public function minecraftServers()
    {
        return $this->hasMany(MinecraftServer::class, 'minecraft_version_id');
    }
}
