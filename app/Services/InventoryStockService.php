<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidLocationException;
use App\Exceptions\InventoryNotFoundException;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryStockService
{
    public function entrada(
        int $productId,
        int $cantidad,
        ?int $sedeId,
        ?int $almacenId,
        ?float $costoUnitario = null,
        ?int $userId = null,
        string $motivo = '',
        ?int $referenceId = null,
        string $referenceType = ''
    ): Inventory {
        return DB::transaction(function () use ($productId, $cantidad, $sedeId, $almacenId, $costoUnitario, $userId, $motivo, $referenceId, $referenceType) {
            $this->validateLocation($sedeId, $almacenId);

            $criteria = $this->locationCriteria($productId, $sedeId, $almacenId);

            $inventory = Inventory::firstOrCreate($criteria, [
                'cantidad_stock'       => 0,
                'ultima_actualizacion' => now(),
            ]);

            // Re-read with lock by PK — prevents lost-update race on concurrent entradas
            $locked = Inventory::lockForUpdate()->find($inventory->id);

            $this->applyMovement($locked, 'entrada', $cantidad, $costoUnitario, $userId, $motivo, $referenceId, $referenceType);

            return $locked->fresh();
        });
    }

    public function salida(
        int $productId,
        int $cantidad,
        ?int $sedeId,
        ?int $almacenId,
        ?float $costoUnitario = null,
        ?int $userId = null,
        string $motivo = '',
        ?int $referenceId = null,
        string $referenceType = ''
    ): Inventory {
        return DB::transaction(function () use ($productId, $cantidad, $sedeId, $almacenId, $costoUnitario, $userId, $motivo, $referenceId, $referenceType) {
            $this->validateLocation($sedeId, $almacenId);

            $locked = Inventory::where($this->locationCriteria($productId, $sedeId, $almacenId))
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                throw new InventoryNotFoundException($productId, $sedeId, $almacenId);
            }

            if ($locked->cantidad_stock < $cantidad) {
                throw new InsufficientStockException($locked->cantidad_stock, $cantidad);
            }

            $this->applyMovement($locked, 'salida', -$cantidad, $costoUnitario, $userId, $motivo, $referenceId, $referenceType);

            return $locked->fresh();
        });
    }

    public function perdida(
        int $productId,
        int $cantidad,
        ?int $sedeId,
        ?int $almacenId,
        ?float $costoUnitario = null,
        ?int $userId = null,
        string $motivo = ''
    ): Inventory {
        return DB::transaction(function () use ($productId, $cantidad, $sedeId, $almacenId, $costoUnitario, $userId, $motivo) {
            $this->validateLocation($sedeId, $almacenId);

            $locked = Inventory::where($this->locationCriteria($productId, $sedeId, $almacenId))
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                throw new InventoryNotFoundException($productId, $sedeId, $almacenId);
            }

            if ($locked->cantidad_stock < $cantidad) {
                throw new InsufficientStockException($locked->cantidad_stock, $cantidad);
            }

            $this->applyMovement($locked, 'pérdida', -$cantidad, $costoUnitario, $userId, $motivo);

            return $locked->fresh();
        });
    }

    public function setStockAbsolute(
        int $productId,
        int $targetCantidad,
        ?int $sedeId,
        ?int $almacenId,
        ?float $costoUnitario = null,
        ?int $userId = null,
        string $motivo = ''
    ): Inventory {
        return DB::transaction(function () use ($productId, $targetCantidad, $sedeId, $almacenId, $costoUnitario, $userId, $motivo) {
            $this->validateLocation($sedeId, $almacenId);

            $criteria = $this->locationCriteria($productId, $sedeId, $almacenId);

            $inventory = Inventory::firstOrCreate($criteria, [
                'cantidad_stock'       => 0,
                'ultima_actualizacion' => now(),
            ]);

            $locked = Inventory::lockForUpdate()->find($inventory->id);

            $delta = $targetCantidad - $locked->cantidad_stock;

            if ($delta === 0) {
                return $locked;
            }

            $this->applyMovement($locked, 'ajuste', $delta, $costoUnitario, $userId, $motivo);

            return $locked->fresh();
        });
    }

    private function validateLocation(?int $sedeId, ?int $almacenId): void
    {
        if ($sedeId !== null && $almacenId !== null) {
            throw new InvalidLocationException('Cannot specify both sede_id and almacen_id.');
        }

        if ($sedeId === null && $almacenId === null) {
            throw new InvalidLocationException('Must specify either sede_id or almacen_id.');
        }
    }

    private function locationCriteria(int $productId, ?int $sedeId, ?int $almacenId): array
    {
        return [
            'product_id' => $productId,
            'sede_id'    => $sedeId,    // null → WHERE sede_id IS NULL (Eloquent converts)
            'almacen_id' => $almacenId, // null → WHERE almacen_id IS NULL
        ];
    }

    private function applyMovement(
        Inventory $locked,
        string $tipo,
        int $delta,
        ?float $costoUnitario,
        ?int $userId,
        string $motivo,
        ?int $referenceId = null,
        string $referenceType = ''
    ): void {
        $locked->update([
            'cantidad_stock'       => $locked->cantidad_stock + $delta,
            'ultima_actualizacion' => now(),
        ]);

        InventoryMovement::create([
            'product_id'       => $locked->product_id,
            'sede_id'          => $locked->sede_id,
            'almacen_id'       => $locked->almacen_id,
            'tipo'             => $tipo,
            'cantidad'         => abs($delta),
            'motivo'           => $motivo,
            'user_id'          => $userId,
            'costo_unitario'   => $costoUnitario,
            'fecha_movimiento' => now(),
            'reference_id'     => $referenceId ?: null,
            'reference_type'   => $referenceType ?: null,
        ]);
    }
}
