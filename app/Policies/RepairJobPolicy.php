<?php

namespace App\Policies;

use App\Models\RepairJob;
use App\Models\User;

class RepairJobPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RepairJob $repairJob): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isServiceWorker();
    }

    public function update(User $user, RepairJob $repairJob): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isServiceWorker();
    }

    public function delete(User $user, RepairJob $repairJob): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, RepairJob $repairJob): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, RepairJob $repairJob): bool
    {
        return $user->isAdmin();
    }
}
