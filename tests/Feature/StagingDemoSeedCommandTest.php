<?php

use App\Models\Material;
use App\Models\RepairJob;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\StagingDemoSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

    expect(Report::withTrashed()->count())->toBe(250)
        ->and(Report::query()->where('status', 'repaired')->count())->toBe(188)
        ->and(Report::query()->where('status', 'verified')->count())->toBe(37)
        ->and(Report::query()->where('status', 'scheduled')->count())->toBe(25)
        ->and(RepairJob::query()->where('status', 'completed')->count())->toBe(188)
        ->and(RepairJob::query()->where('status', 'planned')->count())->toBe(25)
        ->and(Vendor::query()->count())->toBe(3)
        ->and(Material::query()->count())->toBe(3)
        ->and(ReportCategory::query()->pluck('slug')->all())->toBe(['pothole'])
        ->and(DB::table('montreal_roads')->where('source', 'mtl_geobase')->count())->toBeGreaterThan(0);

    $maxDistance = DB::selectOne(
        'SELECT MAX(ST_Distance(r.location, mr.geom::geography)) AS max_distance
         FROM reports r
         CROSS JOIN LATERAL (
            SELECT geom
            FROM montreal_roads
            ORDER BY geom::geography <-> r.location
            LIMIT 1
         ) mr'
    );

    expect((float) $maxDistance->max_distance)->toBeLessThan(2.0);
});

it('runs fresh migrate and core seed before demo seed when fresh option is used', function () {
    app()->detectEnvironment(fn () => 'testing');

    $this->artisan('ops:seed-staging-demo --fresh')
        ->expectsOutputToContain('Staging demo seed completed.')
        ->assertSuccessful();

    expect(Report::withTrashed()->count())->toBe(250);
});

it('prevents direct staging demo seeder execution outside staging and testing', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(fn () => app(StagingDemoSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'StagingDemoSeeder can only run in staging/testing environments.');
});

it('requires configured staging demo password in staging environment', function () {
    app()->detectEnvironment(fn () => 'staging');
    config()->set('admin-auth.staging_demo_seed_password', '');

    expect(fn () => app(StagingDemoSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'staging_demo_seed_password must be configured for staging environment');
});

it('prevents direct test data seeder execution in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(TestDataSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'TestDataSeeder may only run in local, testing, or staging environments.');
});

it('requires Montreal road geometry before direct test data seeding', function () {
    app()->detectEnvironment(fn () => 'testing');
    DB::table('montreal_roads')->delete();

    expect(fn () => app(TestDataSeeder::class)->run())
        ->toThrow(RuntimeException::class, 'Montreal roads must be seeded from database/geo/mtl_geobase.json before TestDataSeeder runs.');
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
