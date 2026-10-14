<?php

namespace App\Models;

use App\Exceptions\ExecutionSlotStateException;
use App\enums\ExecutionSlotStatus;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExecutionSlot extends Model
{
    /** @use HasFactory<\Database\Factories\ExecutionSlotFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => ExecutionSlotStatus::Free,
    ];

    protected $fillable = [
        'slot_number',
        'external_port',
        'service_name',
        'status',
        'last_error',
        'hostname',
        'operation_id'
    ];

    protected $casts = [
        'slot_number' => 'integer',
        'external_port' => 'integer',
        'status' => ExecutionSlotStatus::class,
    ];

    public function server(): MorphTo
    {
        return $this->morphTo();
    }

    public function isOccupied(): bool
    {
        return $this->status !== ExecutionSlotStatus::Free;
    }

    public function isAvailable(): bool
    {
        return $this->status === ExecutionSlotStatus::Free;
    }

    public function isAllocatedTo(Model $server): bool
    {
        return $this->status === ExecutionSlotStatus::Allocated
            && $this->server_type === $server->getMorphClass()
            && (int) $this->server_id === (int) $server->getKey();
    }

    public function release(Model $server): bool // this method must run inside a db transaction with lockForUpdate
    {

        /*if ($this->status !== ExecutionSlotStatus::Allocated) {
            throw new ExecutionSlotStateException('Execution slot is not allocated.');
        }*/

        if (! $this->isAllocatedTo($server)) {
            return false;
        }

        $this->server()->dissociate();

        $this->status = ExecutionSlotStatus::Free;

        $this->save();

        return true;
    }
    
    public static function generateHostname(int $slot_number): string 
    {
        // generate the slot hostname based on the app.url config and the slot_number
        return sprintf('sv%d.%s', $slot_number, parse_url(config('app.url'), PHP_URL_HOST));
    }

}
