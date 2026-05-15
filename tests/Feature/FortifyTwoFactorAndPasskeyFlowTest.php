<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('keeps two-factor management endpoints behind authentication', function () {
    $enableResponse = $this->post(route('two-factor.enable'));
    $disableResponse = $this->delete(route('two-factor.disable'));

    expect($enableResponse->getStatusCode())->toBe(302);
    expect($disableResponse->getStatusCode())->toBe(302);
});

it('allows authenticated users to enable and disable two-factor authentication', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $enableResponse = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'));

    expect(in_array($enableResponse->getStatusCode(), [200, 201, 204, 302], true))->toBeTrue();
    expect($admin->fresh()->two_factor_secret)->not->toBeNull();

    $disableResponse = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('two-factor.disable'));

    expect(in_array($disableResponse->getStatusCode(), [200, 201, 204, 302], true))->toBeTrue();
    expect($admin->fresh()->two_factor_secret)->toBeNull();
});

it('returns passkey registration options for authenticated users', function () {
    $admin = User::factory()->create([
        'role_id' => Role::where('slug', 'admin')->value('id'),
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('passkey.registration-options'));

    $response->assertOk();

    $payload = $response->json();
    $challenge = data_get($payload, 'challenge')
        ?? data_get($payload, 'publicKey.challenge')
        ?? data_get($payload, 'options.challenge');

    expect(is_array($payload))->toBeTrue();
    expect($challenge)->not->toBeNull();
});
