<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Prunable;

class SuspiciousActivity extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'report_id',
        'type',
        'severity',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    public function prunable(): Builder
    {
        $retentionDays = max(1, (int) config('activity_intelligence.retention_days', 180));

        return static::query()
            ->where(function (Builder $query): void {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
            })
            ->orWhere(function (Builder $query) use ($retentionDays): void {
                $query->whereNull('expires_at')
                    ->where('created_at', '<=', now()->subDays($retentionDays));
            });
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
