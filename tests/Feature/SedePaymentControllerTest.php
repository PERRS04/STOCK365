<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CashSession;
use App\Models\InventoryReceipt;
use App\Models\Provider;
use App\Models\ReceiptPaymentAllocation;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SedePaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function confirmacion_guarda_cambio_estado_monto_y_sede_en_auditoria(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Storage::fake('public');

        $sedeOrigen = Sede::factory()->create();
        $sedePagadora = Sede::factory()->create();

        $operator = User::factory()->create([
            'sede_id' => $sedePagadora->id,
        ]);

        $provider = Provider::create([
            'nombre' => 'Proveedor Auditoria',
            'activo' => true,
        ]);

        $receipt = InventoryReceipt::create([
            'sede_id' => $sedeOrigen->id,
            'user_id' => $operator->id,
            'provider_id' => $provider->id,
            'supplier_name' => $provider->nombre,
            'monto_pagado' => 100.00,
            'estado' => 'pendiente',
        ]);

        $allocation = ReceiptPaymentAllocation::create([
            'inventory_receipt_id' => $receipt->id,
            'source_type' => 'other_branch',
            'source_sede_id' => $sedePagadora->id,
            'amount' => 40.00,
            'status' => 'pending',
        ]);

        CashSession::create([
            'user_id' => $operator->id,
            'sede_id' => $sedePagadora->id,
            'opening_amount' => 200.00,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($operator)->post(
            route('sede-payments.confirm', $allocation),
            [
                'evidence_file' => UploadedFile::fake()->image('comprobante.jpg'),
                'notes' => 'Pago confirmado para auditoría',
            ]
        );

        $response->assertRedirect(route('sede-payments.pending'));

        $log = ActivityLog::where('action', 'sede_payment.confirmed')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($sedePagadora->id, $log->sede_id);
        $this->assertSame('pending', $log->old_values['estado'] ?? null);
        $this->assertSame('confirmed', $log->new_values['estado'] ?? null);
        $this->assertEquals(40.00, $log->new_values['monto'] ?? null);
        $this->assertSame($receipt->id, $log->new_values['recepcion_id'] ?? null);
        $this->assertNotNull($log->new_values['movimiento_caja_id'] ?? null);
    }
}