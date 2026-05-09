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
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // BOSS Routes
    Route::middleware('boss')->group(function () {
        // Products
        Route::resource('products', ProductController::class);

        // Inventory
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::post('/inventory/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');

        // Purchase Orders
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::patch('/purchase-orders/{order}/send', [PurchaseOrderController::class, 'markAsSent'])->name('purchase-orders.send');
        Route::post('/purchase-orders/{order}/receive', [PurchaseOrderController::class, 'receiveStock'])->name('purchase-orders.receive');

        // Cash Closings Approval
        Route::get('/cash-closings/pending', [CashClosingController::class, 'approvalsIndex'])->name('cash-closings.pending');
        Route::patch('/cash-closings/{closing}/approve', [CashClosingController::class, 'approve'])->name('cash-closings.approve');
        Route::patch('/cash-closings/{closing}/reject', [CashClosingController::class, 'reject'])->name('cash-closings.reject');

        // Reports
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/profitability', [ReportController::class, 'profitability'])->name('reports.profitability');
        Route::get('/reports/top-products', [ReportController::class, 'topProducts'])->name('reports.top-products');
        Route::get('/reports/comparison', [ReportController::class, 'comparison'])->name('reports.comparison');
        Route::get('/reports/export-sales', [ReportController::class, 'exportSales'])->name('reports.export-sales');

        // Settings
        Route::resource('sedes', SedeController::class);
        Route::resource('users', UserController::class);
        Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    });

    // OPERATOR Routes
    Route::middleware('operator')->group(function () {
        // Point of Sale
        Route::get('/pos', [SaleController::class, 'create'])->name('pos.create');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/history', [SaleController::class, 'history'])->name('sales.history');

        // Cash Closing
        Route::get('/cash-closing', [CashClosingController::class, 'create'])->name('cash-closing.create');
        Route::post('/cash-closing', [CashClosingController::class, 'store'])->name('cash-closing.store');
    });
});
