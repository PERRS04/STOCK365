<?php

namespace App\Exceptions;

class InventoryNotFoundException extends \RuntimeException
{
    public function __construct(int $productId, ?int $sedeId, ?int $almacenId)
    {
        $location = $sedeId !== null ? "sede:{$sedeId}" : "almacen:{$almacenId}";
        parent::__construct("Inventory not found for product {$productId} at {$location}.");
    }
}
