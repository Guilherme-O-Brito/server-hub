<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GameServer extends Model
{
    protected $fillable = [
        'name',
        'game_type',
        'config',
        'user_id'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // relação com dono do server
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'gameserver_user')->withTimestamps();
    }

}
