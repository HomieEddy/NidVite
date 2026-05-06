<?php

use App\Mail\ReportStatusUpdated;
use App\Models\Report;
use Database\Seeders\ReportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ReportCategorySeeder::class);
    Mail::fake();
});

it('sends email notification when report status changes', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'reporter_email' => 'citizen@example.com',
        'preferred_locale' => 'fr',
    ]);

    $report->transitionTo('verified');

    Mail::assertQueued(ReportStatusUpdated::class, function (ReportStatusUpdated $mail) use ($report) {
        return $mail->hasTo('citizen@example.com')
            && $mail->report->id === $report->id
            && $mail->oldStatus === 'received';
    });
});

it('sends email with rejection reason when report is rejected', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'reporter_email' => 'citizen@example.com',
    ]);

    $report->transitionTo('rejected', 'Out of service area');

    Mail::assertQueued(ReportStatusUpdated::class, function (ReportStatusUpdated $mail) {
        return $mail->report->status === 'rejected'
            && $mail->report->rejection_reason === 'Out of service area';
    });
});

it('does not send email when reporter_email is null', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'reporter_email' => null,
    ]);

    $report->transitionTo('verified');

    Mail::assertNothingQueued();
});

it('uses preferred_locale in email subject', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'reporter_email' => 'citizen@example.com',
        'preferred_locale' => 'en',
    ]);

    $report->transitionTo('verified');

    Mail::assertQueued(ReportStatusUpdated::class, function (ReportStatusUpdated $mail) {
        return str_contains($mail->envelope()->subject, 'Your Report Update');
    });
});

it('uses french locale by default in email subject', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'reporter_email' => 'citizen@example.com',
        'preferred_locale' => 'fr',
    ]);

    $report->transitionTo('verified');

    Mail::assertQueued(ReportStatusUpdated::class, function (ReportStatusUpdated $mail) {
        return str_contains($mail->envelope()->subject, 'Mise à jour');
    });
});

it('queues email via ShouldQueue interface', function () {
    $report = Report::factory()->create([
        'status' => 'received',
        'reporter_email' => 'citizen@example.com',
    ]);

    $report->transitionTo('verified');

    Mail::assertQueued(ReportStatusUpdated::class);
});
