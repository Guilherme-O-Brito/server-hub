<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinecraftWhitelist extends Model
{
    use HasFactory;
    protected $fillable = ['nickname'];

    protected $guarded = ['id', 'server_id'];

    public function server()
    {
        return $this->belongsTo(MinecraftServer::class);
    }
}