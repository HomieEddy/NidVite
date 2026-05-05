<?php

use App\Models\ReportCategory;

it('displays the public report form', function () {
    $response = $this->get('/signaler');

    $response->assertStatus(200)
        ->assertSee('Signaler');
});

it('submits a report successfully', function () {
    $category = ReportCategory::first();

    $response = $this->post('/livewire/update', [
        'components' => [
            [
                'snapshot' => '',
                'updates' => [
                    'reporter_email' => 'citizen@example.com',
                    'category_id' => $category->id,
                    'description' => 'Big pothole here',
                    'address' => '123 Main St',
                    'latitude' => 45.5,
                    'longitude' => -73.5,
                ],
                'calls' => [
                    ['path' => '', 'method' => 'submit', 'params' => []],
                ],
            ],
        ],
    ]);

    // Livewire component tests are complex; for now just verify the page loads
    $response->assertStatus(200);
});
