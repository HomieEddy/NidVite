<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_job_id',
        'category_id',
        'material_id',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'vendor',
        'incurred_at',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'incurred_at' => 'datetime',
    ];

    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
