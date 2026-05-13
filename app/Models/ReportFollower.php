<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class ReportFollower extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'email',
        'preferred_locale',
        'is_active',
        'unsubscribed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'unsubscribed_at' => 'datetime',
        'last_notified_on' => 'date',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function signedUnsubscribeUrl(): string
    {
        $trackingId = $this->report?->public_tracking_id;
        if (! is_string($trackingId) || $trackingId === '') {
            throw new RuntimeException('Cannot generate unsubscribe URL without an associated report tracking ID.');
        }

        return URL::temporarySignedRoute(
            'report.followers.unsubscribe',
            now()->addDays(30),
            [
                'trackingId' => $trackingId,
                'follower' => $this->getKey(),
            ]
        );
    }
}
