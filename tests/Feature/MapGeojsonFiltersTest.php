<?php

use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters geojson by status when requested', function () {
    $received = Report::factory()->create([
        'status' => 'received',
        'neighborhood' => 'Plateau-Mont-Royal',
        'is_spam' => false,
    ]);
    $received->setLocation(45.50, -73.57);

    $repaired = Report::factory()->create([
        'status' => 'repaired',
        'neighborhood' => 'Rosemont',
        'is_spam' => false,
    ]);
    $repaired->setLocation(45.51, -73.58);

    $response = $this->getJson(route('api.reports.geojson', ['status' => 'repaired']));

    $response->assertOk();

    $features = $response->json('features');

    expect($features)->toHaveCount(1)
        ->and($features[0]['properties']['status'])->toBe('repaired');
});

it('filters geojson by neighborhood partial match', function () {
    $plateau = Report::factory()->create([
        'status' => 'verified',
        'neighborhood' => 'Le Plateau-Mont-Royal',
        'is_spam' => false,
    ]);
    $plateau->setLocation(45.52, -73.56);

    $ahuntsic = Report::factory()->create([
        'status' => 'verified',
        'neighborhood' => 'Ahuntsic',
        'is_spam' => false,
    ]);
    $ahuntsic->setLocation(45.53, -73.55);

    $response = $this->getJson(route('api.reports.geojson', ['neighborhood' => 'plateau']));

    $response->assertOk();

    $features = $response->json('features');

    expect($features)->toHaveCount(1)
        ->and($features[0]['properties']['neighborhood'])->toBe('Le Plateau-Mont-Royal');
});
