<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('registers the staging readiness command', function () {
    expect(Artisan::all())->toHaveKey('ops:check-staging-readiness');
});

it('passes staging readiness checks with expected baseline config', function () {
    Config::set('queue.default', 'redis');
    Config::set('cache.default', 'redis');
    Config::set('filesystems.default', 'r2');
    Config::set('mail.default', 'log');

    DB::shouldReceive('connection->getDriverName')->once()->andReturn('pgsql');
    DB::shouldReceive('selectOne')->once()->andReturn((object) ['installed' => true]);

    $this->artisan('ops:check-staging-readiness')->assertSuccessful();
});

it('fails staging readiness checks when queue driver is not redis', function () {
    Config::set('queue.default', 'database');
    Config::set('cache.default', 'redis');
    Config::set('filesystems.default', 'r2');
    Config::set('mail.default', 'log');

    DB::shouldReceive('connection->getDriverName')->once()->andReturn('pgsql');
    DB::shouldReceive('selectOne')->once()->andReturn((object) ['installed' => true]);

    $this->artisan('ops:check-staging-readiness')
        ->expectsOutputToContain('QUEUE_CONNECTION must resolve to redis.')
        ->assertExitCode(1);
});

it('fails staging readiness checks when mailer is unsafe for staging', function () {
    Config::set('queue.default', 'redis');
    Config::set('cache.default', 'redis');
    Config::set('filesystems.default', 'r2');
    Config::set('mail.default', 'smtp');

    DB::shouldReceive('connection->getDriverName')->once()->andReturn('pgsql');
    DB::shouldReceive('selectOne')->once()->andReturn((object) ['installed' => true]);

    $this->artisan('ops:check-staging-readiness')
        ->expectsOutputToContain('MAIL_MAILER must be log or array in staging to prevent outbound delivery.')
        ->assertExitCode(1);
});
