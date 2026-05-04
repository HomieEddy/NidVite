<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class RepairJob extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (RepairJob $job) {
            $job->uuid ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'scheduled_at',
        'started_at',
        'completed_at',
        'status',
        'created_by',
        'estimated_cost',
        'actual_cost',
        'weather_conditions',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jobReports(): HasMany
    {
        return $this->hasMany(JobReport::class);
    }

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(Report::class, 'job_reports')
            ->withPivot(['cost_allocation_percentage', 'cost_override_reason', 'repair_notes'])
            ->withTimestamps();
    }

    public function workers(): HasMany
    {
        return $this->hasMany(JobWorker::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'job_workers')
            ->withPivot(['role_in_job', 'hours_worked'])
            ->withTimestamps();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function jobMaterials(): HasMany
    {
        return $this->hasMany(JobMaterial::class);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'job_materials')
            ->withPivot(['quantity_planned', 'quantity_actual', 'unit_cost_at_time'])
            ->withTimestamps();
    }
}
