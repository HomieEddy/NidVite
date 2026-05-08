<?php

use App\Filament\Resources\Materials\Pages\ListMaterials;
use App\Models\Material;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('allows admin to open material inventory list in filament', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    expect($admin->can('viewAny', Material::class))->toBeTrue();

    Livewire::test(ListMaterials::class)->assertSuccessful();
});

it('shows current reserved and available stock distinctly in inventory table', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $material = Material::query()->create([
        'sku' => 'MAT-100',
        'name' => 'Cold Patch',
        'unit' => 'bag',
        'current_stock' => 120,
        'reserved_stock' => 35,
        'min_stock_alert' => 20,
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListMaterials::class)
        ->assertCanSeeTableRecords([$material])
        ->assertSee('120')
        ->assertSee('35')
        ->assertSee('85');
});

it('computes available stock as current minus reserved', function () {
    $material = Material::query()->create([
        'sku' => 'MAT-200',
        'name' => 'Asphalt Mix',
        'unit' => 'kg',
        'current_stock' => 40,
        'reserved_stock' => 12.5,
        'min_stock_alert' => 10,
        'is_active' => true,
    ]);

    expect($material->available_stock)->toBe(27.5);
});
