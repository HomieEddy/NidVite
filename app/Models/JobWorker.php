<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_job_id',
        'user_id',
        'role_in_job',
        'hours_worked',
    ];

    protected $casts = [
        'hours_worked' => 'float',
    ];

    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
