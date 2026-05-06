<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialPurchase extends Model
{
    use HasFactory;

    protected $table = 'material_purchases';

    protected $fillable = [
        'material_id',
        'quantity',
        'unit_cost',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'vendor',
        'stock_updated',
        'purchased_at',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'stock_updated' => 'boolean',
        'purchased_at' => 'datetime',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
