<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CashClosingController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\CashSessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryReceiptController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchasesController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\PurchaseSuggestionsController;
use App\Http\Controllers\FinancesController;
use App\Http\Controllers\BulkInventoryController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Cash Sessions (operators) ─────────────────────────────────────────────
    Route::get('/caja/abrir',  [CashSessionController::class, 'create'])->name('cash-session.create');
    Route::post('/caja/abrir', [CashSessionController::class, 'store'])->name('cash-session.store');
    Route::get('/caja/estado', [CashSessionController::class, 'status'])->name('cash-session.status');
    Route::get('/caja/historial', [CashSessionController::class, 'index'])->name('cash-sessions.index');
    Route::patch('/caja/{cashSession}/forzar-cierre', [CashSessionController::class, 'forceClose'])->name('cash-sessions.force-close');

    // ── Point of Sale & Sales (operators + supervisors + boss) ──────────────
    Route::get('/pos', [SaleController::class, 'create'])->middleware('cash.session')->name('pos.create');
    Route::post('/sales', [SaleController::class, 'store'])->middleware('cash.session')->name('sales.store');
    Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');

    // ── Cash Closing (operators submit; supervisors + boss approve) ──────────
    Route::get('/cash-closing', [CashClosingController::class, 'create'])->name('cash-closing.create');
    Route::post('/cash-closing', [CashClosingController::class, 'store'])->name('cash-closing.store');
    Route::get('/cash-closings/pending', [CashClosingController::class, 'approvalsIndex'])->name('cash-closings.pending');
    Route::patch('/cash-closings/{closing}/approve', [CashClosingController::class, 'approve'])->name('cash-closings.approve');
    Route::patch('/cash-closings/{closing}/reject', [CashClosingController::class, 'reject'])->name('cash-closings.reject');

    // ── Products (boss = full CRUD; supervisor = view only) ──────────────────
    Route::resource('products', ProductController::class);

    // ── Inventory (supervisors + boss) ────────────────────────────────────────
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');

    // ── Bulk Inventory Load (boss only) ───────────────────────────────────────
    Route::get('/inventory/carga-masiva', [BulkInventoryController::class, 'index'])->name('inventory.bulk-load');
    Route::post('/inventory/carga-masiva', [BulkInventoryController::class, 'save'])->name('inventory.bulk-save');

    // ── Purchase Orders (boss only) ───────────────────────────────────────────
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::patch('/purchase-orders/{order}/send', [PurchaseOrderController::class, 'markAsSent'])->name('purchase-orders.send');
    Route::post('/purchase-orders/{order}/receive', [PurchaseOrderController::class, 'receiveStock'])->name('purchase-orders.receive');

    // ── Reports (supervisors + boss view; boss export) ────────────────────────
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/profitability', [ReportController::class, 'profitability'])->name('reports.profitability');
    Route::get('/reports/top-products', [ReportController::class, 'topProducts'])->name('reports.top-products');
    Route::get('/reports/comparison', [ReportController::class, 'comparison'])->name('reports.comparison');
    Route::get('/reports/export-sales', [ReportController::class, 'exportSales'])->name('reports.export-sales');

    // ── Activity Log (supervisors + boss view; boss export) ───────────────────
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/export', [\App\Http\Controllers\ActivityLogController::class, 'export'])->name('activity-logs.export');

    // ── Inventory Receipts (operators register; boss/supervisor approve) ────────
    Route::get('/recepciones/nueva', [InventoryReceiptController::class, 'create'])->name('inventory-receipts.create');
    Route::post('/recepciones',      [InventoryReceiptController::class, 'store'])->name('inventory-receipts.store');
    Route::get('/recepciones',       [InventoryReceiptController::class, 'index'])->name('inventory-receipts.index');
    Route::get('/recepciones/{receipt}',          [InventoryReceiptController::class, 'show'])->name('inventory-receipts.show');
    Route::post('/recepciones/{receipt}/aprobar', [InventoryReceiptController::class, 'approve'])->name('inventory-receipts.approve');
    Route::patch('/recepciones/{receipt}/rechazar',[InventoryReceiptController::class, 'reject'])->name('inventory-receipts.reject');

    // ── Suppliers / Proveedores ───────────────────────────────────────────────
    Route::resource('suppliers', SupplierController::class);

    // ── Purchases (approved receipts ledger) ──────────────────────────────────
    Route::get('/purchases', [PurchasesController::class, 'index'])->name('purchases.index');

    // ── Kardex ────────────────────────────────────────────────────────────────
    Route::get('/kardex', [KardexController::class, 'index'])->name('kardex.index');

    // ── Stock Transfers ───────────────────────────────────────────────────────
    Route::get('/transfers',                        [StockTransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/nueva',                  [StockTransferController::class, 'create'])->name('transfers.create');
    Route::post('/transfers',                       [StockTransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}',             [StockTransferController::class, 'show'])->name('transfers.show');
    Route::post('/transfers/{transfer}/aprobar',    [StockTransferController::class, 'approve'])->name('transfers.approve');
    Route::patch('/transfers/{transfer}/rechazar',  [StockTransferController::class, 'reject'])->name('transfers.reject');

    // ── Purchase Suggestions ──────────────────────────────────────────────────
    Route::get('/purchase-suggestions', [PurchaseSuggestionsController::class, 'index'])->name('purchase-suggestions.index');

    // ── Finances ──────────────────────────────────────────────────────────────
    Route::get('/finances', [FinancesController::class, 'index'])->name('finances.index');

    // ── Configuration (boss only) ─────────────────────────────────────────────
    Route::resource('sedes', SedeController::class);
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
});
