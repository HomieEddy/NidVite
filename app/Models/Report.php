<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Report extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            $report->uuid ??= (string) Str::uuid();
            $report->status ??= ReportStatus::Received->value;
        });
    }

    protected $fillable = [
        'uuid',
        'reporter_email',
        'preferred_locale',
        'email_verified_at',
        'location_accuracy',
        'address',
        'neighborhood',
        'borough',
        'status',
        'priority',
        'category_id',
        'description',
        'ip_address_hash',
        'ip_address_raw',
        'user_agent_hash',
        'geofence_passed',
        'geofence_checked_at',
        'submission_duration_ms',
        'is_spam',
        'spam_score',
        'rejection_reason',
        'admin_notes',
        'first_scheduled_at',
        'first_started_at',
        'target_completion_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'geofence_passed' => 'boolean',
        'geofence_checked_at' => 'datetime',
        'submission_duration_ms' => 'integer',
        'is_spam' => 'boolean',
        'spam_score' => 'float',
        'first_scheduled_at' => 'datetime',
        'first_started_at' => 'datetime',
        'target_completion_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'priority', 'admin_notes', 'rejection_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReportCategory::class, 'category_id');
    }

    public function repairJobs(): BelongsToMany
    {
        return $this->belongsToMany(RepairJob::class, 'job_reports')
            ->withPivot(['cost_allocation_percentage', 'cost_override_reason', 'repair_notes'])
            ->withTimestamps();
    }

    public function jobReports(): HasMany
    {
        return $this->hasMany(JobReport::class);
    }

    /**
     * Set the PostGIS geography location from lat/lng.
     */
    public function setLocation(float $latitude, float $longitude): void
    {
        DB::statement(
            'UPDATE reports SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$longitude, $latitude, $this->id]
        );
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
     * Check if the report is in a terminal state.
     */
    public function isTerminal(): bool
    {
        $current = ReportStatus::tryFrom($this->status);

        return $current !== null && $current->isTerminal();
    }
}
