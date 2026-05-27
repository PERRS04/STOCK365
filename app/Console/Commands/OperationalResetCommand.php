<?php

namespace App\Console\Commands;

use App\Services\SystemReset\OperationalResetService;
use Illuminate\Console\Command;

class OperationalResetCommand extends Command
{
    protected $signature = 'stock365:reset-operational
                            {--force : Skip interactive confirmation (only allowed outside production)}';

    protected $description = 'Reset all operational/transactional data. Preserves products, inventory, users, roles, and all master tables.';

    public function __construct(private readonly OperationalResetService $resetService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->renderHeader();

        if (!$this->checkEnvironmentSafety()) {
            return self::FAILURE;
        }

        $preview = $this->resetService->previewCounts();
        $protected = $this->resetService->protectedCounts();

        $this->renderProtectedSnapshot($protected);
        $this->renderOperationalPreview($preview);

        if (!$this->confirmReset()) {
            $this->newLine();
            $this->line('  <fg=yellow>⚡ Reset cancelled. No data was modified.</>');
            $this->newLine();
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('  <fg=yellow>⏳ Executing operational reset…</>');
        $this->newLine();

        $result = $this->resetService->execute();

        if ($result['success']) {
            $this->renderSuccessReport($result);
            return self::SUCCESS;
        }

        $this->renderFailureReport($result);
        return self::FAILURE;
    }

    // ─── Environment guard ──────────────────────────────────────────────────

    private function checkEnvironmentSafety(): bool
    {
        $env = app()->environment();

        if ($env === 'production' && $this->option('force')) {
            $this->newLine();
            $this->line('  <bg=red;fg=white> ERROR </> --force is not allowed in production.');
            $this->line('  <fg=gray>Production resets require interactive confirmation to prevent accidents.</>');
            $this->newLine();
            return false;
        }

        return true;
    }

    // ─── Confirmation flow ──────────────────────────────────────────────────

    private function confirmReset(): bool
    {
        if ($this->option('force')) {
            $this->line('  <fg=yellow>--force flag detected. Skipping interactive confirmation.</>');
            return true;
        }

        $env = strtoupper(app()->environment());

        $this->newLine();
        $this->line("  <fg=red;options=bold>⚠  You are about to permanently delete operational data on [{$env}].</>");
        $this->line('  <fg=gray>This action cannot be undone. Products, inventory, and users will NOT be affected.</>');
        $this->newLine();

        $answer = $this->ask('  Type <fg=red;options=bold>RESET</> to confirm (anything else cancels)');

        return trim($answer) === 'RESET';
    }

    // ─── Render helpers ─────────────────────────────────────────────────────

    private function renderHeader(): void
    {
        $env     = strtoupper(app()->environment());
        $version = config('app.name', 'STOCK365');

        $this->newLine();
        $this->line('  ┌─────────────────────────────────────────────────────────┐');
        $this->line("  │  <fg=cyan;options=bold>STOCK365 — Operational Reset</> <fg=gray>({$version})</>              │");
        $this->line("  │  <fg=gray>Environment:</> <fg=yellow;options=bold>{$env}</>                                      │");
        $this->line('  │  <fg=gray>Clears transactional data · Preserves master data</>         │');
        $this->line('  └─────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function renderProtectedSnapshot(array $protected): void
    {
        $this->line('  <fg=green;options=bold>✔  MASTER DATA — WILL BE PRESERVED</>');
        $this->newLine();

        $rows = [];
        foreach ($protected as $table => $count) {
            $rows[] = [
                $table,
                $count >= 0
                    ? "<fg=green>{$count} rows</>  ✓"
                    : '<fg=yellow>table not found</>',
            ];
        }

        $this->table(
            ['Table', 'Records'],
            $rows
        );
    }

    private function renderOperationalPreview(array $preview): void
    {
        $total = array_sum(array_filter($preview, fn($v) => $v > 0));

        $this->newLine();
        $this->line('  <fg=red;options=bold>✖  OPERATIONAL DATA — WILL BE DELETED</>');
        $this->newLine();

        $rows = [];
        foreach ($preview as $table => $count) {
            if ($count < 0) {
                $rows[] = [$table, '<fg=yellow>table not found</>'];
            } elseif ($count === 0) {
                $rows[] = [$table, '<fg=gray>already empty</>'];
            } else {
                $rows[] = [$table, "<fg=red>{$count} rows</>"];
            }
        }

        $this->table(['Table', 'Rows to delete'], $rows);

        $this->newLine();
        $this->line("  <fg=red;options=bold>Total rows to be deleted: {$total}</>");
        $this->newLine();
    }

    private function renderSuccessReport(array $result): void
    {
        $totalDeleted = array_sum($result['deleted']);
        $ms           = $result['duration_ms'];

        $this->line('  <bg=green;fg=white;options=bold> ✔  RESET COMPLETED SUCCESSFULLY </>');
        $this->newLine();

        $rows = [];
        foreach ($result['deleted'] as $table => $count) {
            $label = $count > 0
                ? "<fg=green>–{$count} rows deleted</>"
                : '<fg=gray>already empty</>';
            $rows[] = [$table, $label];
        }

        $this->table(['Table', 'Result'], $rows);

        $this->newLine();
        $this->line("  <fg=green>Total deleted : {$totalDeleted} rows</>");
        $this->line("  <fg=gray>Duration      : {$ms} ms</>");
        $this->newLine();

        $this->line('  <fg=cyan;options=bold>PROTECTED TABLES — FINAL COUNT:</>');
        $this->newLine();

        $protectedRows = [];
        foreach ($result['protected'] as $table => $count) {
            $protectedRows[] = [$table, "<fg=green>{$count}</> rows intact"];
        }
        $this->table(['Table', 'Status'], $protectedRows);

        $this->newLine();
        $this->line('  <fg=yellow>Next steps:</>');
        $this->line('  <fg=gray>  php artisan optimize:clear</>');
        $this->line('  <fg=gray>  php artisan optimize</>');
        $this->line('  <fg=gray>  The system is ready for first cash-session opening.</>');
        $this->newLine();
    }

    private function renderFailureReport(array $result): void
    {
        $this->newLine();
        $this->line('  <bg=red;fg=white;options=bold> ✖  RESET FAILED — ALL CHANGES ROLLED BACK </>');
        $this->newLine();
        $this->line('  <fg=red>Error: ' . ($result['error'] ?? 'unknown') . '</>');
        $this->newLine();
        $this->line('  <fg=gray>The database was not modified. Check storage/logs/laravel.log for details.</>');
        $this->newLine();
    }
}
