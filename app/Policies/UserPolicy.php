<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only administrators and approvers can view user lists
        return in_array($user->role, ['Administrator', 'Approver']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile, their subordinates, or if they are admins
        return $user->id === $model->id || 
            $user->role === 'Administrator' || 
            $model->supervisor_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only administrators can create users
        return $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile, or administrators can update any profile
        return $user->id === $model->id || $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only administrators can delete users and they cannot delete themselves
        return $user->role === 'Administrator' && $user->id !== $model->id;
    }
}


// app/Policies/UserPolicy.php