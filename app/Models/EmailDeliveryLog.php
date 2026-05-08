<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class EmailDeliveryLog extends Model
{
    use HasFactory, Prunable;

    public const RETENTION_DAYS = 90;

    protected $fillable = [
        'report_id',
        'user_id',
        'kind',
        'attempts',
        'status',
        'last_error',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = [
            'pending' => ['sending', 'permanent_failed'],
            'sending' => ['delivered', 'permanent_failed'],
            'delivered' => [],
            'permanent_failed' => [],
        ];

        return in_array($newStatus, $allowedTransitions[$this->status] ?? [], true);
    }

    public function transitionTo(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException("Invalid status transition from {$this->status} to {$newStatus}.");
        }

        $this->status = $newStatus;

        if ($newStatus === 'delivered') {
            $this->delivered_at = now();
        }

        if ($newStatus === 'permanent_failed') {
            $this->failed_at = now();
        }
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }
}
