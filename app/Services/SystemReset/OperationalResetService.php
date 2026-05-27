<?php

namespace App\Services\SystemReset;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Clears all operational/transactional data while preserving every
 * master/structural table (products, inventory, users, sedes, roles…).
 *
 * Safe to run repeatedly on staging/demo environments.
 * Requires explicit confirmation before execution in production.
 */
class OperationalResetService
{
    /**
     * Tables that will be emptied, in child-before-parent order so that
     * no foreign-key violation can occur even with FK_CHECKS on.
     * The list is exhaustive — every transactional table in the schema.
     */
    private const OPERATIONAL_TABLES = [
        // ── children of cash_movements ───────────────────────────
        'receipt_payment_allocations',

        // ── children of cash_sessions ────────────────────────────
        'cash_session_adjustments',

        // ── children of sales ────────────────────────────────────
        'sale_items',

        // ── children of inventory_receipts ───────────────────────
        'inventory_receipt_items',

        // ── children of purchase_orders ──────────────────────────
        'purchase_order_items',

        // ── children of stock_transfers ──────────────────────────
        'stock_transfer_items',

        // ── grandchildren resolved — now the direct operationals ─
        'cash_movements',
        'cash_closings',        // has soft-deletes; DB::table bypasses them
        'sales',

        // ── session container (emptied after its children) ───────
        'cash_sessions',

        // ── standalone operationals ──────────────────────────────
        'courtesy_transactions',
        'inventory_receipts',
        'inventory_movements',
        'stock_transfers',
        'purchase_orders',
        'stock_alerts',

        // ── audit / system noise ─────────────────────────────────
        'activity_logs',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
    ];

    /**
     * Tables that must NEVER be touched. Checked as a safety guard
     * before any deletion attempt.
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
     * Returns a snapshot of record counts before the reset so the
     * confirmation screen can show exactly what will be removed.
     *
     * @return array<string, int>
     */
    public function previewCounts(): array
    {
        $counts = [];

        foreach (self::OPERATIONAL_TABLES as $table) {
            try {
                $counts[$table] = (int) DB::table($table)->count();
            } catch (\Throwable) {
                $counts[$table] = -1; // table missing in this environment
            }
        }

        return $counts;
    }

    /**
     * Returns counts of protected tables so the operator can verify
     * that master data will survive the reset.
     *
     * @return array<string, int>
     */
    public function protectedCounts(): array
    {
        $counts = [];

        foreach (self::PROTECTED_TABLES as $table) {
            try {
                $counts[$table] = (int) DB::table($table)->count();
            } catch (\Throwable) {
                $counts[$table] = -1;
            }
        }

        return $counts;
    }

    /**
     * Executes the operational reset inside a single DB transaction.
     *
     * Foreign-key checks are disabled for the duration of the deletes
     * (session-scoped in MySQL) and always restored in the finally block,
     * even on rollback or exception.
     *
     * Returns a stats array with:
     *   - deleted: table => rows_deleted
     *   - protected: table => rows_preserved
     *   - duration_ms: total execution time
     *   - success: bool
     *   - error: ?string
     *
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $this->assertProtectedTablesUntouched();

        $startedAt = microtime(true);
        $deleted   = [];
        $error     = null;

        // Disable FK checks at session level (survives inside transaction).
        // Always restored in the finally block.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::transaction(function () use (&$deleted) {
                foreach (self::OPERATIONAL_TABLES as $table) {
                    try {
                        $rows = DB::table($table)->delete();
                        $deleted[$table] = $rows;
                    } catch (\Throwable $e) {
                        // Re-throw so the outer transaction rolls back.
                        throw new \RuntimeException(
                            "Failed to clear table [{$table}]: " . $e->getMessage(),
                            previous: $e
                        );
                    }
                }
            });

            $this->logSuccess($deleted);

        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $this->logFailure($error);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return [
            'success'     => $error === null,
            'deleted'     => $deleted,
            'protected'   => $this->protectedCounts(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error'       => $error,
        ];
    }

    /**
     * Safety guard: throws if any protected table would somehow be in
     * the operational list (defends against future copy-paste errors).
     */
    private function assertProtectedTablesUntouched(): void
    {
        $intersection = array_intersect(self::OPERATIONAL_TABLES, self::PROTECTED_TABLES);

        if (!empty($intersection)) {
            throw new \LogicException(
                'Configuration error: the following tables appear in BOTH operational and protected lists: '
                . implode(', ', $intersection)
            );
        }
    }

    private function logSuccess(array $deleted): void
    {
        $total = array_sum($deleted);
        $nonZero = array_filter($deleted, fn($v) => $v > 0);

        Log::channel('daily')->info('[OperationalReset] Reset completed successfully.', [
            'total_rows_deleted' => $total,
            'tables_cleared'     => count($nonZero),
            'breakdown'          => $deleted,
            'executed_by'        => 'artisan:stock365:reset-operational',
            'environment'        => app()->environment(),
        ]);
    }

    private function logFailure(string $error): void
    {
        Log::channel('daily')->critical('[OperationalReset] Reset FAILED — transaction rolled back.', [
            'error'       => $error,
            'environment' => app()->environment(),
        ]);
    }
}
