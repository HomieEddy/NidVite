<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_job_id',
        'material_id',
        'quantity_planned',
        'quantity_actual',
        'unit_cost_at_time',
    ];

    protected $casts = [
        'quantity_planned' => 'float',
        'quantity_actual' => 'float',
        'unit_cost_at_time' => 'decimal:2',
    ];

    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
