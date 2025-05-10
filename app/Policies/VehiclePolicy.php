<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Access\Response;

class VehiclePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view vehicles
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return true; // All authenticated users can view vehicle details
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only administrators can create new vehicles
        return $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        // Only administrators can update vehicles
        return $user->role === 'Administrator';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        // Only administrators can delete vehicles
        return $user->role === 'Administrator';
    }
}
