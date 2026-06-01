<?php

namespace App\OperationalIntelligence\Contracts;

use Illuminate\Support\Collection;

interface DetectorContract
{
    public function detect(): Collection;
}
