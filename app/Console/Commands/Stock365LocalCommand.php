<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * stock365:local
 *
 * One-command local setup: applies .env.local.example overrides to .env,
 * clears all caches, and runs pending migrations.
 *
 * Safe to run multiple times (idempotent).
 */
class Stock365LocalCommand extends Command
{
    protected $signature   = 'stock365:local {--force : Skip confirmation}';
    protected $description = 'Apply local dev settings from .env.local.example, clear caches, run migrations';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=blue;options=bold>STOCK365 Local Setup</>');
        $this->line('  ' . str_repeat('─', 48));
        $this->newLine();

        // ── Apply .env.local.example → .env ──────────────────────────────────
        $examplePath = base_path('.env.local.example');
        $envPath     = base_path('.env');

        if (!file_exists($examplePath)) {
            $this->error('  .env.local.example not found');
            return self::FAILURE;
        }

        if (!file_exists($envPath)) {
            $this->error('  .env not found — run: cp .env.example .env first');
            return self::FAILURE;
        }

        // Parse the example overrides (skip blanks and comments)
        $overrides = [];
        foreach (file($examplePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $overrides[trim($key)] = trim($value);
        }

        if (empty($overrides)) {
            $this->warn('  No overrides found in .env.local.example');
            return self::SUCCESS;
        }

        // Show planned changes
        $envContent = file_get_contents($envPath);
        $changed = [];

        foreach ($overrides as $key => $newValue) {
            $pattern = "/^{$key}=.*/m";
            preg_match($pattern, $envContent, $match);
            $currentLine = $match[0] ?? null;
            $newLine = "{$key}={$newValue}";

            if ($currentLine === null) {
                $changed[] = ['type' => 'add',    'key' => $key, 'new' => $newValue, 'old' => null];
            } elseif ($currentLine !== $newLine) {
                $changed[] = ['type' => 'update', 'key' => $key, 'new' => $newValue, 'old' => substr($currentLine, strlen($key) + 1)];
            }
        }

        if (empty($changed)) {
            $this->line('  <fg=green>✓</>  .env already has correct local settings');
        } else {
            $this->line('  Planned .env changes:');
            $this->newLine();
            foreach ($changed as $c) {
                if ($c['type'] === 'add') {
                    $this->line("  <fg=green>+</>  {$c['key']}={$c['new']}  <fg=gray>(new)</>",);
                } else {
                    $this->line("  <fg=yellow>~</>  {$c['key']}: <fg=red>{$c['old']}</> → <fg=green>{$c['new']}</>");
                }
            }

            $this->newLine();
            if (!$this->option('force') && !$this->confirm('  Apply these changes to .env?', true)) {
                $this->line('  Aborted.');
                return self::SUCCESS;
            }

            // Apply changes
            foreach ($overrides as $key => $newValue) {
                $pattern = "/^{$key}=.*/m";
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, "{$key}={$newValue}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$newValue}";
                }
            }

            file_put_contents($envPath, $envContent);
            $this->line('  <fg=green>✓</>  .env updated');
        }

        // ── Also write a .env.local for bootstrap loader ─────────────────────
        file_put_contents(
            base_path('.env.local'),
            file_get_contents($examplePath)
        );
        $this->line('  <fg=green>✓</>  .env.local written (bootstrap override active)');

        // ── Clear all caches ──────────────────────────────────────────────────
        $this->newLine();
        $this->line('  Clearing caches...');
        foreach (['config:clear', 'cache:clear', 'route:clear', 'view:clear'] as $cmd) {
            Artisan::call($cmd);
            $this->line("  <fg=green>✓</>  {$cmd}");
        }

        // ── Run pending migrations ────────────────────────────────────────────
        $this->newLine();
        $this->line('  Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();
        if (str_contains($output, 'Nothing to migrate')) {
            $this->line('  <fg=green>✓</>  Migrations — nothing pending');
        } else {
            $this->line('  <fg=green>✓</>  Migrations ran');
        }

        // ── Done ──────────────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  ' . str_repeat('─', 48));
        $this->line('  <fg=green>✓  Local environment ready!</>');
        $this->newLine();
        $this->line('  Next steps:');
        $this->line('  <fg=cyan>  npm run rebuild</>                             (fresh CSS build)');
        $this->line('  <fg=cyan>  php artisan serve --host=127.0.0.1 --port=8000</>');
        $this->line('  <fg=cyan>  npm run dev</>                                 (hot reload, optional)');
        $this->newLine();
        $this->line('  Verify: <fg=cyan>php artisan stock365:doctor</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
