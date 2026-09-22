<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CashMovement;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CashMovementControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private User $approver;
    private Sede $sede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Permission::firstOrCreate([
            'name' => 'cash_movements.create',
            'guard_name' => 'web',
        ]);

        Permission::firstOrCreate([
            'name' => 'cash_movements.approve',
            'guard_name' => 'web',
        ]);

        $this->sede = Sede::factory()->create();

        $this->operator = User::factory()->create([
            'sede_id' => $this->sede->id,
        ]);

        $this->operator->givePermissionTo('cash_movements.create');

        $this->approver = User::factory()->create([
            'sede_id' => null,
        ]);

        $this->approver->givePermissionTo('cash_movements.approve');
    }

    public function test_registro_guarda_datos_utiles_en_auditoria(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->operator)
            ->post(route('cash-movements.store'), [
                'type' => 'retiro',
                'amount' => 25.50,
                'motivo' => 'Prueba auditoria',
                'observaciones' => 'Movimiento de prueba',
                'attachment' => UploadedFile::fake()->image('comprobante.jpg'),
            ]);

        $response->assertRedirect();

        $movement = CashMovement::latest('id')->firstOrFail();

        $log = ActivityLog::where('action', 'caja.movimiento.registrado')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($movement->id, $log->model_id);
        $this->assertSame($this->sede->id, $log->sede_id);

        $this->assertSame([], $log->old_values ?? []);
        $this->assertSame('retiro', $log->new_values['tipo'] ?? null);
        $this->assertEquals(25.50, $log->new_values['monto'] ?? null);
        $this->assertSame('Prueba auditoria', $log->new_values['motivo'] ?? null);
        $this->assertSame('pendiente', $log->new_values['estado'] ?? null);
    }

    public function test_aprobacion_guarda_cambio_de_estado_y_sede_afectada(): void
    {
        $movement = CashMovement::create([
            'sede_id' => $this->sede->id,
            'user_id' => $this->operator->id,
            'cash_session_id' => null,
            'type' => 'retiro',
            'amount' => 40,
            'motivo' => 'Retiro de prueba',
            'attachment_path' => 'cash_movements/prueba.jpg',
            'status' => 'pendiente',
        ]);

        $response = $this->actingAs($this->approver)
            ->post(route('cash-movements.approve', $movement));

        $response->assertRedirect();

        $log = ActivityLog::where('action', 'caja.movimiento.aprobado')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('pendiente', $log->old_values['estado'] ?? null);
        $this->assertSame('aprobado', $log->new_values['estado'] ?? null);
        $this->assertSame($this->sede->id, $log->sede_id);
    }

    public function test_rechazo_guarda_estado_motivo_y_sede_afectada(): void
    {
        $movement = CashMovement::create([
            'sede_id' => $this->sede->id,
            'user_id' => $this->operator->id,
            'cash_session_id' => null,
            'type' => 'retiro',
            'amount' => 30,
            'motivo' => 'Retiro para rechazar',
            'attachment_path' => 'cash_movements/prueba.jpg',
            'status' => 'pendiente',
        ]);

        $response = $this->actingAs($this->approver)
            ->patch(route('cash-movements.reject', $movement), [
                'rejection_reason' => 'Comprobante incorrecto',
            ]);

        $response->assertRedirect();

        $log = ActivityLog::where('action', 'caja.movimiento.rechazado')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('pendiente', $log->old_values['estado'] ?? null);
        $this->assertSame('rechazado', $log->new_values['estado'] ?? null);
        $this->assertSame(
            'Comprobante incorrecto',
            $log->new_values['motivo_rechazo'] ?? null
        );
        $this->assertSame($this->sede->id, $log->sede_id);
    }
}
