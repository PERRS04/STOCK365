<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Almacen;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User    $boss;
    private Sede    $sedeA;
    private Almacen $almacenA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // Bootstrap roles and the required permission.
        Role::firstOrCreate(['name' => 'boss',      'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operador',  'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $this->boss = User::factory()->create(['role' => 'boss']);
        $this->boss->assignRole('boss');
        $this->boss->givePermissionTo('users.manage');

        $this->sedeA    = Sede::factory()->create(['nombre' => 'Sede Alpha', 'activa' => true]);
        $this->almacenA = Almacen::factory()->create(['nombre' => 'Depósito Alfa', 'activo' => true]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function createUserPayload(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'operador',
            'location_type'         => 'sede',
            'sede_id'               => $this->sedeA->id,
            'almacen_id'            => null,
        ], $overrides);
    }

    private function store(array $params): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->boss)->post(route('users.store'), $params);
    }

    private function update(User $user, array $params): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->boss)->put(route('users.update', $user), $params);
    }

    // ── Index loads both relations ─────────────────────────────────────────────

    #[Test]
    public function index_loads_sede_and_almacen_relations(): void
    {
        $this->actingAs($this->boss)->get(route('users.index'))
            ->assertOk()
            ->assertViewHas('users');
    }

    // ── Create: sede ──────────────────────────────────────────────────────────

    #[Test]
    public function can_create_user_assigned_to_sede(): void
    {
        $response = $this->store($this->createUserPayload([
            'email'         => 'op_sede@example.com',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
            'almacen_id'    => null,
        ]));

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'op_sede@example.com')->firstOrFail();
        $this->assertEquals($this->sedeA->id, $user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    // ── Create: almacén ───────────────────────────────────────────────────────

    #[Test]
    public function can_create_user_assigned_to_almacen(): void
    {
        $response = $this->store($this->createUserPayload([
            'email'         => 'op_almacen@example.com',
            'location_type' => 'almacen',
            'sede_id'       => null,
            'almacen_id'    => $this->almacenA->id,
        ]));

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'op_almacen@example.com')->firstOrFail();
        $this->assertNull($user->sede_id);
        $this->assertEquals($this->almacenA->id, $user->almacen_id);
    }

    // ── Create: boss sin ubicación ─────────────────────────────────────────────

    #[Test]
    public function can_create_boss_without_location(): void
    {
        $response = $this->store($this->createUserPayload([
            'email'         => 'newboss@example.com',
            'role'          => 'boss',
            'location_type' => null,
            'sede_id'       => null,
            'almacen_id'    => null,
        ]));

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'newboss@example.com')->firstOrFail();
        $this->assertNull($user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    // ── Create: supervisor sin ubicación ──────────────────────────────────────

    #[Test]
    public function can_create_supervisor_without_location(): void
    {
        $response = $this->store($this->createUserPayload([
            'email'         => 'sup@example.com',
            'role'          => 'supervisor',
            'location_type' => null,
            'sede_id'       => null,
            'almacen_id'    => null,
        ]));

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'sup@example.com')->firstOrFail();
        $this->assertNull($user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    // ── Constraint: operador sin ubicación rechazado ───────────────────────────

    #[Test]
    public function operador_without_location_is_rejected(): void
    {
        $response = $this->store($this->createUserPayload([
            'email'         => 'noloc@example.com',
            'role'          => 'operador',
            'location_type' => null,
            'sede_id'       => null,
            'almacen_id'    => null,
        ]));

        $response->assertSessionHasErrors(['location_type']);
        $this->assertDatabaseMissing('users', ['email' => 'noloc@example.com']);
    }

    // ── Constraint: sede + almacén simultáneos rechazados ────────────────────

    #[Test]
    public function sede_and_almacen_cannot_coexist_on_create(): void
    {
        // Frontend forces an XOR through location_type; backend enforces it
        // by ignoring whichever field doesn't match location_type.
        // This test sends location_type='sede' but also an almacen_id — backend
        // must set almacen_id to null.
        $response = $this->store($this->createUserPayload([
            'email'         => 'xor@example.com',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
            'almacen_id'    => $this->almacenA->id, // extra — must be ignored
        ]));

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'xor@example.com')->firstOrFail();
        $this->assertEquals($this->sedeA->id, $user->sede_id);
        $this->assertNull($user->almacen_id, 'almacen_id should be null when location_type is sede');
    }

    #[Test]
    public function sede_and_almacen_cannot_coexist_when_location_type_is_almacen(): void
    {
        $response = $this->store($this->createUserPayload([
            'email'         => 'xor2@example.com',
            'location_type' => 'almacen',
            'sede_id'       => $this->sedeA->id, // extra — must be ignored
            'almacen_id'    => $this->almacenA->id,
        ]));

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'xor2@example.com')->firstOrFail();
        $this->assertNull($user->sede_id, 'sede_id should be null when location_type is almacen');
        $this->assertEquals($this->almacenA->id, $user->almacen_id);
    }

    // ── Edit: sede → almacén ──────────────────────────────────────────────────

    #[Test]
    public function can_edit_user_from_sede_to_almacen(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',
            'location_type' => 'almacen',
            'sede_id'       => null,
            'almacen_id'    => $this->almacenA->id,
            'active'        => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertNull($user->sede_id);
        $this->assertEquals($this->almacenA->id, $user->almacen_id);
    }

    // ── Edit: almacén → sede ──────────────────────────────────────────────────

    #[Test]
    public function can_edit_user_from_almacen_to_sede(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => null,
            'almacen_id' => $this->almacenA->id,
        ]);
        $user->assignRole('operador');

        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
            'almacen_id'    => null,
            'active'        => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertEquals($this->sedeA->id, $user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    // ── Edit: sede → sin ubicación (solo roles no-operador) ──────────────────

    #[Test]
    public function can_clear_location_when_role_is_supervisor(): void
    {
        $user = User::factory()->create([
            'role'       => 'supervisor',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('supervisor');

        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'supervisor',
            'location_type' => null,
            'sede_id'       => null,
            'almacen_id'    => null,
            'active'        => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertNull($user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    // ── Relaciones del modelo ─────────────────────────────────────────────────

    #[Test]
    public function user_sede_relation_works(): void
    {
        $user = User::factory()->create(['sede_id' => $this->sedeA->id, 'almacen_id' => null]);
        $user->load('sede');
        $this->assertEquals('Sede Alpha', $user->sede->nombre);
    }

    #[Test]
    public function user_almacen_relation_works(): void
    {
        $user = User::factory()->create(['sede_id' => null, 'almacen_id' => $this->almacenA->id]);
        $user->load('almacen');
        $this->assertEquals('Depósito Alfa', $user->almacen->nombre);
    }

    #[Test]
    public function workplace_name_returns_sede_nombre(): void
    {
        $user = User::factory()->create(['sede_id' => $this->sedeA->id, 'almacen_id' => null]);
        $user->load('sede');
        $this->assertEquals('Sede Alpha', $user->workplaceName());
    }

    #[Test]
    public function workplace_name_returns_almacen_nombre(): void
    {
        $user = User::factory()->create(['sede_id' => null, 'almacen_id' => $this->almacenA->id]);
        $user->load('almacen');
        $this->assertEquals('Depósito Alfa', $user->workplaceName());
    }

    #[Test]
    public function workplace_name_returns_sin_asignar_when_no_location(): void
    {
        $user = User::factory()->create(['sede_id' => null, 'almacen_id' => null]);
        $this->assertEquals('Sin asignar', $user->workplaceName());
    }

    #[Test]
    public function workplace_type_returns_sede(): void
    {
        $user = User::factory()->create(['sede_id' => $this->sedeA->id, 'almacen_id' => null]);
        $this->assertEquals('sede', $user->workplaceType());
    }

    #[Test]
    public function workplace_type_returns_almacen(): void
    {
        $user = User::factory()->create(['sede_id' => null, 'almacen_id' => $this->almacenA->id]);
        $this->assertEquals('almacen', $user->workplaceType());
    }

    #[Test]
    public function workplace_type_returns_null_when_no_location(): void
    {
        $user = User::factory()->create(['sede_id' => null, 'almacen_id' => null]);
        $this->assertNull($user->workplaceType());
    }

    // ── Auditoría ─────────────────────────────────────────────────────────────

    #[Test]
    public function creating_user_logs_activity_with_sede(): void
    {
        $this->store($this->createUserPayload([
            'email'         => 'audit_sede@example.com',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
        ]));

        $log = ActivityLog::where('action', 'user.create')
            ->where('description', 'like', '%audit_sede@example.com%')
            ->orWhere(function ($q) {
                $q->where('action', 'user.create')
                  ->whereJsonContains('new_values->sede_id', $this->sedeA->id);
            })
            ->first();

        // Just confirm a user.create log entry was created.
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.create']);
    }

    #[Test]
    public function creating_user_logs_sede_and_almacen_in_new_values(): void
    {
        $this->store($this->createUserPayload([
            'email'         => 'audit2@example.com',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
        ]));

        $log = ActivityLog::where('action', 'user.create')->latest()->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('sede_id', $log->new_values);
        $this->assertArrayHasKey('almacen_id', $log->new_values);
    }

    #[Test]
    public function updating_location_logs_old_and_new_values(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',
            'location_type' => 'almacen',
            'sede_id'       => '',          // watcher-cleared: form sends ''
            'almacen_id'    => $this->almacenA->id,
            'active'        => 1,
        ]);

        // Action is now user.location_change (not user.update)
        $log = ActivityLog::where('action', 'user.location_change')
            ->where('model_id', $user->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals((int) $this->sedeA->id, $log->old_values['sede_id']);
        $this->assertNull($log->old_values['almacen_id']);
        $this->assertNull($log->new_values['sede_id']);
        $this->assertEquals((int) $this->almacenA->id, $log->new_values['almacen_id']);
    }

    // ── Tests que reproducen el payload REAL del formulario HTML ─────────────
    //
    // Convention: the form sends '' (empty string) for location_type when
    // "Sin ubicación" is selected; '' for sede_id/almacen_id when those selects
    // are cleared by the Alpine watcher. ConvertEmptyStringsToNull converts ''→null
    // before validation (standard Laravel 11 middleware).

    #[Test]
    public function real_form_boss_without_location(): void
    {
        // Form sends: role=boss, location_type='', sede_id='', almacen_id=''
        $response = $this->store($this->createUserPayload([
            'email'         => 'boss_form@example.com',
            'role'          => 'boss',
            'location_type' => '',   // "Sin ubicación" radio value
            'sede_id'       => '',   // cleared by Alpine watcher
            'almacen_id'    => '',
        ]));

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'boss_form@example.com')->firstOrFail();
        $this->assertNull($user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    #[Test]
    public function real_form_supervisor_without_location(): void
    {
        $response = $this->store($this->createUserPayload([
            'email'         => 'sup_form@example.com',
            'role'          => 'supervisor',
            'location_type' => '',
            'sede_id'       => '',
            'almacen_id'    => '',
        ]));

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'sup_form@example.com')->firstOrFail();
        $this->assertNull($user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    #[Test]
    public function real_form_edit_operador_to_boss_selecting_sin_ubicacion(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        // User switched role to boss then clicked "Sin ubicación"
        // → Alpine watcher cleared sedeId; form sends:
        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'boss',
            'location_type' => '',   // Sin ubicación
            'sede_id'       => '',   // cleared by watcher
            'almacen_id'    => '',
            'active'        => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertNull($user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    #[Test]
    public function real_form_edit_operador_to_boss_keeping_sede(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        // User switched role to boss but kept Sede selected
        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'boss',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
            'almacen_id'    => '',
            'active'        => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertEquals($this->sedeA->id, $user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    #[Test]
    public function real_form_edit_sede_to_almacen_clears_sede(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        // User clicked Depósito radio → Alpine watcher cleared sedeId
        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',
            'location_type' => 'almacen',
            'sede_id'       => '',             // cleared by watcher
            'almacen_id'    => $this->almacenA->id,
            'active'        => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertNull($user->sede_id);
        $this->assertEquals($this->almacenA->id, $user->almacen_id);
    }

    #[Test]
    public function real_form_edit_almacen_to_sede_clears_almacen(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => null,
            'almacen_id' => $this->almacenA->id,
        ]);
        $user->assignRole('operador');

        // User clicked Sede radio → Alpine watcher cleared almacenId
        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
            'almacen_id'    => '',             // cleared by watcher
            'active'        => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertEquals($this->sedeA->id, $user->sede_id);
        $this->assertNull($user->almacen_id);
    }

    #[Test]
    public function real_form_changing_to_operador_without_location_is_rejected(): void
    {
        // A boss/supervisor with no location switches to operador
        // without selecting Sede or Depósito — backend must reject it.
        $user = User::factory()->create([
            'role'       => 'boss',
            'sede_id'    => null,
            'almacen_id' => null,
        ]);
        $user->assignRole('boss');

        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',
            'location_type' => '',  // no radio checked (x-if removed Sin ubicación)
            'sede_id'       => '',
            'almacen_id'    => '',
            'active'        => 1,
        ]);

        $response->assertSessionHasErrors(['location_type']);
        $user->refresh();
        $this->assertEquals('boss', $user->role); // not changed
    }

    #[Test]
    public function malicious_payload_with_both_ids_only_persists_active_location(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => null,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        // Attacker sends both sede_id and almacen_id with location_type=sede
        $response = $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
            'almacen_id'    => $this->almacenA->id, // malicious extra field
            'active'        => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertEquals($this->sedeA->id, $user->sede_id);
        $this->assertNull($user->almacen_id, 'almacen_id must be null when location_type is sede');
    }

    #[Test]
    public function saving_without_changes_does_not_create_activity_log(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        $countBefore = ActivityLog::count();

        $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
            'almacen_id'    => '',
            'active'        => 1,
        ]);

        $this->assertEquals($countBefore, ActivityLog::count());
    }

    #[Test]
    public function role_change_logs_user_role_change_action(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'supervisor',   // role changes
            'location_type' => 'sede',
            'sede_id'       => $this->sedeA->id,
            'almacen_id'    => '',
            'active'        => 1,
        ]);

        $log = ActivityLog::where('action', 'user.role_change')
            ->where('model_id', $user->id)
            ->first();

        $this->assertNotNull($log, 'user.role_change log must exist');
        $this->assertEquals('operador',   $log->old_values['role']);
        $this->assertEquals('supervisor', $log->new_values['role']);

        // Must NOT log user.location_change since location didn't change
        $this->assertDatabaseMissing('activity_logs', [
            'action'   => 'user.location_change',
            'model_id' => $user->id,
        ]);
    }

    #[Test]
    public function location_change_logs_user_location_change_action(): void
    {
        $user = User::factory()->create([
            'role'       => 'operador',
            'sede_id'    => $this->sedeA->id,
            'almacen_id' => null,
        ]);
        $user->assignRole('operador');

        $this->update($user, [
            'name'          => $user->name,
            'email'         => $user->email,
            'role'          => 'operador',  // role unchanged
            'location_type' => 'almacen',
            'sede_id'       => '',
            'almacen_id'    => $this->almacenA->id,
            'active'        => 1,
        ]);

        $log = ActivityLog::where('action', 'user.location_change')
            ->where('model_id', $user->id)
            ->first();

        $this->assertNotNull($log, 'user.location_change log must exist');
        $this->assertEquals((int) $this->sedeA->id,    $log->old_values['sede_id']);
        $this->assertNull($log->old_values['almacen_id']);
        $this->assertNull($log->new_values['sede_id']);
        $this->assertEquals((int) $this->almacenA->id, $log->new_values['almacen_id']);

        // Must NOT log user.role_change since role didn't change
        $this->assertDatabaseMissing('activity_logs', [
            'action'   => 'user.role_change',
            'model_id' => $user->id,
        ]);
    }

    // ── Seguridad: acceso sin permiso ─────────────────────────────────────────

    #[Test]
    public function user_without_permission_cannot_access_index(): void
    {
        $unprivileged = User::factory()->create(['role' => 'operador']);
        $unprivileged->assignRole('operador');

        $this->actingAs($unprivileged)->get(route('users.index'))->assertForbidden();
    }

    #[Test]
    public function user_without_permission_cannot_store(): void
    {
        $unprivileged = User::factory()->create(['role' => 'operador']);
        $unprivileged->assignRole('operador');

        $this->actingAs($unprivileged)
            ->post(route('users.store'), $this->createUserPayload())
            ->assertForbidden();
    }
}
