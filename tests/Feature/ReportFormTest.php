<?php

use App\Models\ReportCategory;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
});

it('displays the public report form', function () {
    $response = $this->get('/signaler');

    $response->assertStatus(200)
        ->assertSee('Signaler');
});

it('has active report categories for the form', function () {
    $categories = ReportCategory::where('is_active', true)->get();

    expect($categories->count())->toBeGreaterThan(0);
});
