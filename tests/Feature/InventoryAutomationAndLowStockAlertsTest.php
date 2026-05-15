<?php

use App\Models\JobMaterial;
use App\Models\Material;
use App\Models\RepairJob;
use App\Models\Role;
use App\Models\User;
use App\Notifications\LowStockMaterialAlertNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('decrements material current and reserved stock when a repair job is completed', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $material = Material::query()->create([
        'sku' => 'MAT-301',
        'name' => 'Cold Mix',
        'unit' => 'bag',
        'current_stock' => 100,
        'reserved_stock' => 20,
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Repair Parc Ave',
        'status' => 'in_progress',
        'created_by' => $admin->id,
    ]);

    JobMaterial::query()->create([
        'repair_job_id' => $job->id,
        'material_id' => $material->id,
        'quantity_planned' => 15,
        'quantity_actual' => 15,
    ]);

    $job->update(['status' => 'completed']);

    $material->refresh();

    expect((float) $material->current_stock)->toBe(85.0)
        ->and((float) $material->reserved_stock)->toBe(5.0);
});

it('does not decrement stock again after completion if non-status fields are later updated', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $material = Material::query()->create([
        'sku' => 'MAT-302',
        'name' => 'Asphalt',
        'unit' => 'kg',
        'current_stock' => 50,
        'reserved_stock' => 10,
        'min_stock_alert' => 5,
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Repair Saint-Laurent',
        'status' => 'in_progress',
        'created_by' => $admin->id,
    ]);

    JobMaterial::query()->create([
        'repair_job_id' => $job->id,
        'material_id' => $material->id,
        'quantity_planned' => 10,
        'quantity_actual' => 10,
    ]);

    $job->update(['status' => 'completed']);
    $job->update(['title' => 'Repair Saint-Laurent Updated']);

    $material->refresh();

    expect((float) $material->current_stock)->toBe(40.0)
        ->and((float) $material->reserved_stock)->toBe(0.0);
});

it('sends low-stock notifications to active admin and manager when threshold is crossed', function () {
    Notification::fake();

    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $manager = User::factory()->create([
        'role_id' => Role::where('slug', 'manager')->value('id'),
        'is_active' => true,
    ]);

    $viewer = User::factory()->create([
        'role_id' => Role::where('slug', 'viewer')->value('id'),
        'is_active' => true,
    ]);

    $inactiveManager = User::factory()->create([
        'role_id' => Role::where('slug', 'manager')->value('id'),
        'is_active' => false,
    ]);

    $material = Material::query()->create([
        'sku' => 'MAT-303',
        'name' => 'Sealant',
        'unit' => 'tube',
        'current_stock' => 12,
        'reserved_stock' => 6,
        'min_stock_alert' => 10,
        'is_active' => true,
    ]);

    $job = RepairJob::query()->create([
        'title' => 'Repair Plateau',
        'status' => 'in_progress',
        'created_by' => $admin->id,
    ]);

    JobMaterial::query()->create([
        'repair_job_id' => $job->id,
        'material_id' => $material->id,
        'quantity_planned' => 5,
        'quantity_actual' => 5,
    ]);

    $job->update(['status' => 'completed']);

    Notification::assertSentTo($admin, LowStockMaterialAlertNotification::class);
    Notification::assertSentTo($manager, LowStockMaterialAlertNotification::class);
    Notification::assertNotSentTo($viewer, LowStockMaterialAlertNotification::class);
    Notification::assertNotSentTo($inactiveManager, LowStockMaterialAlertNotification::class);

    $material->refresh();

    expect((float) $material->current_stock)->toBe(7.0)
        ->and((float) $material->reserved_stock)->toBe(1.0);
});
