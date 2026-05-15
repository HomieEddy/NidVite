<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Expense $expense): void {
            $quantity = (float) ($expense->quantity ?? 0);
            $unitCost = (float) ($expense->unit_cost ?? 0);
            $subtotal = round($quantity * $unitCost, 2);

            $gstRate = (float) ($expense->gst_rate ?? 0.05);
            $qstRate = (float) ($expense->qst_rate ?? 0.0998);
            $combinedTaxRate = round($gstRate + $qstRate, 4);

            $expense->subtotal = $subtotal;
            $expense->tax_rate = $combinedTaxRate;
            $expense->tax_amount = round($subtotal * $combinedTaxRate, 2);
            $expense->total = round($subtotal + (float) $expense->tax_amount, 2);
        });

        static::saved(function (Expense $expense): void {
            if ($expense->cost_allocation_mode !== 'equal_split' || ! $expense->repair_job_id) {
                return;
            }

            $expense->repairJob?->applyEqualCostAllocation();
        });
    }

    protected $fillable = [
        'repair_job_id',
        'material_id',
        'vendor_id',
        'description',
        'quantity',
        'unit',
        'unit_cost',
        'subtotal',
        'tax_rate',
        'gst_rate',
        'qst_rate',
        'cost_allocation_mode',
        'receipt_path',
        'tax_amount',
        'total',
        'incurred_at',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'gst_rate' => 'decimal:4',
        'qst_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'incurred_at' => 'datetime',
    ];

    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function vendorRelation(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
