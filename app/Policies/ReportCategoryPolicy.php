<?php

namespace App\Policies;

use App\Models\ReportCategory;
use App\Models\User;

class ReportCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ReportCategory $reportCategory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, ReportCategory $reportCategory): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function delete(User $user, ReportCategory $reportCategory): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function restore(User $user, ReportCategory $reportCategory): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function forceDelete(User $user, ReportCategory $reportCategory): bool
    {
        return $user->isAdmin() || $user->isManager();
    }
}
