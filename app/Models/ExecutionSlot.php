<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExecutionSlot extends Model
{
    /** @use HasFactory<\Database\Factories\ExecutionSlotFactory> */
    use HasFactory;
    
    public const string STATUS_STOPPED = 'stopped';
    public const string STATUS_STARTING = 'starting';
    public const string STATUS_RUNNING = 'running';
    public const string STATUS_STOPPING = 'stopping';
    public const string STATUS_FAILED = 'failed';

    protected $fillable = [
        'slot_number',
        'external_port',
        'service_name',
        'game_name',
        'status',
    ];

    public function server(): MorphTo
    {
        return $this->morphTo();
    }

    public function isOccupied(): bool
    {
        return $this->server_id !== null;
    }

    public function isAvailable(): bool
    {
        return $this->server_id === null;
    }

    public function allocate($server) {

    }

    public function release() {
        
    }
}
