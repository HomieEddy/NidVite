<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isServiceWorker() || $user->isAccountant();
    }

    public function view(User $user, Material $material): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isServiceWorker() || $user->isAccountant();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isServiceWorker();
    }

    public function update(User $user, Material $material): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isServiceWorker();
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function restore(User $user, Material $material): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function forceDelete(User $user, Material $material): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
