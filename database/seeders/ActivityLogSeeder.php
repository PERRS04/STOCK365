<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $sedes     = Sede::all();
        $boss      = User::where('role', 'boss')->first();
        $supervisor= User::where('role', 'supervisor')->first();
        $operators = User::where('role', 'operador')->whereNotNull('sede_id')->get();

        $entries = [
            // Boss / supervisor actions
            ['role' => 'boss',       'user' => $boss,       'action' => 'producto.creado',       'desc' => 'Producto creado: Club Verde 600ml (CLV-600)'],
            ['role' => 'boss',       'user' => $boss,       'action' => 'producto.actualizado',   'desc' => 'Precio actualizado: Pilsener 630ml → $1.80'],
            ['role' => 'supervisor', 'user' => $supervisor, 'action' => 'cierre.aprobado',        'desc' => 'Cierre de caja aprobado — Centenario'],
            ['role' => 'supervisor', 'user' => $supervisor, 'action' => 'cierre.aprobado',        'desc' => 'Cierre de caja aprobado — Bahía'],
            ['role' => 'boss',       'user' => $boss,       'action' => 'inventario.ajustado',    'desc' => 'Ajuste stock: +48 Pilsener 630ml — Sauces'],
            ['role' => 'supervisor', 'user' => $supervisor, 'action' => 'inventario.ajustado',    'desc' => 'Ajuste stock: +36 Club Verde 600ml — Florida'],
            ['role' => 'boss',       'user' => $boss,       'action' => 'pedido.creado',          'desc' => 'Pedido #12 creado — Cervecería Nacional · $1,440.00'],
            ['role' => 'supervisor', 'user' => $supervisor, 'action' => 'cierre.rechazado',       'desc' => 'Cierre rechazado: diferencia excede límite — Martha'],
            ['role' => 'boss',       'user' => $boss,       'action' => 'usuario.creado',         'desc' => 'Nuevo operador registrado: Diego – Martha'],
            ['role' => 'supervisor', 'user' => $supervisor, 'action' => 'transferencia.realizada','desc' => 'Transferencia: 24 Corona Extra · Centenario → Bahía'],
        ];

        // Operator actions per sede
        $operatorActions = [
            ['action' => 'venta.registrada',    'desc' => 'Venta registrada — %s'],
            ['action' => 'caja.abierta',        'desc' => 'Caja abierta — %s'],
            ['action' => 'caja.cerrada',        'desc' => 'Cierre de caja enviado — %s'],
            ['action' => 'venta.registrada',    'desc' => 'Venta registrada — %s'],
            ['action' => 'venta.registrada',    'desc' => 'Venta registrada — %s'],
            ['action' => 'inventario.ajustado', 'desc' => 'Ajuste manual solicitado — %s'],
        ];

        $now = now();

        // Write boss/supervisor entries (oldest first)
        foreach ($entries as $i => $entry) {
            ActivityLog::create([
                'user_id'    => $entry['user']?->id,
                'user_name'  => $entry['user']?->name ?? 'Sistema',
                'user_role'  => $entry['role'],
                'sede_id'    => $sedes->random()->id,
                'action'     => $entry['action'],
                'description'=> $entry['desc'],
                'model_type' => null,
                'model_id'   => null,
                'old_values' => null,
                'new_values' => null,
                'ip_address' => '10.0.0.' . rand(2, 20),
                'created_at' => $now->copy()->subMinutes(($i + 1) * 18),
                'updated_at' => $now->copy()->subMinutes(($i + 1) * 18),
            ]);
        }

        // Write operator entries (recent, distributed across sedes)
        foreach ($operators as $j => $operator) {
            $sede    = $sedes->firstWhere('id', $operator->sede_id);
            $action  = $operatorActions[$j % count($operatorActions)];
            $desc    = sprintf($action['desc'], $sede?->nombre ?? '—');

            ActivityLog::create([
                'user_id'    => $operator->id,
                'user_name'  => $operator->name,
                'user_role'  => 'operador',
                'sede_id'    => $operator->sede_id,
                'action'     => $action['action'],
                'description'=> $desc,
                'model_type' => null,
                'model_id'   => null,
                'old_values' => null,
                'new_values' => null,
                'ip_address' => '10.0.0.' . rand(50, 80),
                'created_at' => $now->copy()->subMinutes($j * 7 + 3),
                'updated_at' => $now->copy()->subMinutes($j * 7 + 3),
            ]);
        }
    }
}
