<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_job_id',
        'report_id',
        'cost_allocation_percentage',
        'cost_override_reason',
        'repair_notes',
    ];

    protected $casts = [
        'cost_allocation_percentage' => 'float',
    ];

    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
