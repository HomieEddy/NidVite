<?php

use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\Role;
use App\Models\User;

it('creates a user with auto-generated uuid', function () {
    $user = User::create([
        'name' => 'Test',
        'email' => 'test-'.uniqid().'@example.com',
        'password' => bcrypt('secret'),
        'role_id' => Role::first()->id,
    ]);

    expect($user->uuid)->not->toBeNull()
        ->and($user->role)->not->toBeNull();
});

it('creates a report with auto-generated uuid', function () {
    $report = Report::create([
        'reporter_email' => 'citizen@example.com',
        'status' => 'pending',
        'ip_address_hash' => hash('sha256', '127.0.0.1'),
        'user_agent_hash' => hash('sha256', 'Mozilla'),
    ]);

    expect($report->uuid)->not->toBeNull()
        ->and($report->status)->toBe('pending');
});

it('sets postgis location on report', function () {
    $report = Report::create([
        'reporter_email' => 'citizen@example.com',
        'status' => 'pending',
        'ip_address_hash' => hash('sha256', '127.0.0.1'),
        'user_agent_hash' => hash('sha256', 'Mozilla'),
    ]);

    $report->setLocation(45.5019, -73.5674);

    $location = DB::selectOne('SELECT ST_X(location::geometry) as lng, ST_Y(location::geometry) as lat FROM reports WHERE id = ?', [$report->id]);

    expect((float) $location->lat)->toBeCloseTo(45.5019, 0.0001)
        ->and((float) $location->lng)->toBeCloseTo(-73.5674, 0.0001);
});

it('filters reports by status', function () {
    Report::create([
        'reporter_email' => 'a@example.com',
        'status' => 'pending',
        'ip_address_hash' => hash('sha256', '127.0.0.1'),
        'user_agent_hash' => hash('sha256', 'Mozilla'),
    ]);

    Report::create([
        'reporter_email' => 'b@example.com',
        'status' => 'repaired',
        'ip_address_hash' => hash('sha256', '127.0.0.1'),
        'user_agent_hash' => hash('sha256', 'Mozilla'),
    ]);

    expect(Report::status('pending')->count())->toBe(1);
});

it('returns active report categories', function () {
    expect(ReportCategory::where('is_active', true)->count())->toBeGreaterThan(0);
});

it('has admin role seeded', function () {
    expect(Role::where('slug', 'admin')->exists())->toBeTrue();
});
