<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'uuid',
        'role_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login_at',
        'locale',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'role_id' => 'integer',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function repairJobs(): HasMany
    {
        return $this->hasMany(RepairJob::class, 'created_by');
    }

    public function assignedRepairJobs(): BelongsToMany
    {
        return $this->belongsToMany(RepairJob::class, 'job_workers')
            ->withPivot(['role_in_job', 'hours_worked'])
            ->withTimestamps();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function materialPurchases(): HasMany
    {
        return $this->hasMany(MaterialPurchase::class, 'created_by');
    }

    public function jobWorkers(): HasMany
    {
        return $this->hasMany(JobWorker::class);
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role?->slug === 'manager';
    }

    public function isServiceWorker(): bool
    {
        return $this->role?->slug === 'service_worker';
    }

    public function isAccountant(): bool
    {
        return $this->role?->slug === 'accountant';
    }

    public function isViewer(): bool
    {
        return $this->role?->slug === 'viewer';
    }

    public function canManage(): bool
    {
        return in_array($this->role?->slug, ['admin', 'manager'], true);
    }
}
