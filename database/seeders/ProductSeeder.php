<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar o crear BOSS user para created_by
        $boss = \App\Models\User::where('role', 'boss')->first();
        $bossId = $boss?->id ?? 1;

        $products = [
            // Pilsener
            [
                'sku' => 'PIL-630',
                'nombre' => 'Pilsener',
                'marca' => 'Pilsener',
                'tamaño' => '630ml',
                'precio_compra' => 1.20,
                'precio_venta' => 1.80,
                'stock_minimo' => 20,
                'created_by' => $bossId,
            ],
            [
                'sku' => 'PIL-355',
                'nombre' => 'Pilsener',
                'marca' => 'Pilsener',
                'tamaño' => '355ml',
                'precio_compra' => 0.80,
                'precio_venta' => 1.20,
                'stock_minimo' => 30,
                'created_by' => $bossId,
            ],
            [
                'sku' => 'PIL-1L',
                'nombre' => 'Pilsener',
                'marca' => 'Pilsener',
                'tamaño' => '1 Litro',
                'precio_compra' => 2.00,
                'precio_venta' => 3.00,
                'stock_minimo' => 15,
                'created_by' => $bossId,
            ],
            // Corona
            [
                'sku' => 'COR-355',
                'nombre' => 'Corona Extra',
                'marca' => 'Corona',
                'tamaño' => '355ml',
                'precio_compra' => 1.50,
                'precio_venta' => 2.30,
                'stock_minimo' => 25,
                'created_by' => $bossId,
            ],
            [
                'sku' => 'COR-330',
                'nombre' => 'Corona Light',
                'marca' => 'Corona',
                'tamaño' => '330ml',
                'precio_compra' => 1.40,
                'precio_venta' => 2.20,
                'stock_minimo' => 20,
                'created_by' => $bossId,
            ],
            // Budweiser
            [
                'sku' => 'BUD-473',
                'nombre' => 'Budweiser',
                'marca' => 'Budweiser',
                'tamaño' => '473ml',
                'precio_compra' => 1.80,
                'precio_venta' => 2.80,
                'stock_minimo' => 20,
                'created_by' => $bossId,
            ],
            [
                'sku' => 'BUD-355',
                'nombre' => 'Budweiser',
                'marca' => 'Budweiser',
                'tamaño' => '355ml',
                'precio_compra' => 1.40,
                'precio_venta' => 2.10,
                'stock_minimo' => 25,
                'created_by' => $bossId,
            ],
            // Heineken
            [
                'sku' => 'HEI-355',
                'nombre' => 'Heineken',
                'marca' => 'Heineken',
                'tamaño' => '355ml',
                'precio_compra' => 1.60,
                'precio_venta' => 2.50,
                'stock_minimo' => 20,
                'created_by' => $bossId,
            ],
            [
                'sku' => 'HEI-0KL',
                'nombre' => 'Heineken 0.0',
                'marca' => 'Heineken',
                'tamaño' => '355ml',
                'precio_compra' => 1.50,
                'precio_venta' => 2.30,
                'stock_minimo' => 15,
                'created_by' => $bossId,
            ],
            // Cusqueña
            [
                'sku' => 'CUS-355',
                'nombre' => 'Cusqueña',
                'marca' => 'Cusqueña',
                'tamaño' => '355ml',
                'precio_compra' => 1.10,
                'precio_venta' => 1.70,
                'stock_minimo' => 25,
                'created_by' => $bossId,
            ],
            [
                'sku' => 'CUS-473',
                'nombre' => 'Cusqueña Dorada',
                'marca' => 'Cusqueña',
                'tamaño' => '473ml',
                'precio_compra' => 1.45,
                'precio_venta' => 2.20,
                'stock_minimo' => 20,
                'created_by' => $bossId,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product + ['activo' => true]);
        }
    }
}
