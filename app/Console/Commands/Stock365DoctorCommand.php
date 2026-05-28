<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class Stock365DoctorCommand extends Command
{
    protected $signature   = 'stock365:doctor';
    protected $description = 'Validate local STOCK365 environment — APP_URL, DB, Vite build, CSS integrity, migrations';

    private const VPS_PATTERNS = ['192.168.100.', '10.0.', '172.16.', '172.17.', '172.18.', '172.19.', '172.2', '172.3'];

    private const REQUIRED_CSS = [
        'page-title', 'page-subtitle',
        'metric-value', 'metric-label',
        'btn-primary', 'btn-secondary', 'btn-ghost',
        'shadow-card', 'glow-emerald',
        'form-label', 'empty-state', 'animate-status-ring',
    ];

    private int $passed  = 0;
    private int $warned  = 0;
    private int $failed  = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=blue;options=bold>STOCK365 Environment Doctor</>');
        $this->line('  ' . str_repeat('─', 54));
        $this->newLine();

        $this->checkAppEnv();
        $this->checkAppUrl();
        $this->checkAppDebug();
        $this->checkDatabase();
        $this->checkConfigCache();
        $this->checkViteBuild();
        $this->checkMigrations();
        $this->checkEnvLocal();

        $this->printSummary();

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ── Checks ────────────────────────────────────────────────────────────────

    private function checkAppEnv(): void
    {
        $env = app()->environment();
        match ($env) {
            'local', 'testing' => $this->ok('APP_ENV', $env),
            default => $this->caution('APP_ENV', "$env — set to 'local' for local dev"),
        };
    }

    private function checkAppUrl(): void
    {
        $url = config('app.url');

        $isVps = collect(self::VPS_PATTERNS)->contains(fn ($p) => str_contains($url, $p));
        if ($isVps) {
            $this->bad('APP_URL', "$url  ← VPS IP — set to http://127.0.0.1:8000");
            return;
        }

        if (str_contains($url, '127.0.0.1') || str_contains($url, 'localhost')) {
            $this->ok('APP_URL', $url);
        } else {
            $this->caution('APP_URL', "$url (unusual for local dev — expected 127.0.0.1:8000)");
        }
    }

    private function checkAppDebug(): void
    {
        config('app.debug')
            ? $this->ok('APP_DEBUG', 'true')
            : $this->caution('APP_DEBUG', 'false — set to true for local debugging');
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $db = DB::connection()->getDatabaseName();
            $this->ok('Database', "connected — $db");
        } catch (\Throwable $e) {
            $this->bad('Database', 'cannot connect — ' . $e->getMessage());
        }
    }

    private function checkConfigCache(): void
    {
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->caution('Config cache', 'EXISTS — run: php artisan config:clear');
        } else {
            $this->ok('Config cache', 'not cached (correct for local dev)');
        }
    }

    private function checkViteBuild(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (!file_exists($manifestPath)) {
            $this->bad('Vite build', 'manifest.json missing — run: npm run rebuild');
            return;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $cssEntry = $manifest['resources/css/app.css'] ?? null;

        if (!$cssEntry) {
            $this->bad('Vite manifest', 'no CSS entry — run: npm run rebuild');
            return;
        }

        $cssFile = $cssEntry['file'];
        $cssPath = public_path("build/$cssFile");

        if (!file_exists($cssPath)) {
            $this->bad('CSS file', "manifest references $cssFile but file is missing — run: npm run rebuild");
            return;
        }

        // Build freshness: warn if source is newer than build
        $buildMtime  = filemtime($cssPath);
        $sourceMtime = filemtime(resource_path('css/app.css'));
        if ($sourceMtime > $buildMtime) {
            $diff = round(($sourceMtime - $buildMtime) / 60);
            $this->caution('Build freshness', "app.css source is {$diff}m newer than build — run: npm run rebuild");
        } else {
            $kb = round(filesize($cssPath) / 1024, 1);
            $this->ok('Vite build', "$cssFile ($kb KB)");
        }

        // CSS integrity
        $css     = file_get_contents($cssPath);
        $missing = array_filter(self::REQUIRED_CSS, fn ($c) => !str_contains($css, $c));

        if (!empty($missing)) {
            $this->bad('CSS integrity', 'missing: ' . implode(', ', $missing) . ' — run: npm run rebuild');
        } else {
            $this->ok('CSS integrity', count(self::REQUIRED_CSS) . '/' . count(self::REQUIRED_CSS) . ' design-system classes ✓');
        }
    }

    private function checkMigrations(): void
    {
        try {
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            Artisan::call('migrate:status', [], $output);
            $pendingCount = substr_count($output->fetch(), 'Pending');

            if ($pendingCount > 0) {
                $this->caution('Migrations', "$pendingCount pending — run: php artisan migrate");
            } else {
                $this->ok('Migrations', 'all up to date');
            }
        } catch (\Throwable $e) {
            $this->caution('Migrations', 'could not check — ' . $e->getMessage());
        }
    }

    private function checkEnvLocal(): void
    {
        if (file_exists(base_path('.env.local'))) {
            $this->ok('.env.local', 'found — local overrides active');
        } else {
            $this->caution('.env.local', 'not found — run: php artisan stock365:local');
        }
    }

    // ── Output helpers ────────────────────────────────────────────────────────

    private function ok(string $label, string $detail): void
    {
        $this->line(sprintf('  <fg=green>✓</>  %-22s %s', $label, $detail));
        $this->passed++;
    }

    private function caution(string $label, string $detail): void
    {
        $this->line(sprintf('  <fg=yellow>⚠</>  %-22s %s', $label, $detail));
        $this->warned++;
    }

    private function bad(string $label, string $detail): void
    {
        $this->line(sprintf('  <fg=red>✗</>  %-22s %s', $label, $detail));
        $this->failed++;
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->line('  ' . str_repeat('─', 54));
        $this->line("  <fg=green>✓ {$this->passed} passed</>    <fg=yellow>⚠ {$this->warned} warnings</>    <fg=red>✗ {$this->failed} failed</>");

        if ($this->failed > 0) {
            $this->newLine();
            $this->line('  <fg=red>Fix failures before starting the local server.</>');
            $this->line('  Run <fg=cyan>php artisan stock365:local</> to apply local settings automatically.');
        } elseif ($this->warned > 0) {
            $this->newLine();
            $this->line('  <fg=yellow>Warnings are non-blocking but will cause confusion.</>');
            $this->line('  Run <fg=cyan>php artisan stock365:local</> to fix automatically.');
        } else {
            $this->newLine();
            $this->line('  <fg=green>Environment is healthy — ready for local review.</> 🚀');
        }

        $this->newLine();
    }
}
