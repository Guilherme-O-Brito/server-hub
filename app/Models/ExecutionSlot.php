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

    public function release() {
        
        DB::transaction(function () {
            $slot = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($slot->status !== self::STATUS_ALLOCATED) {
                throw new ExecutionSlotStateException('Execution slot is not allocated.');
            }

            $slot->server()->dissociate();

            $slot->status = self::STATUS_FREE;

            $slot->save();

        });

    }
}
