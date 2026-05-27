<?php

namespace App\Console\Commands;

use App\Services\SystemReset\OperationalResetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OperationalResetCommand extends Command
{
    protected $signature = 'stock365:reset-operational
        {--dry-run    : Preview every action without mutating the database or filesystem}
        {--force      : Skip interactive confirmation (blocked in production)}
        {--keep-storage : Do not clean up operational storage files}
        {--clear-kardex : Also clear inventory_movements (kardex history). Default: preserved}';

    protected $description = 'Reset all operational/transactional data. Preserves products, inventory, users, roles, and all master tables.';

    public function __construct(private readonly OperationalResetService $resetService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun       = (bool) $this->option('dry-run');
        $preserveKardex = !$this->option('clear-kardex');
        $keepStorage    = (bool) $this->option('keep-storage');

        $this->renderHeader($isDryRun);

        if (!$this->checkEnvironmentSafety()) {
            return self::FAILURE;
        }

        // ── Pre-reset analysis ──────────────────────────────────────────────
        $warnings  = $this->resetService->preResetWarnings();
        $preview   = $this->resetService->previewCounts($preserveKardex);
        $protected = $this->resetService->protectedCounts();

        $this->renderWarnings($warnings);
        $this->renderProtectedSnapshot($protected);
        $this->renderOperationalPreview($preview, $preserveKardex);

        if (!$keepStorage) {
            $storagePreview = $this->resetService->cleanupOperationalStorage(dryRun: true);
            $this->renderStoragePreview($storagePreview);
        }

        // ── Dry-run exits here ──────────────────────────────────────────────
        if ($isDryRun) {
            $this->newLine();
            $this->line('  <fg=cyan;options=bold>──────────────────────────────────────────────────────────</>');
            $this->line('  <fg=cyan;options=bold>  DRY RUN COMPLETE — zero changes were made.</>');
            $this->line('  <fg=gray>  Run without --dry-run to execute the reset.</>');
            $this->line('  <fg=cyan;options=bold>──────────────────────────────────────────────────────────</>');
            $this->newLine();
            return self::SUCCESS;
        }

        // ── Confirmation ────────────────────────────────────────────────────
        if (!$this->confirmReset($warnings)) {
            $this->newLine();
            $this->line('  <fg=yellow>⚡ Reset cancelled — no data was modified.</>');
            $this->newLine();
            return self::SUCCESS;
        }

        // ── Execute DB reset ────────────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=yellow>  [1/3] Clearing operational tables…</>');

        $result = $this->resetService->execute($preserveKardex);

        if (!$result['success']) {
            $this->renderFailureReport($result);
            return self::FAILURE;
        }

        // ── Storage cleanup ─────────────────────────────────────────────────
        $storageResult = [];
        if (!$keepStorage) {
            $this->line('  <fg=yellow>  [2/3] Cleaning operational storage files…</>');
            $storageResult = $this->resetService->cleanupOperationalStorage(dryRun: false);
        }

        // ── Cache flush ─────────────────────────────────────────────────────
        $this->line('  <fg=yellow>  [3/3] Flushing application caches…</>');
        $cacheResult = $this->flushApplicationCaches();

        // ── Final report ────────────────────────────────────────────────────
        $this->renderSuccessReport($result, $storageResult, $cacheResult, $keepStorage);

        return self::SUCCESS;
    }

    // ─── Environment guard ──────────────────────────────────────────────────

    private function checkEnvironmentSafety(): bool
    {
        if (app()->environment('production') && $this->option('force')) {
            $this->newLine();
            $this->line('  <bg=red;fg=white;options=bold> BLOCKED </> --force is not permitted in production.');
            $this->line('  <fg=gray>  Production resets require interactive confirmation to prevent accidents.</>');
            $this->newLine();
            return false;
        }

        return true;
    }

    // ─── Confirmation flow ──────────────────────────────────────────────────

    private function confirmReset(array $warnings): bool
    {
        if ($this->option('force')) {
            $this->line('  <fg=yellow>  --force flag active — skipping interactive confirmation.</>');
            return true;
        }

        $env = strtoupper(app()->environment());

        $this->newLine();

        if (!empty($warnings)) {
            $highRisk = array_filter($warnings, fn($w) => $w['level'] === 'high');
            if (!empty($highRisk)) {
                $this->line('  <bg=red;fg=white;options=bold> HIGH RISK </> There are active cash sessions that will be force-closed.');
                $this->newLine();
            }
        }

        $this->line("  <fg=red;options=bold>⚠  Permanent deletion of operational data on [{$env}].</>");
        $this->line('  <fg=gray>  Products · Inventory · Users · Roles → NOT affected.</>');
        $this->newLine();

        $answer = $this->ask('  Type <fg=red;options=bold>RESET</> to confirm, anything else cancels');

        return trim($answer) === 'RESET';
    }

    // ─── Cache flush ────────────────────────────────────────────────────────

    private function flushApplicationCaches(): array
    {
        $results = [];

        foreach (['cache:clear', 'view:clear', 'config:clear', 'route:clear'] as $cmd) {
            try {
                Artisan::call($cmd);
                $results[$cmd] = 'cleared';
            } catch (\Throwable $e) {
                $results[$cmd] = 'error: ' . $e->getMessage();
            }
        }

        // Clear session files (file driver)
        $sessionsPath = storage_path('framework/sessions');
        if (is_dir($sessionsPath)) {
            $count = 0;
            foreach (glob($sessionsPath . '/*') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $count++;
                }
            }
            $results['sessions'] = "{$count} file(s) removed";
        }

        // Clear Livewire temporary uploads if directory exists
        $livewireTmp = storage_path('framework/livewire-tmp');
        if (is_dir($livewireTmp)) {
            $count = 0;
            foreach (glob($livewireTmp . '/*') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $count++;
                }
            }
            $results['livewire-tmp'] = "{$count} file(s) removed";
        }

        return $results;
    }

    // ─── Render helpers ─────────────────────────────────────────────────────

    private function renderHeader(bool $isDryRun): void
    {
        $env  = strtoupper(app()->environment());
        $mode = $isDryRun ? '<fg=cyan;options=bold> DRY RUN MODE </>' : '<fg=yellow;options=bold> LIVE MODE </>';

        $this->newLine();
        $this->line('  ┌──────────────────────────────────────────────────────────────┐');
        $this->line('  │  <fg=white;options=bold>STOCK365 — Operational Reset</>                              │');
        $this->line("  │  Environment : <fg=yellow;options=bold>{$env}</>                                       │");
        $this->line("  │  Mode        : {$mode}                                        │");
        $this->line('  │  Scope       : Transactional data only · Master data intact   │');
        $this->line('  └──────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function renderWarnings(array $warnings): void
    {
        if (empty($warnings)) {
            $this->line('  <fg=green>✔  No active sessions or pending operations detected.</>');
            $this->newLine();
            return;
        }

        $levelColors = ['high' => 'red', 'medium' => 'yellow', 'low' => 'gray'];

        $this->line('  <fg=yellow;options=bold>⚠  PRE-RESET WARNINGS</>');
        $this->newLine();

        foreach ($warnings as $w) {
            $color = $levelColors[$w['level']] ?? 'white';
            $badge = strtoupper($w['level']);
            $this->line("  <fg={$color}>[{$badge}]</> {$w['message']}");
        }

        $this->newLine();
    }

    private function renderProtectedSnapshot(array $protected): void
    {
        $this->line('  <fg=green;options=bold>✔  MASTER DATA — PRESERVED</>');
        $this->newLine();

        $rows = [];
        foreach ($protected as $table => $count) {
            $rows[] = [
                $table,
                $count >= 0 ? "<fg=green>{$count}</>" : '<fg=yellow>not found</>',
                $count >= 0 ? '✓ safe' : '—',
            ];
        }

        $this->table(['Table', 'Records', 'Status'], $rows);
    }

    private function renderOperationalPreview(array $preview, bool $preserveKardex): void
    {
        $total = array_sum(array_filter($preview, fn($v) => $v > 0));

        $this->newLine();
        $this->line('  <fg=red;options=bold>✖  OPERATIONAL DATA — TO BE DELETED</>');

        if ($preserveKardex) {
            $this->line('  <fg=cyan>  ℹ  Kardex preserved — inventory_movements will NOT be cleared.</>');
            $this->line('  <fg=gray>     Use --clear-kardex to override.</>');
        } else {
            $this->line('  <fg=yellow>  ⚠  --clear-kardex active — inventory_movements WILL be cleared.</>');
        }

        $this->newLine();

        $rows = [];
        foreach ($preview as $table => $count) {
            if ($count < 0) {
                $rows[] = [$table, '—', '<fg=yellow>table not found</>'];
            } elseif ($count === 0) {
                $rows[] = [$table, '0', '<fg=gray>already empty</>'];
            } else {
                $rows[] = [$table, (string) $count, '<fg=red>will be deleted</>'];
            }
        }

        $this->table(['Table', 'Rows', 'Action'], $rows);

        $this->newLine();
        $this->line("  <fg=red;options=bold>Total rows to delete: {$total}</>");
    }

    private function renderStoragePreview(array $storagePreview): void
    {
        $totalFiles = array_sum(array_column($storagePreview, 'files'));

        $this->newLine();
        $this->line('  <fg=yellow;options=bold>⊘  OPERATIONAL STORAGE FILES — TO BE CLEANED</>');
        $this->newLine();

        $rows = [];
        foreach ($storagePreview as $dir => $info) {
            if (!$info['exists']) {
                $rows[] = ["public/{$dir}", '—', '<fg=gray>directory not found</>'];
            } elseif ($info['files'] === 0) {
                $rows[] = ["public/{$dir}", '0', '<fg=gray>already empty</>'];
            } else {
                $rows[] = ["public/{$dir}", (string) $info['files'], '<fg=yellow>will be deleted</>'];
            }
        }

        $this->table(['Directory', 'Files', 'Action'], $rows);

        if ($totalFiles > 0) {
            $this->line("  <fg=yellow>Total files to delete: {$totalFiles}</>");
        }
    }

    private function renderSuccessReport(
        array $result,
        array $storageResult,
        array $cacheResult,
        bool  $keepStorage
    ): void {
        $totalDeleted = array_sum($result['deleted']);
        $durationSec  = number_format($result['duration_ms'] / 1000, 2);
        $env          = strtoupper(app()->environment());
        $kardexLabel  = $result['preserve_kardex'] ? 'preserved' : 'cleared';

        $this->newLine();
        $this->line('  <bg=green;fg=white;options=bold> ✔  OPERATIONAL RESET COMPLETED SUCCESSFULLY </>');
        $this->newLine();

        // ── Summary box ──────────────────────────────────────────────────
        $this->line("  Environment             : {$env}");
        $this->line("  Kardex (inv. movements) : {$kardexLabel}");
        $this->line("  Operational tables      : " . count($result['deleted']));
        $this->line("  Rows deleted            : <fg=green>{$totalDeleted}</>");
        $this->line("  Execution time          : {$durationSec}s");
        $this->newLine();

        // ── Deletion breakdown ───────────────────────────────────────────
        $this->line('  <fg=white;options=bold>DB TABLES</>');
        $rows = [];
        foreach ($result['deleted'] as $table => $count) {
            $label = $count > 0
                ? "<fg=green>–{$count} rows</>"
                : '<fg=gray>was empty</>';
            $rows[] = [$table, $label];
        }
        $this->table(['Table', 'Result'], $rows);

        // ── AUTO_INCREMENT reset ─────────────────────────────────────────
        $aiReset = array_filter($result['auto_increment_reset'] ?? [], fn($v) => $v === 'reset');
        if (!empty($aiReset)) {
            $this->line('  <fg=cyan>AUTO_INCREMENT reset on: ' . implode(', ', array_keys($aiReset)) . '</>');
            $this->newLine();
        }

        // ── Storage cleanup ──────────────────────────────────────────────
        if (!$keepStorage && !empty($storageResult)) {
            $this->line('  <fg=white;options=bold>STORAGE FILES</>');
            $sRows = [];
            foreach ($storageResult as $dir => $info) {
                $sRows[] = [
                    "public/{$dir}",
                    $info['exists'] ? "<fg=green>{$info['deleted']}</> of {$info['files']} deleted" : '<fg=gray>not found</>',
                ];
            }
            $this->table(['Directory', 'Result'], $sRows);
        }

        // ── Cache flush ──────────────────────────────────────────────────
        $this->line('  <fg=white;options=bold>CACHES FLUSHED</>');
        $cRows = [];
        foreach ($cacheResult as $cmd => $status) {
            $cRows[] = [$cmd, "<fg=green>{$status}</>"];
        }
        $this->table(['Command / Target', 'Result'], $cRows);

        // ── Protected master data final count ───────────────────────────
        $this->line('  <fg=white;options=bold>MASTER DATA (post-reset)</>');
        $pRows = [];
        foreach ($result['protected'] as $table => $count) {
            $pRows[] = [$table, "<fg=green>{$count}</> rows intact"];
        }
        $this->table(['Table', 'Status'], $pRows);

        // ── Ready banner ─────────────────────────────────────────────────
        $this->line('  <fg=green;options=bold>────────────────────────────────────────────────────────────</>');
        $this->line('  <fg=green;options=bold>  System ready for first cash-session opening.</>');
        $this->line('  <fg=gray>  All operators may now open their cajas normally.</>');
        $this->line('  <fg=green;options=bold>────────────────────────────────────────────────────────────</>');
        $this->newLine();
    }

    private function renderFailureReport(array $result): void
    {
        $this->newLine();
        $this->line('  <bg=red;fg=white;options=bold> ✖  RESET FAILED — ALL CHANGES ROLLED BACK </>');
        $this->newLine();
        $this->line('  <fg=red>  Error: ' . ($result['error'] ?? 'unknown') . '</>');
        $this->newLine();
        $this->line('  <fg=gray>  The database was not modified.</>');
        $this->line('  <fg=gray>  Check storage/logs/laravel.log for the full stack trace.</>');
        $this->newLine();
    }
}
