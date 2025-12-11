<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    /**
     * Determine if the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, Server $server): bool
    {
        return $user->id === $server->user_id;
    }

    /**
     * Determine if the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, Server $server): bool
    {
        return $user->id === $server->user_id;
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, Server $server): bool
    {
        return $user->id === $server->user_id;
    }
}
