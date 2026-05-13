<?php

use App\Models\Report;
use App\Models\User;
use Database\Seeders\StagingDemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

it('registers the staging demo seed command', function () {
    expect(Artisan::all())->toHaveKey('ops:seed-staging-demo');
});

it('fails staging demo seed command outside staging or testing environment', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->artisan('ops:seed-staging-demo')
        ->expectsOutputToContain('This command is restricted to staging/testing environments.')
        ->assertExitCode(1);
});

it('runs demo seed command in testing environment', function () {
    app()->detectEnvironment(fn () => 'testing');

    $this->artisan('ops:seed-staging-demo')
        ->expectsOutputToContain('Staging demo seed completed.')
        ->assertSuccessful();

    expect(Report::withTrashed()->count())->toBe(0);
});

it('runs fresh migrate and core seed before demo seed when fresh option is used', function () {
    app()->detectEnvironment(fn () => 'testing');

    $this->artisan('ops:seed-staging-demo --fresh')
        ->expectsOutputToContain('Staging demo seed completed.')
        ->assertSuccessful();

    expect(Report::withTrashed()->count())->toBe(0);
});

it('prevents direct staging demo seeder execution outside staging and testing', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(fn () => app(StagingDemoSeeder::class)->run())
        ->toThrow(\RuntimeException::class, 'StagingDemoSeeder can only run in staging/testing environments.');
});

it('uses configured staging demo password instead of hardcoded shared credentials', function () {
    app()->detectEnvironment(fn () => 'testing');
    config()->set('admin-auth.staging_demo_seed_password', 'seeded-test-password');

    app(StagingDemoSeeder::class)->run();

    $admin = User::query()->where('email', 'admin@nidvite.ca')->firstOrFail();
    $manager = User::query()->where('email', 'marquize.7@nidvite.ca')->firstOrFail();

    expect(Hash::check('seeded-test-password', (string) $admin->password))->toBeTrue()
        ->and(Hash::check('seeded-test-password', (string) $manager->password))->toBeTrue();
});
