<?php

use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps base report columns when withCoordinates is used without explicit select', function () {
    $report = Report::factory()->create([
        'status' => 'received',
    ]);

    $report->setLocation(45.5019, -73.5674);

    $result = Report::query()
        ->whereKey($report->getKey())
        ->withCoordinates()
        ->first();

    expect($result)->not->toBeNull();
    expect($result?->getKey())->toBe($report->getKey());
    expect($result?->status)->toBe('received');
    expect((float) $result?->latitude)->toBe(45.5019);
    expect((float) $result?->longitude)->toBe(-73.5674);
});

it('adds coordinate aliases when withCoordinates is combined with explicit select', function () {
    $report = Report::factory()->create([
        'status' => 'received',
    ]);

    $report->setLocation(45.5123, -73.5591);

    $result = Report::query()
        ->select('id')
        ->whereKey($report->getKey())
        ->withCoordinates()
        ->first();

    expect($result)->not->toBeNull();
    expect($result?->getKey())->toBe($report->getKey());
    expect((float) $result?->latitude)->toBe(45.5123);
    expect((float) $result?->longitude)->toBe(-73.5591);
});
