<?php

use App\Filament\Resources\Reports\Pages\ListReports;
use App\Models\Report;
use App\Models\ReportSavedView;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReportCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);
});

it('stores and loads saved report views for current user only', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    /** @var User $otherAdmin */
    $otherAdmin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $otherView = ReportSavedView::query()->create([
        'user_id' => $otherAdmin->id,
        'name' => 'Other Admin View',
        'filters' => ['status' => ['values' => ['verified']]],
        'sort_column' => 'created_at',
        'sort_direction' => 'desc',
        'search' => 'other',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListReports::class)
        ->set('tableFilters', ['status' => ['values' => ['received']]])
        ->sortTable('priority', 'asc')
        ->set('tableSearch', 'triage')
        ->callAction('save_view', ['name' => 'My Queue']);

    $saved = ReportSavedView::query()
        ->where('user_id', $admin->id)
        ->where('name', 'My Queue')
        ->first();

    expect($saved)->not->toBeNull()
        ->and($saved?->filters)->toMatchArray(['status' => ['values' => ['received']]])
        ->and($saved?->sort_column)->toBe('priority')
        ->and($saved?->sort_direction)->toBe('asc')
        ->and($saved?->search)->toBe('triage');

    Livewire::test(ListReports::class)
        ->set('tableFilters', ['status' => ['values' => ['verified']]])
        ->set('tableSearch', 'override')
        ->callAction('load_view', ['view_id' => $saved->id])
        ->assertSet('tableFilters', ['status' => ['values' => ['received']]])
        ->assertSet('tableSearch', 'triage')
        ->callAction('delete_view', ['view_id' => $otherView->id]);

    expect(ReportSavedView::query()->whereKey($otherView->id)->exists())->toBeTrue();
});

it('bulk duplicate-close processes valid rows and blocks invalid transitions', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    $processable = Report::factory()->create(['status' => 'received']);
    $blocked = Report::factory()->create(['status' => 'repaired']);

    Livewire::test(ListReports::class)
        ->callTableBulkAction('duplicate_close', [$processable, $blocked], [
            'reason' => 'Duplicate of existing ticket',
        ]);

    expect($processable->fresh()->status)->toBe('rejected')
        ->and($processable->fresh()->rejection_reason)->toBe('Duplicate of existing ticket')
        ->and($blocked->fresh()->status)->toBe('repaired');

    $parent = Activity::query()
        ->where('log_name', 'report_batch')
        ->where('description', 'Batch duplicate-close started')
        ->latest('id')
        ->first();

    expect($parent)->not->toBeNull()
        ->and($parent?->properties['processed_count'])->toBe(1)
        ->and($parent?->properties['blocked_count'])->toBe(1);

    $childLogs = Activity::query()
        ->where('log_name', 'report_batch_item')
        ->where('description', 'Batch duplicate-close blocked')
        ->where('subject_id', $blocked->id)
        ->count();

    expect($childLogs)->toBeGreaterThan(0);
});

it('bulk assign-contractor sets contractor and only transitions verified reports', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    /** @var User $contractor */
    $contractor = User::factory()->create([
        'role_id' => Role::where('slug', 'service_worker')->value('id'),
        'is_active' => true,
    ]);

    $verified = Report::factory()->create(['status' => 'verified']);
    $received = Report::factory()->create(['status' => 'received']);

    Livewire::test(ListReports::class)
        ->callTableBulkAction('assign_contractor', [$verified, $received], [
            'contractor_user_id' => $contractor->id,
        ]);

    expect($verified->fresh()->status)->toBe('scheduled')
        ->and($verified->fresh()->contractor_user_id)->toBe($contractor->id)
        ->and($received->fresh()->status)->toBe('received')
        ->and($received->fresh()->contractor_user_id)->toBeNull();

    $parent = Activity::query()
        ->where('log_name', 'report_batch')
        ->where('description', 'Batch assign-contractor started')
        ->latest('id')
        ->first();

    expect($parent)->not->toBeNull()
        ->and($parent?->properties['processed_count'])->toBe(1)
        ->and($parent?->properties['blocked_count'])->toBe(1);
});

it('bulk request-more-info appends note and blocks terminal rows', function () {
    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    $active = Report::factory()->create([
        'status' => 'verified',
        'admin_notes' => 'Existing note',
    ]);

    $terminal = Report::factory()->create(['status' => 'rejected']);

    Livewire::test(ListReports::class)
        ->callTableBulkAction('request_more_info', [$active, $terminal], [
            'note' => 'Please confirm nearest intersection',
        ]);

    expect($active->fresh()->admin_notes)->toContain('REQUEST_MORE_INFO: Please confirm nearest intersection')
        ->and($terminal->fresh()->admin_notes)->toBeNull();

    $parent = Activity::query()
        ->where('log_name', 'report_batch')
        ->where('description', 'Batch request-more-info started')
        ->latest('id')
        ->first();

    expect($parent)->not->toBeNull()
        ->and($parent?->properties['processed_count'])->toBe(1)
        ->and($parent?->properties['blocked_count'])->toBe(1);
});
