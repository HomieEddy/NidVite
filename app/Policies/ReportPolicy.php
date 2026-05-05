<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Report $report): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Report $report): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Report $report): bool
    {
        return $user->isAdmin();
    }
}
