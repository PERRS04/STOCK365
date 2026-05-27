<?php

namespace App\Services\SystemReset;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * OperationalResetService
 *
 * Wipes all transactional / operational data from the database and
 * (optionally) cleans up orphaned storage files, leaving every master
 * table — products, inventories, users, sedes, roles, permissions —
 * completely intact.
 *
 * Design principles
 * ─────────────────
 * • DELETEs run inside a single DB::transaction() so any failure
 *   leaves the database unchanged.
 * • FK checks are disabled session-wide for the duration of the DELETEs
 *   and always restored in a finally{} block.
 * • ALTER TABLE … AUTO_INCREMENT = 1 is DDL (implicit commit in MySQL)
 *   and therefore runs AFTER the transaction commits, only on cleared tables.
 * • PRESERVE_KARDEX (default: true) keeps inventory_movements intact so
 *   stock history / kardex is not lost on production resets.
 * • Every method is safe to call in --dry-run mode (no mutations).
 */
class OperationalResetService
{
    // ─── Configuration ─────────────────────────────────────────────────────

    /**
     * When true, inventory_movements is NOT deleted (kardex preserved).
     * Set to false only when you need a true blank-slate demo environment.
     */
    protected const PRESERVE_KARDEX = true;

    // ─── Table lists ────────────────────────────────────────────────────────

    /**
     * Always-cleared operational tables, in strict child-before-parent order.
     * Changing this order requires re-verifying FK dependencies.
     */
    private const OPERATIONAL_TABLES = [
        // children of cash_movements
        'receipt_payment_allocations',

        // children of cash_sessions
        'cash_session_adjustments',

        // children of sales
        'sale_items',

        // children of inventory_receipts
        'inventory_receipt_items',

        // children of purchase_orders
        'purchase_order_items',

        // children of stock_transfers
        'stock_transfer_items',

        // direct operationals (after children are gone)
        'cash_movements',
        'cash_closings',        // soft-delete model; DB::table bypasses deleted_at
        'sales',

        // session container
        'cash_sessions',

        // standalone operationals
        'courtesy_transactions',
        'inventory_receipts',
        'stock_transfers',
        'purchase_orders',
        'stock_alerts',

        // audit / system noise
        'activity_logs',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
    ];

    /**
     * Cleared only when $preserveKardex === false.
     * Kept separate so the rest of the system never accidentally touches it.
     */
    private const KARDEX_TABLES = ['inventory_movements'];

    /**
     * Hard-protected tables. A runtime assertion verifies these never
     * appear in the operational lists (guards against future copy-paste errors).
     */
    private const PROTECTED_TABLES = [
        'users',
        'sedes',
        'providers',
        'products',
        'inventories',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'migrations',
    ];

    /**
     * Operational storage directories (relative to the 'public' disk root).
     * Files here are orphaned after the DB reset.
     * Product images, logos, and other master-data assets live elsewhere
     * and are never touched.
     */
    private const OPERATIONAL_STORAGE_DIRS = [
        'cash_movements',   // CashMovementController attachment_path
        'invoices',         // InventoryReceiptController invoice_path
        'sede-payments',    // SedePaymentController evidence_path
    ];

    // ─── Public API ─────────────────────────────────────────────────────────

    /**
     * Returns the full list of tables that will be cleared for the given
     * kardex setting (useful for preview and AUTO_INCREMENT reset).
     */
    public function effectiveTables(bool $preserveKardex): array
    {
        return $preserveKardex
            ? self::OPERATIONAL_TABLES
            : array_merge(self::OPERATIONAL_TABLES, self::KARDEX_TABLES);
    }

    /**
     * Snapshot of current record counts for the effective operational tables.
     *
     * @return array<string, int>  table => count  (-1 means table not found)
     */
    public function previewCounts(bool $preserveKardex): array
    {
        return $this->countTables($this->effectiveTables($preserveKardex));
    }

    /**
     * Snapshot of current record counts for every protected master table.
     *
     * @return array<string, int>
     */
    public function protectedCounts(): array
    {
        return $this->countTables(self::PROTECTED_TABLES);
    }

    /**
     * Analyses operational state and returns a list of warnings.
     * Does NOT block execution — the caller decides how to present them.
     *
     * @return list<array{level: string, table: string, count: int, message: string}>
     */
    public function preResetWarnings(): array
    {
        $warnings = [];

        $checks = [
            [
                'table'   => 'cash_sessions',
                'level'   => 'high',
                'where'   => ['status' => ['open', 'pending_closing']],
                'whereIn' => true,
                'message' => '%d caja(s) ACTIVA(S). Deben cerrarse antes del reset.',
            ],
            [
                'table'   => 'cash_closings',
                'level'   => 'medium',
                'where'   => ['estado' => 'pendiente'],
                'whereIn' => false,
                'message' => '%d cierre(s) pendiente(s) de aprobación.',
            ],
            [
                'table'   => 'inventory_receipts',
                'level'   => 'medium',
                'where'   => ['estado' => 'pendiente'],
                'whereIn' => false,
                'message' => '%d recepción(es) de inventario sin aprobar.',
            ],
            [
                'table'   => 'cash_movements',
                'level'   => 'low',
                'where'   => ['status' => 'pendiente'],
                'whereIn' => false,
                'message' => '%d movimiento(s) de caja sin aprobar.',
            ],
        ];

        foreach ($checks as $check) {
            try {
                $query = DB::table($check['table']);

                if ($check['whereIn']) {
                    $key = array_key_first($check['where']);
                    $query->whereIn($key, $check['where'][$key]);
                } else {
                    $query->where($check['where']);
                }

                $count = $query->count();

                if ($count > 0) {
                    $warnings[] = [
                        'level'   => $check['level'],
                        'table'   => $check['table'],
                        'count'   => $count,
                        'message' => sprintf($check['message'], $count),
                    ];
                }
            } catch (\Throwable) {
                // Table might not exist in this environment — skip silently.
            }
        }

        return $warnings;
    }

    /**
     * Executes the operational reset.
     *
     * Steps:
     *  1. Safety assertion (protected ∩ operational must be empty)
     *  2. Disable FK checks (session-scoped)
     *  3. DB::transaction() — DELETE all effective tables
     *  4. Re-enable FK checks (always, in finally{})
     *  5. On success: reset AUTO_INCREMENT for cleared tables
     *
     * @param  bool  $preserveKardex  Whether to keep inventory_movements
     * @param  bool  $dryRun          When true, returns counts without mutating
     * @return array<string, mixed>
     */
    public function execute(bool $preserveKardex, bool $dryRun = false): array
    {
        $this->assertSafeConfiguration();

        $startedAt = microtime(true);
        $tables    = $this->effectiveTables($preserveKardex);

        if ($dryRun) {
            return $this->buildDryRunResult($tables, $preserveKardex, $startedAt);
        }

        $deleted             = [];
        $autoIncrementReset  = [];
        $error               = null;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::transaction(function () use ($tables, &$deleted) {
                foreach ($tables as $table) {
                    $rows = DB::table($table)->delete();
                    $deleted[$table] = $rows;
                }
            });

            // AUTO_INCREMENT resets are DDL — they MUST run outside the transaction.
            // Only executed after the transaction commits successfully.
            $autoIncrementReset = $this->resetAutoIncrements($tables);

            $this->writeLog('info', $deleted, $autoIncrementReset, $preserveKardex);

        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $this->writeLog('critical', [], [], $preserveKardex, $error);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return [
            'success'              => $error === null,
            'dry_run'              => false,
            'preserve_kardex'      => $preserveKardex,
            'deleted'              => $deleted,
            'auto_increment_reset' => $autoIncrementReset,
            'protected'            => $this->protectedCounts(),
            'duration_ms'          => (int) round((microtime(true) - $startedAt) * 1000),
            'error'                => $error,
        ];
    }

    /**
     * Scans the operational storage directories and optionally deletes
     * orphaned files. Never touches product images or master-data assets.
     *
     * @param  bool  $dryRun  When true, only counts files without deleting
     * @return array<string, array{exists: bool, files: int, deleted: int}>
     */
    public function cleanupOperationalStorage(bool $dryRun = false): array
    {
        $results = [];

        foreach (self::OPERATIONAL_STORAGE_DIRS as $dir) {
            $results[$dir] = ['exists' => false, 'files' => 0, 'deleted' => 0];

            try {
                if (!Storage::disk('public')->exists($dir)) {
                    continue;
                }

                $files = $this->listStorageFilesRecursive($dir);
                $results[$dir]['exists'] = true;
                $results[$dir]['files']  = count($files);

                if (!$dryRun) {
                    foreach ($files as $file) {
                        try {
                            Storage::disk('public')->delete($file);
                            $results[$dir]['deleted']++;
                        } catch (\Throwable) {
                            // File already gone or permission error — continue.
                        }
                    }
                }
            } catch (\Throwable) {
                // Directory inaccessible — skip without noise.
            }
        }

        return $results;
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    /**
     * Resets the AUTO_INCREMENT counter to 1 for each table.
     * Silently skips tables without an AUTO_INCREMENT column (e.g. password_reset_tokens).
     * This is intentionally called OUTSIDE any transaction.
     *
     * @param  string[]  $tables
     * @return array<string, string>  table => 'reset' | 'skipped'
     */
    private function resetAutoIncrements(array $tables): array
    {
        $results = [];

        foreach ($tables as $table) {
            try {
                DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                $results[$table] = 'reset';
            } catch (\Throwable) {
                $results[$table] = 'skipped'; // no AUTO_INCREMENT column
            }
        }

        return $results;
    }

    /**
     * @param  string[]  $tables
     * @return array<string, int>
     */
    private function countTables(array $tables): array
    {
        $counts = [];

        foreach ($tables as $table) {
            try {
                $counts[$table] = (int) DB::table($table)->count();
            } catch (\Throwable) {
                $counts[$table] = -1;
            }
        }

        return $counts;
    }

    /**
     * Returns all file paths inside a storage directory, recursively.
     *
     * @return string[]
     */
    private function listStorageFilesRecursive(string $dir): array
    {
        $allFiles = [];

        $files = Storage::disk('public')->files($dir);
        $allFiles = array_merge($allFiles, $files);

        foreach (Storage::disk('public')->directories($dir) as $subDir) {
            $allFiles = array_merge($allFiles, $this->listStorageFilesRecursive($subDir));
        }

        return $allFiles;
    }

    private function buildDryRunResult(array $tables, bool $preserveKardex, float $startedAt): array
    {
        return [
            'success'         => true,
            'dry_run'         => true,
            'preserve_kardex' => $preserveKardex,
            'would_delete'    => $this->countTables($tables),
            'preserved_kardex_count' => $preserveKardex
                ? $this->countTables(self::KARDEX_TABLES)
                : [],
            'protected'       => $this->protectedCounts(),
            'storage_preview' => $this->cleanupOperationalStorage(dryRun: true),
            'duration_ms'     => (int) round((microtime(true) - $startedAt) * 1000),
            'error'           => null,
        ];
    }

    private function assertSafeConfiguration(): void
    {
        $intersection = array_intersect(
            array_merge(self::OPERATIONAL_TABLES, self::KARDEX_TABLES),
            self::PROTECTED_TABLES
        );

        if (!empty($intersection)) {
            throw new \LogicException(
                'Safety violation: these tables appear in BOTH operational and protected lists: '
                . implode(', ', $intersection)
            );
        }
    }

    private function writeLog(
        string $level,
        array  $deleted,
        array  $aiReset,
        bool   $preserveKardex,
        string $error = ''
    ): void {
        $context = [
            'environment'          => app()->environment(),
            'preserve_kardex'      => $preserveKardex,
            'total_rows_deleted'   => array_sum($deleted),
            'tables_cleared'       => count(array_filter($deleted, fn($v) => $v > 0)),
            'auto_increment_reset' => array_keys(array_filter($aiReset, fn($v) => $v === 'reset')),
            'breakdown'            => $deleted,
            'executed_via'         => 'artisan:stock365:reset-operational',
            'timestamp'            => now()->toIso8601String(),
        ];

        if ($error) {
            $context['error'] = $error;
            Log::channel('daily')->critical('[OperationalReset] Reset FAILED — transaction rolled back.', $context);
        } else {
            Log::channel('daily')->warning('[OperationalReset] Reset completed successfully.', $context);
        }
    }
}
