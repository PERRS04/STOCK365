<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function createSale(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::create([
                'sede_id' => $data['sede_id'],
                'user_id' => $data['user_id'],
                'total_sistema' => $data['total_sistema'],
                'descuento' => $data['descuento'] ?? 0,
                'estado' => 'completada',
                'fecha_venta' => now(),
            ]);

            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                ]);

                $this->decrementInventory(
                    $item['product_id'],
                    $data['sede_id'],
                    $item['cantidad'],
                    $data['user_id']
                );
            }

            return $sale;
        });
    }

    private function decrementInventory(int $productId, int $sedeId, int $quantity, int $userId): void
    {
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $productId, 'sede_id' => $sedeId],
            ['cantidad_stock' => 0]
        );

        $inventory->decrement('cantidad_stock', $quantity);

        InventoryMovement::create([
            'product_id' => $productId,
            'sede_id' => $sedeId,
            'tipo' => 'salida',
            'cantidad' => $quantity,
            'motivo' => 'Venta',
            'user_id' => $userId,
            'fecha_movimiento' => now(),
        ]);
    }
}
