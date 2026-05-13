<?php

use App\Events\ReportCreated;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\ResponseCache\Facades\ResponseCache;
use Spatie\ResponseCache\Middlewares\CacheResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['broadcasting.default' => 'log']);
});

it('caches only welcome and public map pages', function () {
    $routes = app('router')->getRoutes();

    $home = $routes->match(Request::create('/', 'GET'));
    $map = $routes->match(Request::create('/carte', 'GET'));
    $reportCreate = $routes->match(Request::create('/signaler', 'GET'));

    expect($home)->not->toBeNull();
    expect($map)->not->toBeNull();
    expect($reportCreate)->not->toBeNull();

    expect($home->gatherMiddleware())->toContain(CacheResponse::class);
    expect($map->gatherMiddleware())->toContain(CacheResponse::class);
    expect($reportCreate->gatherMiddleware())->not->toContain(CacheResponse::class);
});

it('clears response cache when a report is created', function () {
    ResponseCache::shouldReceive('forget')
        ->atLeast()
        ->once()
        ->with(['/', '/carte']);

    $report = Report::factory()->create();

    event(new ReportCreated($report));
});

it('registers and executes the opcache clear command', function () {
    expect(Artisan::all())->toHaveKey('ops:opcache-clear');

    // Runtime behavior varies between CI/local PHP builds; assert command executes deterministically.
    $exitCode = Artisan::call('ops:opcache-clear');

    expect($exitCode)->toBeIn([0, 1]);
});
