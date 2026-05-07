<?php

namespace App\Policies;

use App\Models\MaterialPurchase;
use App\Models\User;

class MaterialPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isServiceWorker() || $user->isAccountant();
    }

    public function view(User $user, MaterialPurchase $materialPurchase): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isServiceWorker() || $user->isAccountant();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isAccountant();
    }

    public function update(User $user, MaterialPurchase $materialPurchase): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isAccountant();
    }

    public function delete(User $user, MaterialPurchase $materialPurchase): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function restore(User $user, MaterialPurchase $materialPurchase): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function forceDelete(User $user, MaterialPurchase $materialPurchase): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
