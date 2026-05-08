<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    Schema::connection('sqlite')->create('reports', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('reporter_email')->nullable();
        $table->string('preferred_locale', 5)->default('fr');
        $table->string('status')->default('received');
        $table->string('priority')->default('normal');
        $table->unsignedSmallInteger('category_id')->nullable();
        $table->text('description')->nullable();
        $table->string('address')->nullable();
        $table->string('neighborhood')->nullable();
        $table->string('borough')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->string('ip_address_raw', 45)->nullable();
        $table->timestamp('archived_at')->nullable();
        $table->string('archive_path', 512)->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Config::set('retention.ip_purge_days', 30);
    Config::set('retention.report_archive_days', 730);
    Config::set('retention.cold_storage_disk', 'r2-cold');
    Config::set('retention.cold_storage_prefix', 'cold/reports');

    Storage::fake('r2-cold');
});

afterEach(function () {
    Schema::connection('sqlite')->dropIfExists('reports');
});

it('purges stale raw ips and archives reports older than retention threshold', function () {
    DB::table('reports')->insert([
        [
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'reporter_email' => 'old@example.com',
            'ip_address_raw' => '198.51.100.10',
            'created_at' => now()->subDays(800),
            'updated_at' => now()->subDays(800),
        ],
        [
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'reporter_email' => 'recent@example.com',
            'ip_address_raw' => '198.51.100.11',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ],
    ]);

    Artisan::call('reports:run-retention');

    $old = DB::table('reports')->where('uuid', '11111111-1111-1111-1111-111111111111')->first();
    $recent = DB::table('reports')->where('uuid', '22222222-2222-2222-2222-222222222222')->first();

    expect($old)->not->toBeNull();
    expect($old->ip_address_raw)->toBeNull();
    expect($old->archived_at)->not->toBeNull();
    expect($old->archive_path)->not->toBeNull();
    expect($old->deleted_at)->toBeNull();

    expect(Storage::disk('r2-cold')->exists($old->archive_path))->toBeTrue();

    expect($recent)->not->toBeNull();
    expect($recent->ip_address_raw)->toBe('198.51.100.11');
    expect($recent->archived_at)->toBeNull();
    expect($recent->deleted_at)->toBeNull();
});
