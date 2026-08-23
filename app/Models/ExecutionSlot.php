<?php

namespace App\Models;

use App\Exceptions\ExecutionSlotStateException;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExecutionSlot extends Model
{
    /** @use HasFactory<\Database\Factories\ExecutionSlotFactory> */
    use HasFactory;

    public const string STATUS_FREE = 'free';
    public const string STATUS_PROVISIONING = 'provisioning';
    public const string STATUS_DELETING = 'deleting';
    public const string STATUS_ALLOCATED = 'allocated';
    public const string STATUS_FAILED = 'failed';

    protected $attributes = [
        'status' => self::STATUS_FREE,
    ];

    protected $fillable = [
        'slot_number',
        'external_port',
        'service_name',
        'status',
        'last_error',
        'hostname'
    ];

    protected $casts = [
        'slot_number' => 'integer',
        'external_port' => 'integer',
    ];

    public function server(): MorphTo
    {
        return $this->morphTo();
    }

    public function isOccupied(): bool
    {
        return $this->status !== ExecutionSlot::STATUS_FREE;
    }

    public function isAvailable(): bool
    {
        return $this->status === ExecutionSlot::STATUS_FREE;
    }

    public function isAllocatedTo(Model $server): bool
    {
        return $this->status === self::STATUS_ALLOCATED
            && $this->server_type === $server->getMorphClass()
            && (int) $this->server_id === (int) $server->getKey();
    }

    public function release(Model $server): bool // this method must run inside a db transaction with lockForUpdate
    {

        /*if ($this->status !== self::STATUS_ALLOCATED) {
            throw new ExecutionSlotStateException('Execution slot is not allocated.');
        }*/

        if (! $this->isAllocatedTo($server)) {
            return false;
        }

        $this->server()->dissociate();

        $this->status = self::STATUS_FREE;

        $this->save();

        return true;
    }
    
    public static function generateHostname(int $slot_number): string 
    {
        // generate the slot hostname based on the app.url config and the slot_number
        return sprintf('sv%d.%s', $slot_number, parse_url(config('app.url'), PHP_URL_HOST));
    }

}