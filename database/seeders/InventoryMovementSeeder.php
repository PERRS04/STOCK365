<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryMovementSeeder extends Seeder
{
    public function run(): void
    {
        $products  = Product::where('activo', true)->get();
        $sedes     = Sede::where('activa', true)->get();
        $bossUser  = User::where('role', 'boss')->first();
        $supUser   = User::where('role', 'supervisor')->first();
        $operators = User::where('role', 'operador')->whereNotNull('sede_id')->get()->groupBy('sede_id');

        $entradaMotivos = [
            'Pedido proveedor recibido',
            'Reposición de stock',
            'Compra urgente por demanda',
            'Stock inicial temporada',
            'Devolución de cliente',
        ];

        $salidaMotivos = [
            'Ajuste de inventario físico',
            'Consumo interno',
            'Muestra a proveedor',
            'Corrección de conteo',
        ];

        $ajusteMotivos = [
            'Conteo físico mensual',
            'Corrección diferencia de registro',
            'Auditoria de inventario',
            'Reconciliación de stock',
        ];

        $perdidaMotivos = [
            'Producto vencido / caducado',
            'Rotura durante transporte',
            'Daño en almacén',
            'Producto defectuoso proveedor',
        ];

        // Generate 14 days of movements
        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            $date = now()->subDays($daysAgo)->startOfDay();

            foreach ($sedes as $sede) {
                $sedeOperators = $operators->get($sede->id, collect());
                $actorUser     = $sedeOperators->isNotEmpty() ? $sedeOperators->random() : $supUser;

                // 2-5 movements per sede per day
                $movCount = rand(2, 5);

                for ($m = 0; $m < $movCount; $m++) {
                    $product = $products->random();
                    $hour    = rand(7, 19);
                    $fecha   = $date->copy()->setHour($hour)->setMinute(rand(0, 59));

                    // Weighted type distribution
                    $typeRoll = rand(1, 100);
                    if ($typeRoll <= 35) {
                        $tipo    = 'entrada';
                        $motivo  = $entradaMotivos[array_rand($entradaMotivos)];
                        $cantidad = rand(24, 72);
                        $actor   = $bossUser ?? $supUser;
                    } elseif ($typeRoll <= 55) {
                        $tipo    = 'transferencia';
                        $destSede = $sedes->where('id', '!=', $sede->id)->random();
                        $motivo  = "Transferencia hacia {$destSede->nombre}";
                        $cantidad = rand(6, 24);
                        $actor   = $supUser ?? $bossUser;
                    } elseif ($typeRoll <= 70) {
                        $tipo    = 'ajuste';
                        $motivo  = $ajusteMotivos[array_rand($ajusteMotivos)];
                        $cantidad = rand(1, 12);
                        $actor   = $actorUser;
                    } elseif ($typeRoll <= 85) {
                        $tipo    = 'salida';
                        $motivo  = $salidaMotivos[array_rand($salidaMotivos)];
                        $cantidad = rand(3, 15);
                        $actor   = $actorUser;
                    } else {
                        $tipo    = 'pérdida';
                        $motivo  = $perdidaMotivos[array_rand($perdidaMotivos)];
                        $cantidad = rand(1, 8);
                        $actor   = $actorUser;
                    }

                    InventoryMovement::create([
                        'product_id'       => $product->id,
                        'sede_id'          => $sede->id,
                        'tipo'             => $tipo,
                        'cantidad'         => $cantidad,
                        'motivo'           => $motivo,
                        'user_id'          => $actor->id,
                        'observaciones'    => null,
                        'fecha_movimiento' => $fecha,
                    ]);
                }
            }
        }
    }
}
