<?php

namespace App\Policies;

use App\Models\ExecutionSlot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExecutionSlotPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ExecutionSlot $executionSlot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view sensitive data.
     */
    public function viewSensitiveData(User $user, ExecutionSlot $executionSlot): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExecutionSlot $executionSlot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExecutionSlot $executionSlot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExecutionSlot $executionSlot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExecutionSlot $executionSlot): bool
    {
        return false;
    }
}
