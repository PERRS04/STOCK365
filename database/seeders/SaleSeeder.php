<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::where('activo', true)->get();
        $sedes    = Sede::where('activa', true)->get();

        // Operators grouped by sede
        $operatorsBySede = User::where('role', 'operador')
            ->whereNotNull('sede_id')
            ->get()
            ->groupBy('sede_id');

        // Weighted product pool — popular products appear more
        $productPool = [];
        foreach ($products as $p) {
            $weight = match($p->marca) {
                'Pilsener'   => 5,
                'Club Verde' => 4,
                'Corona'     => 3,
                'Cusqueña'   => 2,
                default      => 1,
            };
            for ($w = 0; $w < $weight; $w++) {
                $productPool[] = $p->id;
            }
        }
        $productById = $products->keyBy('id');

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date      = now()->subDays($daysAgo)->startOfDay();
            $dayOfWeek = (int) $date->format('N'); // 1=Mon … 7=Sun

            foreach ($sedes as $sede) {
                $operators = $operatorsBySede->get($sede->id, collect());
                if ($operators->isEmpty()) continue;

                // Vary volume by day of week and sede
                $peakDay  = in_array($dayOfWeek, [5, 6, 7]);
                $maxHour  = $daysAgo === 0 ? (int) now()->format('H') : 20;

                $baseSales = $peakDay ? rand(18, 28) : rand(10, 18);
                if ($daysAgo === 0) {
                    $baseSales = rand(4, 12); // partial day today
                }

                for ($s = 0; $s < $baseSales; $s++) {
                    $operator = $operators->random();
                    $hour     = rand(9, max(9, $maxHour));
                    $fecha    = $date->copy()->setHour($hour)->setMinute(rand(0, 59))->setSecond(0);

                    // Build items
                    $numItems    = rand(1, 4);
                    $pickedItems = [];

                    for ($i = 0; $i < $numItems; $i++) {
                        $productId = $productPool[array_rand($productPool)];
                        if (!isset($pickedItems[$productId])) {
                            $pickedItems[$productId] = 0;
                        }
                        $pickedItems[$productId] += rand(2, 8);
                    }

                    $total     = 0;
                    $descuento = 0;
                    $itemsData = [];

                    foreach ($pickedItems as $productId => $cantidad) {
                        $product  = $productById[$productId];
                        $subtotal = round($product->precio_venta * $cantidad, 2);
                        $total   += $subtotal;
                        $itemsData[] = [
                            'product_id'      => $productId,
                            'cantidad'        => $cantidad,
                            'precio_unitario' => $product->precio_venta,
                            'subtotal'        => $subtotal,
                        ];
                    }

                    // Small discount on 15% of sales
                    if (rand(1, 100) <= 15) {
                        $descuento = round($total * 0.05, 2);
                        $total     = round($total - $descuento, 2);
                    }

                    $sale = Sale::create([
                        'sede_id'         => $sede->id,
                        'user_id'         => $operator->id,
                        'cash_session_id' => null,
                        'total_sistema'   => $total,
                        'descuento'       => $descuento,
                        'estado'          => 'completada',
                        'fecha_venta'     => $fecha,
                    ]);

                    foreach ($itemsData as $item) {
                        SaleItem::create(array_merge($item, ['sale_id' => $sale->id]));
                    }
                }
            }
        }
    }
}
