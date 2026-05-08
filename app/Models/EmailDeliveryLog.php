<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDeliveryLog extends Model
{
    use HasFactory;

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
}
