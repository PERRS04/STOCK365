<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Sede extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'ciudad',
        'ubicacion',
        'telefono',
        'email',
        'activa',
        'is_demo',
    ];

    protected $casts = [
        'activa'  => 'boolean',
        'is_demo' => 'boolean',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeProduction($query)
    {
        return $query->where('is_demo', false);
    }

    public function scopeDemo($query)
    {
        return $query->where('is_demo', true);
    }

    // IDs de sedes demo, cacheados 5 min para evitar N+1 en los engines.
    public static function demoIds(): \Illuminate\Support\Collection
    {
        return Cache::remember('sede_demo_ids', 300, function () {
            // Guard against missing migration — returns empty collection (no demo sedes)
            // rather than throwing SQLSTATE[42S22] if is_demo column doesn't exist yet.
            if (!\Illuminate\Support\Facades\Schema::hasColumn('sedes', 'is_demo')) {
                return collect();
            }
            return static::where('is_demo', true)->pluck('id');
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function cashClosings()
    {
        return $this->hasMany(CashClosing::class);
    }

    public function stockAlerts()
    {
        return $this->hasMany(StockAlert::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
