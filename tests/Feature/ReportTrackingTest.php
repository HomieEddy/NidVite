<?php

use App\Models\Report;
use App\Models\ReportFollower;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
});

it('displays a report tracking page by public tracking id', function () {
    $report = Report::factory()->create([
        'status' => 'in_progress',
        'address' => '123 Rue Saint-Catherine, Montréal',
    ]);

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee($report->public_tracking_id)
        ->assertSee('En cours')
        ->assertSee('123 Rue Saint-Catherine, Montréal');
});

it('returns 404 for invalid tracking id', function () {
    $this->get('/suivi/invalid-tracking-id')->assertStatus(404);
});

it('shows correct timeline for received report', function () {
    $report = Report::factory()->create(['status' => 'received']);

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee('Reçu')
        ->assertSee('Statut actuel');
});

it('shows correct timeline for repaired report', function () {
    $report = Report::factory()->create(['status' => 'repaired']);

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee('Réparé')
        ->assertSee('Statut actuel');
});

it('shows rejection message for rejected report', function () {
    $report = Report::factory()->create([
        'status' => 'rejected',
        'rejection_reason' => 'Out of service area',
    ]);

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee('Signalement rejeté')
        ->assertSee('Out of service area');
});

it('shows category label when present', function () {
    $report = Report::factory()->create();

    $response = $this->get("/suivi/{$report->public_tracking_id}");

    $response->assertStatus(200)
        ->assertSee($report->category->label_fr);
});

it('renders eta hint and qr card on tracking page', function () {
    $report = Report::factory()->create([
        'status' => 'verified',
        'borough' => 'Ville-Marie',
    ]);

    $response = $this->get(route('report.tracking', ['trackingId' => $report->public_tracking_id]));

    $response->assertOk()
        ->assertSee('data-testid="tracking-eta-hint"', false)
        ->assertSee('data-testid="tracking-qr-card"', false)
        ->assertSee(route('report.tracking', ['trackingId' => $report->public_tracking_id]));
});

it('returns tracking lookup location as lat/lng object when report has coordinates', function () {
    $report = Report::factory()->create([
        'status' => 'verified',
    ]);
    $report->setLocation(45.508, -73.561);

    $response = $this->getJson(route('api.reports.lookup', ['trackingId' => $report->public_tracking_id]));

    $response->assertOk()
        ->assertJsonPath('location.lat', 45.508)
        ->assertJsonPath('location.lng', -73.561);
});

it('returns null location in tracking lookup when report has no coordinates', function () {
    $report = Report::factory()->create([
        'status' => 'verified',
    ]);

    $response = $this->getJson(route('api.reports.lookup', ['trackingId' => $report->public_tracking_id]));

    $response->assertOk()
        ->assertJsonPath('location', null);
});

it('returns eta hint in tracking lookup payload', function () {
    $report = Report::factory()->create([
        'status' => 'scheduled',
        'borough' => 'Ville-Marie',
    ]);

    $response = $this->getJson(route('api.reports.lookup', ['trackingId' => $report->public_tracking_id]));

    $response->assertOk()
        ->assertJsonPath('eta_hint.zone_bucket', 'central')
        ->assertJsonPath('eta_hint.days_min', 1);
});

it('returns duplicate nudge when an open nearby report exists in 30-day window', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'created_at' => now()->subDays(3),
    ]);
    $report->setLocation(45.5017, -73.5673);

    $response = $this->getJson(route('api.reports.duplicate-hint', [
        'latitude' => 45.5018,
        'longitude' => -73.5674,
    ]));

    $response->assertOk()
        ->assertJsonPath('has_duplicate_nudge', true)
        ->assertJsonPath('report.tracking_id', $report->public_tracking_id);
});

it('does not return duplicate nudge for reports outside 30-day window', function () {
    $report = Report::factory()->create([
        'status' => 'verified',
        'created_at' => now()->subDays(40),
    ]);
    $report->setLocation(45.5017, -73.5673);

    $response = $this->getJson(route('api.reports.duplicate-hint', [
        'latitude' => 45.5017,
        'longitude' => -73.5673,
    ]));

    $response->assertOk()
        ->assertJsonPath('has_duplicate_nudge', false);
});

it('updates notification preference from tracking page', function () {
    $report = Report::factory()->create([
        'notification_preference' => 'all',
    ]);

    $response = $this->post(route('report.tracking.preference.update', ['trackingId' => $report->public_tracking_id]), [
        'notification_preference' => 'resolved',
    ]);

    $response->assertRedirect(route('report.tracking', ['trackingId' => $report->public_tracking_id]));

    expect($report->fresh()->notification_preference)->toBe('resolved');
});

it('deduplicates report followers by report and email', function () {
    $report = Report::factory()->create();
    config()->set('tracking_experience.followers.retention_days', 30);

    $first = $this->post(route('report.followers.store', ['trackingId' => $report->public_tracking_id]), [
        'email' => 'follow@example.com',
    ]);

    $first->assertRedirect(route('report.tracking', ['trackingId' => $report->public_tracking_id]));

    $second = $this->post(route('report.followers.store', ['trackingId' => $report->public_tracking_id]), [
        'email' => 'follow@example.com',
    ]);

    $second->assertRedirect(route('report.tracking', ['trackingId' => $report->public_tracking_id]));

    expect(ReportFollower::query()->where('report_id', $report->id)->where('email', 'follow@example.com')->count())->toBe(1);

    $follower = ReportFollower::query()->where('report_id', $report->id)->where('email', 'follow@example.com')->first();
    expect($follower)->not->toBeNull();
    expect($follower->expires_at)->not->toBeNull();
    expect($follower->expires_at?->greaterThan(now()->addDays(29)))->toBeTrue();
    expect($follower->expires_at?->lessThanOrEqualTo(now()->addDays(30)->addMinute()))->toBeTrue();
});

it('unsubscribes follower with signed link', function () {
    $report = Report::factory()->create();

    $follower = $report->followers()->create([
        'email' => 'follow@example.com',
        'preferred_locale' => 'fr',
        'is_active' => true,
    ]);

    $url = URL::temporarySignedRoute('report.followers.unsubscribe', now()->addMinutes(10), [
        'trackingId' => $report->public_tracking_id,
        'follower' => $follower->id,
    ]);

    $response = $this->get($url);

    $response->assertRedirect(route('report.tracking', ['trackingId' => $report->public_tracking_id]));

    $follower->refresh();
    expect($follower->is_active)->toBeFalse();
    expect($follower->unsubscribed_at)->not->toBeNull();
});

it('rejects tampered unsubscribe signed links', function () {
    $report = Report::factory()->create();

    $follower = $report->followers()->create([
        'email' => 'follow@example.com',
        'preferred_locale' => 'fr',
        'is_active' => true,
    ]);

    $url = URL::temporarySignedRoute('report.followers.unsubscribe', now()->addMinutes(10), [
        'trackingId' => $report->public_tracking_id,
        'follower' => $follower->id,
    ]);

    $response = $this->get($url.'&tampered=1');

    $response->assertForbidden();

    $follower->refresh();
    expect($follower->is_active)->toBeTrue();
    expect($follower->unsubscribed_at)->toBeNull();
});

it('rejects expired unsubscribe signed links', function () {
    $report = Report::factory()->create();

    $follower = $report->followers()->create([
        'email' => 'follow@example.com',
        'preferred_locale' => 'fr',
        'is_active' => true,
    ]);

    $url = URL::temporarySignedRoute('report.followers.unsubscribe', now()->subMinute(), [
        'trackingId' => $report->public_tracking_id,
        'follower' => $follower->id,
    ]);

    $response = $this->get($url);

    $response->assertForbidden();

    $follower->refresh();
    expect($follower->is_active)->toBeTrue();
    expect($follower->unsubscribed_at)->toBeNull();
});
