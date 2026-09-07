<?php

namespace App\Exceptions;

class InsufficientStockException extends \RuntimeException
{
    public int $disponible;
    public int $requerido;

    public function __construct(int $disponible, int $requerido)
    {
        $this->disponible = $disponible;
        $this->requerido  = $requerido;
        parent::__construct("Stock insuficiente. Disponible: {$disponible}. Requerido: {$requerido}.");
    }
}
