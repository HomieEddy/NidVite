<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSavedView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'sort_column',
        'sort_direction',
        'search',
        'expires_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
