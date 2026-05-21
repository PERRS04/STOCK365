<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $bossId = \App\Models\User::where('email', 'boss@stock365.com')->value('id') ?? 1;

        $products = [
            [
                'sku'           => 'COR-355',
                'nombre'        => 'Corona',
                'marca'         => 'Corona',
                'tamaño'        => '355ml',
                'precio_compra' => 1.10,
                'precio_venta'  => 1.50,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'COR-ITA',
                'nombre'        => 'Coronita',
                'marca'         => 'Corona',
                'tamaño'        => '210ml',
                'precio_compra' => 0.70,
                'precio_venta'  => 1.00,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'PIL-630',
                'nombre'        => 'Pilsener',
                'marca'         => 'Pilsener',
                'tamaño'        => '630ml',
                'precio_compra' => 0.70,
                'precio_venta'  => 1.00,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'PIL-LIT',
                'nombre'        => 'Pilsener Light',
                'marca'         => 'Pilsener',
                'tamaño'        => '630ml',
                'precio_compra' => 0.70,
                'precio_venta'  => 1.00,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'CLV-600',
                'nombre'        => 'Club Verde',
                'marca'         => 'Club',
                'tamaño'        => '600ml',
                'precio_compra' => 0.90,
                'precio_venta'  => 1.25,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'CLB-PLA',
                'nombre'        => 'Club Platino',
                'marca'         => 'Club',
                'tamaño'        => '600ml',
                'precio_compra' => 0.90,
                'precio_venta'  => 1.25,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'BIE-RES',
                'nombre'        => 'Biela Reserva',
                'marca'         => 'Biela',
                'tamaño'        => '630ml',
                'precio_compra' => 0.70,
                'precio_venta'  => 1.00,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'BIE-LIT',
                'nombre'        => 'Biela Light',
                'marca'         => 'Biela',
                'tamaño'        => '630ml',
                'precio_compra' => 0.70,
                'precio_venta'  => 1.00,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'SIE-LAT',
                'nombre'        => 'Siembra Latona',
                'marca'         => 'Siembra',
                'tamaño'        => '330ml',
                'precio_compra' => 0.70,
                'precio_venta'  => 1.00,
                'stock_minimo'  => 24,
            ],
            [
                'sku'           => 'CAR-330',
                'nombre'        => 'Carnival',
                'marca'         => 'Carnival',
                'tamaño'        => '330ml',
                'precio_compra' => 0.20,
                'precio_venta'  => 0.30,
                'stock_minimo'  => 48,
            ],
        ];

        foreach ($products as $data) {
            Product::create($data + ['activo' => true, 'created_by' => $bossId]);
        }
    }
}
