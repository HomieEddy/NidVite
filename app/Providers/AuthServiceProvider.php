<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\User;
use App\Policies\ExpensePolicy;
use App\Policies\RepairJobPolicy;
use App\Policies\ReportPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Report::class => ReportPolicy::class,
        User::class => UserPolicy::class,
        RepairJob::class => RepairJobPolicy::class,
        Expense::class => ExpensePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
