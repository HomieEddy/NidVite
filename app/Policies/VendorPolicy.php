<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isAccountant();
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isAccountant();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function restore(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function forceDelete(User $user, Vendor $vendor): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
