<?php

namespace App\Models;

use App\Models\Concerns\HasReportCoordinates;
use App\Enums\ReportStatus;
use App\Mail\ReportStatusUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Report extends Model implements HasMedia
{
    use HasFactory, HasReportCoordinates, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            $report->uuid ??= (string) Str::uuid();
            $report->public_tracking_id ??= static::generatePublicTrackingId();
            $report->status ??= ReportStatus::Received->value;
            $report->ip_address_raw ??= request()->ip();

            $fingerprint = request()->attributes->get('device_fingerprint_hash');
            if (is_string($fingerprint) && $fingerprint !== '') {
                $report->device_fingerprint_hash = $fingerprint;
            }
        });
    }

    protected $fillable = [
        'uuid',
        'public_tracking_id',
        'reporter_email',
        'preferred_locale',
        'address',
        'neighborhood',
        'borough',
        'status',
        'priority',
        'category_id',
        'description',
        'ip_address_raw',
        'archive_path',
        'archived_at',
        'geofence_passed',
        'is_spam',
        'rejection_reason',
        'admin_notes',
        'first_scheduled_at',
        'first_started_at',
        'target_completion_at',
        'completed_at',
        'expires_at',
        'location_accuracy',
        'location_source',
    ];

    protected $casts = [
        'geofence_passed' => 'boolean',
        'is_spam' => 'boolean',
        'first_scheduled_at' => 'datetime',
        'first_started_at' => 'datetime',
        'target_completion_at' => 'datetime',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
        'expires_at' => 'datetime',
        'location_accuracy' => 'float',
        'road_distance_meters' => 'float',
        'location_accuracy_passed' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'priority', 'admin_notes', 'rejection_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('report-photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function signedPhotoUrls(int $ttlMinutes = 5): array
    {
        $expiresAt = now()->addMinutes($ttlMinutes);

        return $this->getMedia('report-photos')
            ->map(fn ($media) => URL::temporarySignedRoute('media.signed', $expiresAt, ['media' => $media->getKey()]))
            ->values()
            ->all();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReportCategory::class, 'category_id');
    }

    public function repairJobs(): BelongsToMany
    {
        return $this->belongsToMany(RepairJob::class, 'job_reports')
            ->withPivot(['cost_allocation_percentage'])
            ->withTimestamps();
    }

    public function jobReports(): HasMany
    {
        return $this->hasMany(JobReport::class);
    }

    public function suspiciousActivities(): HasMany
    {
        return $this->hasMany(SuspiciousActivity::class);
    }

    /**
     * Set the PostGIS geography location from lat/lng.
     */
    public function setLocation(float $latitude, float $longitude, ?float $accuracy = null, ?string $source = null): void
    {
        if ($accuracy !== null && ($accuracy < 0 || ! is_numeric($accuracy))) {
            throw new InvalidArgumentException('location_accuracy must be a non-negative number');
        }

        if ($source !== null) {
            $source = trim($source);
            if ($source === '' || ! in_array($source, ['gps', 'manual', 'geocode'], true)) {
                throw new InvalidArgumentException('location_source must be one of: gps, manual, geocode');
            }
        }

        DB::statement(
            'UPDATE reports SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$longitude, $latitude, $this->id]
        );

        if ($accuracy !== null) {
            $this->location_accuracy = $accuracy;
        }

        if ($source !== null) {
            $this->location_source = $source;
        }

        if ($accuracy !== null || $source !== null) {
            $this->saveQuietly();
        }
    }

    /**
     * Scope reports within a radius (in meters) of a point.
     */
    public function scopeNear(Builder $query, float $latitude, float $longitude, int $radiusMeters = 1000): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$longitude, $latitude, $radiusMeters]
        );
    }

    /**
     * Scope reports by status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope non-spam reports.
     */
    public function scopeNotSpam(Builder $query): Builder
    {
        return $query->where('is_spam', false);
    }

    /**
     * Transition the report to a new status.
     *
     * @throws InvalidArgumentException
     */
    public function transitionTo(string $newStatus, ?string $reason = null): void
    {
        $current = ReportStatus::tryFrom($this->status);

        /** @phpstan-ignore identical.alwaysFalse */
        if ($current === null) {
            throw new InvalidArgumentException("Invalid current status: {$this->status}");
        }

        if (! $current->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                "Cannot transition from '{$this->status}' to '{$newStatus}'. Allowed: "
                .implode(', ', $current->transitions())
            );
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        if ($newStatus === ReportStatus::Rejected->value && $reason !== null) {
            $this->rejection_reason = $reason;
        }

        $this->save();

        activity('report_status')
            ->performedOn($this)
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
            ])
            ->log("Report status changed from {$oldStatus} to {$newStatus}");

        $this->sendStatusNotification($oldStatus);
    }

    /**
     * Send email notification to reporter about status change.
     */
    protected function sendStatusNotification(string $oldStatus): void
    {
        if ($this->reporter_email === null) {
            return;
        }

        Mail::to($this->reporter_email)
            ->send(new ReportStatusUpdated($this, $oldStatus));
    }

    /**
     * Check if the report can transition to the given status.
     */
    public function canTransitionTo(string $status): bool
    {
        $current = ReportStatus::tryFrom($this->status);

        return $current !== null && $current->canTransitionTo($status);
    }

    /**
     * Apply a manual admin override for road-validation decision with an audit note.
     */
    public function overrideRoadValidation(string $decision, string $note): void
    {
        $allowedDecisions = ['pass', 'fail_off_street', 'fail_low_accuracy', 'fail_both'];

        $decision = trim($decision);
        if (! in_array($decision, $allowedDecisions, true)) {
            throw new InvalidArgumentException('Invalid road_validation_decision provided for override');
        }

        $note = trim($note);
        if ($note === '') {
            throw new InvalidArgumentException('Audit note is required for validation override');
        }

        $oldDecision = $this->road_validation_decision;
        $oldReason = $this->road_validation_reason;
        $oldMode = $this->road_validation_mode;

        $this->road_validation_decision = $decision;
        $this->road_validation_reason = 'admin_override';
        $this->road_validation_mode = 'admin_override';
        $this->location_accuracy_passed = ! in_array($decision, ['fail_low_accuracy', 'fail_both'], true);
        $this->save();

        activity('report_validation_override')
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_decision' => $oldDecision,
                'new_decision' => $decision,
                'old_reason' => $oldReason,
                'new_reason' => 'admin_override',
                'old_mode' => $oldMode,
                'new_mode' => 'admin_override',
                'audit_note' => $note,
            ])
            ->log('Report road validation overridden by admin');
    }

    /**
     * Check if the report is in a terminal state.
     */
    public function isTerminal(): bool
    {
        $current = ReportStatus::tryFrom($this->status);

        return $current !== null && $current->isTerminal();
    }

    /**
     * Validate that the given coordinates are within the Montreal boundary.
     *
     * @throws ValidationException
     */
    public static function validateGeofence(float $latitude, float $longitude): void
    {
        if (! MontrealBoundary::contains($latitude, $longitude)) {
            throw ValidationException::withMessages([
                'location' => [__('report.validation.outside_montreal')],
            ]);
        }
    }

    protected static function generatePublicTrackingId(): string
    {
        do {
            $candidate = 'MTL'.strtoupper(Str::random(8));
        } while (static::query()->where('public_tracking_id', $candidate)->exists());

        return $candidate;
    }
}
