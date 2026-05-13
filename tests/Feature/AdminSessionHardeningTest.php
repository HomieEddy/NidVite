<?php

use App\Http\Middleware\EnforceAdminSessionTimeout;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    if (! Route::has('test.admin.timeout')) {
        Route::middleware(['web', EnforceAdminSessionTimeout::class])
            ->get('/test/admin-timeout', fn () => response('ok', 200))
            ->name('test.admin.timeout');
    }
});

it('uses expected admin auth config defaults', function () {
    expect(config('admin-auth.session_timeout_minutes'))->toBe(15);
    expect(config('admin-auth.max_concurrent_sessions'))->toBeInt();
});

it('forces re-authentication when admin has been idle for more than fifteen minutes', function () {
    config(['admin-auth.session_timeout_minutes' => 15]);

    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->withSession([
            'admin_last_activity' => now()->subMinutes(16)->getTimestamp(),
        ])
        ->get('/test/admin-timeout')
        ->assertRedirect(route('filament.admin.auth.login'));

    $this->assertGuest();
});

it('keeps admin authenticated and updates activity timestamp inside timeout window', function () {
    config(['admin-auth.session_timeout_minutes' => 15]);

    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $previousActivity = now()->subMinutes(5)->getTimestamp();

    $this->actingAs($admin)
        ->withSession([
            'admin_last_activity' => $previousActivity,
        ])
        ->get('/test/admin-timeout')
        ->assertOk();

    expect((int) session('admin_last_activity'))->toBeGreaterThan($previousActivity);
});

it('prunes oldest admin sessions when configured concurrent limit is exceeded', function () {
    config([
        'session.driver' => 'database',
        'admin-auth.max_concurrent_sessions' => 2,
    ]);

    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    DB::table('sessions')->insert([
        [
            'id' => 'session-oldest',
            'user_id' => $admin->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 100,
        ],
        [
            'id' => 'session-middle',
            'user_id' => $admin->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 200,
        ],
        [
            'id' => 'session-newest',
            'user_id' => $admin->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 300,
        ],
    ]);

    event(new Login('web', $admin, false));

    $sessionIds = DB::table('sessions')
        ->where('user_id', $admin->getKey())
        ->pluck('id')
        ->all();

    expect($sessionIds)->toContain('session-middle');
    expect($sessionIds)->toContain('session-newest');
    expect($sessionIds)->not->toContain('session-oldest');
});

it('does not prune sessions for non-admin users', function () {
    config([
        'session.driver' => 'database',
        'admin-auth.max_concurrent_sessions' => 1,
    ]);

    /** @var User $viewer */
    $viewer = User::factory()->create([
        'role_id' => Role::where('slug', 'viewer')->value('id'),
        'is_active' => true,
    ]);

    DB::table('sessions')->insert([
        [
            'id' => 'viewer-session-one',
            'user_id' => $viewer->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 100,
        ],
        [
            'id' => 'viewer-session-two',
            'user_id' => $viewer->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 200,
        ],
    ]);

    event(new Login('web', $viewer, false));

    $count = DB::table('sessions')
        ->where('user_id', $viewer->getKey())
        ->count();

    expect($count)->toBe(2);
});

it('does not prune admin sessions when session driver is not database', function () {
    config([
        'session.driver' => 'file',
        'admin-auth.max_concurrent_sessions' => 1,
    ]);

    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    DB::table('sessions')->insert([
        [
            'id' => 'admin-file-driver-one',
            'user_id' => $admin->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 100,
        ],
        [
            'id' => 'admin-file-driver-two',
            'user_id' => $admin->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 200,
        ],
    ]);

    event(new Login('web', $admin, false));

    $count = DB::table('sessions')
        ->where('user_id', $admin->getKey())
        ->count();

    expect($count)->toBe(2);
});

it('clamps invalid admin concurrent session limits to one session', function () {
    config([
        'session.driver' => 'database',
        'admin-auth.max_concurrent_sessions' => 0,
    ]);

    /** @var User $admin */
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    DB::table('sessions')->insert([
        [
            'id' => 'admin-clamp-old',
            'user_id' => $admin->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 100,
        ],
        [
            'id' => 'admin-clamp-new',
            'user_id' => $admin->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => 200,
        ],
    ]);

    event(new Login('web', $admin, false));

    $sessionIds = DB::table('sessions')
        ->where('user_id', $admin->getKey())
        ->pluck('id')
        ->all();

    expect($sessionIds)->toBe(['admin-clamp-new']);
});
