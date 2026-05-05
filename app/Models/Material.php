<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'unit',
        'current_stock',
        'reserved_stock',
        'min_stock_alert',
        'avg_purchase_price',
        'last_purchase_price',
        'location',
        'is_active',
    ];

    protected $casts = [
        'current_stock' => 'float',
        'reserved_stock' => 'float',
        'min_stock_alert' => 'float',
        'avg_purchase_price' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(MaterialPurchase::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function repairJobs(): BelongsToMany
    {
        return $this->belongsToMany(RepairJob::class, 'job_materials')
            ->withPivot(['quantity_planned', 'quantity_actual', 'unit_cost_at_time'])
            ->withTimestamps();
    }

    public function jobMaterials(): HasMany
    {
        return $this->hasMany(JobMaterial::class);
    }
}
