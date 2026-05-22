<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    // Spatie must always use the web guard — prevents sanctum guard mismatch.
    public string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'sede_id',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function cashClosings()
    {
        return $this->hasMany(CashClosing::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    public function isBoss(): bool
    {
        return $this->hasRole('boss');
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function isOperator(): bool
    {
        return $this->hasRole('operador');
    }

    public function isAdminLevel(): bool
    {
        return $this->hasAnyRole(['boss', 'supervisor']);
    }
}
