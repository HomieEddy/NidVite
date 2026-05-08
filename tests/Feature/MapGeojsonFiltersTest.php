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

it('ignores invalid status filter and returns all valid non-rejected reports', function () {
    $verified = Report::factory()->create([
        'status' => 'verified',
        'neighborhood' => 'Mercier',
        'is_spam' => false,
    ]);
    $verified->setLocation(45.54, -73.54);

    $received = Report::factory()->create([
        'status' => 'received',
        'neighborhood' => 'Mercier',
        'is_spam' => false,
    ]);
    $received->setLocation(45.55, -73.53);

    $response = $this->getJson(route('api.reports.geojson', ['status' => 'invalid_status']));

    $response->assertOk();

    $features = $response->json('features');

    expect($features)->toHaveCount(2);
});

it('excludes spam and rejected reports regardless of filters', function () {
    $spam = Report::factory()->create([
        'status' => 'verified',
        'neighborhood' => 'Villeray',
        'is_spam' => true,
    ]);
    $spam->setLocation(45.56, -73.52);

    $rejected = Report::factory()->create([
        'status' => 'rejected',
        'neighborhood' => 'Villeray',
        'is_spam' => false,
    ]);
    $rejected->setLocation(45.57, -73.51);

    $valid = Report::factory()->create([
        'status' => 'verified',
        'neighborhood' => 'Villeray',
        'is_spam' => false,
    ]);
    $valid->setLocation(45.58, -73.50);

    $response = $this->getJson(route('api.reports.geojson'));

    $response->assertOk();

    $features = $response->json('features');

    expect($features)->toHaveCount(1)
        ->and($features[0]['properties']['status'])->toBe('verified');
});

it('applies combined status and neighborhood filters', function () {
    $match = Report::factory()->create([
        'status' => 'verified',
        'neighborhood' => 'Le Plateau-Mont-Royal',
        'is_spam' => false,
    ]);
    $match->setLocation(45.59, -73.49);

    $statusOnly = Report::factory()->create([
        'status' => 'verified',
        'neighborhood' => 'Saint-Laurent',
        'is_spam' => false,
    ]);
    $statusOnly->setLocation(45.60, -73.48);

    $neighborhoodOnly = Report::factory()->create([
        'status' => 'received',
        'neighborhood' => 'Le Plateau-Mont-Royal',
        'is_spam' => false,
    ]);
    $neighborhoodOnly->setLocation(45.61, -73.47);

    $response = $this->getJson(route('api.reports.geojson', [
        'status' => 'verified',
        'neighborhood' => 'plateau',
    ]));

    $response->assertOk();

    $features = $response->json('features');

    expect($features)->toHaveCount(1)
        ->and($features[0]['properties']['status'])->toBe('verified')
        ->and($features[0]['properties']['neighborhood'])->toBe('Le Plateau-Mont-Royal');
});
