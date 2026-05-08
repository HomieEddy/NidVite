<?php

use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Material;
use App\Models\MaterialPurchase;
use App\Models\ReportCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function roleId(string $slug): int
{
    return (int) Role::query()->where('slug', $slug)->value('id');
}

function userWithRole(string $slug): User
{
    return User::factory()->create(['role_id' => roleId($slug)]);
}

describe('RBAC-01 vendor policy matrix', function () {
    it('allows admin and manager full vendor crud', function (string $roleSlug) {
        $user = userWithRole($roleSlug);
        $vendor = Vendor::query()->create(['name' => 'Test Vendor']);

        expect($user->can('viewAny', Vendor::class))->toBeTrue()
            ->and($user->can('view', $vendor))->toBeTrue()
            ->and($user->can('create', Vendor::class))->toBeTrue()
            ->and($user->can('update', $vendor))->toBeTrue()
            ->and($user->can('delete', $vendor))->toBeTrue();
    })->with(['admin', 'manager']);

    it('allows accountant view-only vendor access', function () {
        $user = userWithRole('accountant');
        $vendor = Vendor::query()->create(['name' => 'Test Vendor']);

        expect($user->can('viewAny', Vendor::class))->toBeTrue()
            ->and($user->can('view', $vendor))->toBeTrue()
            ->and($user->can('create', Vendor::class))->toBeFalse()
            ->and($user->can('update', $vendor))->toBeFalse()
            ->and($user->can('delete', $vendor))->toBeFalse();
    });

    it('denies service worker and viewer all vendor operations', function (string $roleSlug) {
        $user = userWithRole($roleSlug);
        $vendor = Vendor::query()->create(['name' => 'Test Vendor']);

        expect($user->can('viewAny', Vendor::class))->toBeFalse()
            ->and($user->can('view', $vendor))->toBeFalse()
            ->and($user->can('create', Vendor::class))->toBeFalse()
            ->and($user->can('update', $vendor))->toBeFalse()
            ->and($user->can('delete', $vendor))->toBeFalse();
    })->with(['service_worker', 'viewer']);

    it('blocks unauthorized roles from vendor create route', function (string $roleSlug) {
        $user = userWithRole($roleSlug);
        $this->actingAs($user);

        $this->get(VendorResource::getUrl('create'))->assertForbidden();
    })->with(['accountant', 'service_worker', 'viewer'])->group('vendor_create_action');
});

describe('RBAC-02 material policy matrix', function () {
    it('allows expected material inventory operations by role', function (string $roleSlug, array $expected) {
        $user = userWithRole($roleSlug);
        $material = Material::query()->create([
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Asphalt',
            'unit' => 'kg',
        ]);

        expect($user->can('viewAny', Material::class))->toBe($expected['viewAny'])
            ->and($user->can('view', $material))->toBe($expected['view'])
            ->and($user->can('create', Material::class))->toBe($expected['create'])
            ->and($user->can('update', $material))->toBe($expected['update'])
            ->and($user->can('delete', $material))->toBe($expected['delete']);
    })->with([
        'admin' => ['admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true]],
        'manager' => ['manager', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true]],
        'service_worker' => ['service_worker', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => false]],
        'accountant' => ['accountant', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false]],
        'viewer' => ['viewer', ['viewAny' => false, 'view' => false, 'create' => false, 'update' => false, 'delete' => false]],
    ]);
});

describe('RBAC-03 material purchase policy matrix', function () {
    it('enforces purchase logging permissions by role', function (string $roleSlug, array $expected) {
        $user = userWithRole($roleSlug);
        $material = Material::query()->create([
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Asphalt',
            'unit' => 'kg',
        ]);
        $materialPurchase = MaterialPurchase::query()->create([
            'material_id' => $material->id,
            'quantity' => 10,
            'unit_cost' => 5,
            'subtotal' => 50,
            'tax_rate' => 0.14975,
            'tax_amount' => 7.49,
            'total' => 57.49,
            'vendor' => 'Road Supply',
            'created_by' => userWithRole('admin')->id,
        ]);

        expect($user->can('viewAny', MaterialPurchase::class))->toBe($expected['viewAny'])
            ->and($user->can('view', $materialPurchase))->toBe($expected['view'])
            ->and($user->can('create', MaterialPurchase::class))->toBe($expected['create'])
            ->and($user->can('update', $materialPurchase))->toBe($expected['update'])
            ->and($user->can('delete', $materialPurchase))->toBe($expected['delete']);
    })->with([
        'admin' => ['admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true]],
        'manager' => ['manager', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true]],
        'service_worker' => ['service_worker', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false]],
        'accountant' => ['accountant', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => false]],
        'viewer' => ['viewer', ['viewAny' => false, 'view' => false, 'create' => false, 'update' => false, 'delete' => false]],
    ]);
});

describe('RBAC-04 report category policy matrix', function () {
    it('allows only admin and manager to manage report categories', function (string $roleSlug, bool $canManage) {
        $user = userWithRole($roleSlug);
        $reportCategory = ReportCategory::factory()->create();

        expect($user->can('viewAny', ReportCategory::class))->toBeTrue()
            ->and($user->can('view', $reportCategory))->toBeTrue()
            ->and($user->can('create', ReportCategory::class))->toBe($canManage)
            ->and($user->can('update', $reportCategory))->toBe($canManage)
            ->and($user->can('delete', $reportCategory))->toBe($canManage);
    })->with([
        ['admin', true],
        ['manager', true],
        ['service_worker', false],
        ['accountant', false],
        ['viewer', false],
    ]);
});
