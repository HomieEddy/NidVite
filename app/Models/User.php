<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, PasskeyUser
{
    use HasFactory, Notifiable, PasskeyAuthenticatable;

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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->isAdmin();
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->two_factor_secret ? decrypt($this->two_factor_secret) : null;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->forceFill([
            'two_factor_secret' => $secret !== null ? encrypt($secret) : null,
        ])->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->two_factor_recovery_codes
            ? json_decode(decrypt($this->two_factor_recovery_codes), true)
            : null;
    }

    /**
     * @param  ?array<string>  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => $codes !== null ? encrypt(json_encode($codes)) : null,
        ])->save();
    }
}
