<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\Material;
use App\Models\MaterialPurchase;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Models\Vendor;
use App\Policies\ExpensePolicy;
use App\Policies\MaterialPolicy;
use App\Policies\MaterialPurchasePolicy;
use App\Policies\RepairJobPolicy;
use App\Policies\ReportCategoryPolicy;
use App\Policies\ReportPolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Report::class => ReportPolicy::class,
        User::class => UserPolicy::class,
        RepairJob::class => RepairJobPolicy::class,
        Expense::class => ExpensePolicy::class,
        Vendor::class => VendorPolicy::class,
        Material::class => MaterialPolicy::class,
        MaterialPurchase::class => MaterialPurchasePolicy::class,
        ReportCategory::class => ReportCategoryPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
