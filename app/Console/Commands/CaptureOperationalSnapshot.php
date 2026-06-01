<?php

namespace App\Console\Commands;

use App\OperationalIntelligence\Memory\OperationalSnapshotEngine;
use Illuminate\Console\Command;

class CaptureOperationalSnapshot extends Command
{
    protected $signature   = 'oi:snapshot {--sede= : Capture only this sede_id}';
    protected $description = 'Capture hourly operational health snapshot for all active sedes';

    public function handle(OperationalSnapshotEngine $engine): int
    {
        $sedeId = $this->option('sede');

        if ($sedeId) {
            $engine->captureForSede((int) $sedeId);
            $this->info("Snapshot captured for sede {$sedeId}.");
            return self::SUCCESS;
        }

        $count = $engine->captureAll();
        $this->info("Snapshots captured for {$count} sedes.");

        return self::SUCCESS;
    }
}
