<?php

use App\Models\Report;
use Illuminate\Support\Facades\Artisan;

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

    expect(Report::query()->count())->toBe(0);
});

it('runs fresh migrate and core seed before demo seed when fresh option is used', function () {
    app()->detectEnvironment(fn () => 'testing');

    $this->artisan('ops:seed-staging-demo --fresh')
        ->expectsOutputToContain('Staging demo seed completed.')
        ->assertSuccessful();

    expect(Report::query()->count())->toBe(0);
});
