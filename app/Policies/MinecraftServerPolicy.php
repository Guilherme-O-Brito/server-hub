<?php

namespace App\Policies;

use App\Models\MinecraftServer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MinecraftServerPolicy
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
    public function view(User $user, MinecraftServer $minecraftServer): bool
    {
        return $minecraftServer->owner_id === $user->id || $minecraftServer->admins()->where('users.id', $user->id)->exists();
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
    public function update(User $user, MinecraftServer $minecraftServer): bool
    {
        return $minecraftServer->owner_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MinecraftServer $minecraftServer): bool
    {
        return $minecraftServer->owner_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MinecraftServer $minecraftServer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MinecraftServer $minecraftServer): bool
    {
        return false;
    }
    public function manageWhitelist(User $user, MinecraftServer $minecraftServer): bool
    {   
        return $minecraftServer->owner_id === $user->id || $minecraftServer->admins()->where('users.id', $user->id)->exists();
    }

    public function manageOperators(User $user, MinecraftServer $minecraftServer): bool
    {
        return $minecraftServer->owner_id === $user->id;
    }

    public function start(User $user, MinecraftServer $minecraftServer): bool
    {
        return $minecraftServer->owner_id === $user->id || $minecraftServer->admins()->where('users.id', $user->id)->exists();
    }

    public function stop(User $user, MinecraftServer $minecraftServer): bool
    {
        return $minecraftServer->owner_id === $user->id || $minecraftServer->admins()->where('users.id', $user->id)->exists();
    }
}
