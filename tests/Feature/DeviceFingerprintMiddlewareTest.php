<?php

use App\Http\Middleware\GenerateDeviceFingerprint;
use App\Models\Report;
use App\Models\ReportCategory;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);

    if (! Route::has('test.fingerprint.show')) {
        Route::middleware(GenerateDeviceFingerprint::class)->get('/_test/fingerprint', function (Request $request) {
            return response()->json([
                'fingerprint' => $request->attributes->get('device_fingerprint_hash'),
            ]);
        })->name('test.fingerprint.show');
    }

    if (! Route::has('test.fingerprint.persist')) {
        Route::middleware(GenerateDeviceFingerprint::class)->post('/_test/fingerprint/persist', function (Request $request) {
            $categoryId = ReportCategory::where('slug', 'pothole')->value('id');

            $report = Report::create([
                'reporter_email' => 'citizen@example.com',
                'preferred_locale' => 'fr',
                'category_id' => $categoryId,
                'description' => 'Fingerprint persistence test',
                'address' => '123 Test Street',
            ]);

            return response()->json([
                'id' => $report->id,
                'fingerprint' => $report->device_fingerprint_hash,
            ]);
        })->name('test.fingerprint.persist');
    }
});

it('generates deterministic fingerprint hash for identical headers', function () {
    $headers = [
        'User-Agent' => 'Mozilla/5.0 Test Browser',
        'Accept-Language' => 'fr-CA,fr;q=0.9',
        'Sec-CH-UA' => '"Chromium";v="125"',
    ];

    $first = $this->withHeaders($headers)->get('/_test/fingerprint');
    $second = $this->withHeaders($headers)->get('/_test/fingerprint');

    $first->assertOk();
    $second->assertOk();

    $firstHash = $first->json('fingerprint');
    $secondHash = $second->json('fingerprint');

    expect($firstHash)->toBeString()
        ->and(strlen($firstHash))->toBe(64)
        ->and($secondHash)->toBe($firstHash);
});

it('stores device fingerprint hash on report creation through request context', function () {
    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 Persist Test',
        'Accept-Language' => 'en-US',
    ])->post('/_test/fingerprint/persist');

    $response->assertOk();

    $fingerprint = $response->json('fingerprint');

    expect($fingerprint)->toBeString()
        ->and(strlen($fingerprint))->toBe(64);

    $this->assertDatabaseHas('reports', [
        'id' => $response->json('id'),
        'device_fingerprint_hash' => $fingerprint,
    ]);
});

it('keeps fingerprint null for non-http model creation paths', function () {
    $report = Report::factory()->create();

    expect($report->device_fingerprint_hash)->toBeNull();
});

