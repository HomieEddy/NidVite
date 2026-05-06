<?php

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReportCategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReportCategorySeeder::class);
    $this->admin = User::factory()->create(['role_id' => Role::where('slug', 'admin')->first()->id]);
});

function createReport(array $attributes = []): Report
{
    /** @var Report $report */
    $report = Report::factory()->create($attributes);

    return $report;
}

describe('ReportStatus enum', function () {
    it('has the expected statuses', function () {
        expect(ReportStatus::values())->toBe([
            'received',
            'verified',
            'scheduled',
            'in_progress',
            'repaired',
            'rejected',
        ]);
    });

    it('defines correct transitions from received', function () {
        expect(ReportStatus::Received->transitions())->toBe([
            'verified',
            'rejected',
        ]);
    });

    it('defines correct transitions from verified', function () {
        expect(ReportStatus::Verified->transitions())->toBe([
            'scheduled',
            'rejected',
        ]);
    });

    it('defines correct transitions from scheduled', function () {
        expect(ReportStatus::Scheduled->transitions())->toBe([
            'in_progress',
            'rejected',
        ]);
    });

    it('defines correct transitions from in_progress', function () {
        expect(ReportStatus::InProgress->transitions())->toBe([
            'repaired',
            'rejected',
        ]);
    });

    it('has no transitions from repaired', function () {
        expect(ReportStatus::Repaired->transitions())->toBe([]);
    });

    it('has no transitions from rejected', function () {
        expect(ReportStatus::Rejected->transitions())->toBe([]);
    });

    it('identifies terminal states', function () {
        expect(ReportStatus::Repaired->isTerminal())->toBeTrue()
            ->and(ReportStatus::Rejected->isTerminal())->toBeTrue()
            ->and(ReportStatus::Received->isTerminal())->toBeFalse();
    });
});

describe('Report state machine', function () {
    it('defaults to received on creation', function () {
        $report = createReport(['status' => null]);

        expect($report->status)->toBe('received');
    });

    it('allows transition from received to verified', function () {
        $report = createReport(['status' => 'received']);

        $report->transitionTo('verified');

        expect($report->fresh()->status)->toBe('verified');
    });

    it('allows transition from received to rejected with reason', function () {
        $report = createReport(['status' => 'received']);

        $report->transitionTo('rejected', 'Out of service area');

        expect($report->fresh()->status)->toBe('rejected')
            ->and($report->fresh()->rejection_reason)->toBe('Out of service area');
    });

    it('allows full forward progression to repaired', function () {
        $report = createReport(['status' => 'received']);

        $report->transitionTo('verified');
        $report->transitionTo('scheduled');
        $report->transitionTo('in_progress');
        $report->transitionTo('repaired');

        expect($report->fresh()->status)->toBe('repaired');
    });

    it('prevents invalid transition from received to repaired', function () {
        $report = createReport(['status' => 'received']);

        expect(fn () => $report->transitionTo('repaired'))
            ->toThrow(InvalidArgumentException::class, "Cannot transition from 'received' to 'repaired'");
    });

    it('prevents transition from repaired to any status', function () {
        $report = createReport(['status' => 'repaired']);

        expect(fn () => $report->transitionTo('in_progress'))
            ->toThrow(InvalidArgumentException::class, "Cannot transition from 'repaired' to 'in_progress'");
    });

    it('prevents transition from rejected to any status', function () {
        $report = createReport(['status' => 'rejected']);

        expect(fn () => $report->transitionTo('verified'))
            ->toThrow(InvalidArgumentException::class, "Cannot transition from 'rejected' to 'verified'");
    });

    it('reports terminal state correctly', function () {
        $repaired = createReport(['status' => 'repaired']);
        $received = createReport(['status' => 'received']);

        expect($repaired->isTerminal())->toBeTrue()
            ->and($received->isTerminal())->toBeFalse();
    });

    it('checks canTransitionTo correctly', function () {
        $report = createReport(['status' => 'scheduled']);

        expect($report->canTransitionTo('in_progress'))->toBeTrue()
            ->and($report->canTransitionTo('repaired'))->toBeFalse();
    });
});

describe('Report activity logging', function () {
    it('logs status transitions to activity log', function () {
        $report = createReport(['status' => 'received']);

        $report->transitionTo('verified');

        $activity = Activity::where('subject_type', Report::class)
            ->where('subject_id', $report->id)
            ->where('log_name', 'report_status')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->properties['old_status'])->toBe('received')
            ->and($activity->properties['new_status'])->toBe('verified');
    });
});
