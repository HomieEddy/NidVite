<?php

namespace App\Models;

use App\Notifications\LowStockMaterialAlertNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RepairJob extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (RepairJob $job) {
            $job->uuid ??= (string) Str::uuid();
        });

        static::updated(function (RepairJob $job): void {
            if (! $job->wasChanged('status')) {
                return;
            }

            if ($job->getOriginal('status') === 'completed' || $job->status !== 'completed') {
                return;
            }

            $job->loadMissing('jobMaterials.material');

            $recipients = User::query()
                ->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->whereIn('slug', ['admin', 'manager']))
                ->get();

            foreach ($job->jobMaterials as $jobMaterial) {
                $material = $jobMaterial->material;
                if (! $material) {
                    continue;
                }

                $used = (float) ($jobMaterial->quantity_actual ?? $jobMaterial->quantity_planned ?? 0);
                if ($used <= 0) {
                    continue;
                }

                $previousStock = (float) $material->current_stock;
                $material->current_stock = max(0, $previousStock - $used);
                $material->reserved_stock = max(0, (float) $material->reserved_stock - $used);
                $material->save();

                $threshold = (float) $material->min_stock_alert;
                if ($threshold <= 0 || $previousStock < $threshold || (float) $material->current_stock >= $threshold) {
                    continue;
                }

                foreach ($recipients as $recipient) {
                    $recipient->notify(new LowStockMaterialAlertNotification($material, $previousStock));
                }
            }
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
            ->withPivot(['cost_allocation_percentage'])
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

    public function assignWorkers(array $workerIds): void
    {
        $eligibleWorkers = User::query()
            ->where('is_active', true)
            ->whereIn('id', $workerIds)
            ->whereHas('role', fn ($query) => $query->where('slug', 'service_worker'))
            ->pluck('id')
            ->all();

        if ($eligibleWorkers === []) {
            return;
        }

        $payload = [];
        foreach ($eligibleWorkers as $workerId) {
            $payload[$workerId] = ['role_in_job' => 'assistant'];
        }

        $this->users()->syncWithoutDetaching($payload);
    }

    public function selfAssign(User $user): void
    {
        if (! $user->isServiceWorker()) {
            throw new InvalidArgumentException('Only service workers can self-assign jobs.');
        }

        if (! in_array($this->status, ['planned', 'in_progress'], true)) {
            throw new InvalidArgumentException('This job is not eligible for self-assignment.');
        }

        if ($this->users()->whereKey($user->id)->exists()) {
            return;
        }

        $this->users()->attach($user->id, ['role_in_job' => 'assistant']);
    }

    public function applyEqualCostAllocation(): void
    {
        $reportIds = $this->reports()->pluck('reports.id')->all();
        $count = count($reportIds);

        if ($count === 0) {
            return;
        }

        $base = round(100 / $count, 2);
        $remaining = 100.0;

        foreach ($reportIds as $index => $reportId) {
            $allocation = $index === $count - 1 ? round($remaining, 2) : $base;
            $this->reports()->updateExistingPivot($reportId, ['cost_allocation_percentage' => $allocation]);
            $remaining -= $allocation;
        }
    }

    public function applyManualCostAllocation(array $overrides): void
    {
        foreach ($overrides as $reportId => $allocation) {
            $this->reports()->updateExistingPivot((int) $reportId, [
                'cost_allocation_percentage' => (float) $allocation,
            ]);
        }
    }
}
