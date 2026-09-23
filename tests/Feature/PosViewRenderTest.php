<?php

namespace Tests\Feature;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regression test: ensure GET /pos renders the POS view without Blade errors.
 *
 * Added after a production 500 where Alpine.js @show-presentation-picker
 * was parsed by Blade as the @show directive, prematurely closing the
 * pos-content section and throwing "Cannot end a section without first
 * starting one." The POST /sales tests pass even with this bug because
 * they never render the view — so a dedicated GET render test is required.
 */
class PosViewRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\EnsureCashSessionOpen::class,
        ]);

        Permission::firstOrCreate(['name' => 'sales.create', 'guard_name' => 'web']);

        $bossRole = Role::firstOrCreate(['name' => 'boss', 'guard_name' => 'web']);
        $bossRole->syncPermissions(Permission::all());
    }

    #[Test]
    public function test_pos_view_renders_without_blade_error(): void
    {
        $sede = Sede::factory()->create();

        // A boss with sede_id passes the EnsureCashSessionOpen middleware (not an operator).
        // sede_id != null passes the abort_if check in SaleController::create().
        $boss = User::factory()->create(['role' => 'boss', 'sede_id' => $sede->id]);
        $boss->assignRole('boss');

        $response = $this->actingAs($boss)->get(route('pos.create'));

        // Any Blade section/compilation error produces a 500.
        $response->assertStatus(200);

        // Confirm the view actually rendered the POS Alpine component and the
        // presentations map injection — not just an empty or partial response.
        $response->assertSee('posCart', false);
        $response->assertSee('_presentationsMap', false);
    }

    #[Test]
    public function test_pos_create_blocked_without_sede(): void
    {
        $boss = User::factory()->create(['role' => 'boss', 'sede_id' => null]);
        $boss->assignRole('boss');

        $response = $this->actingAs($boss)->get(route('pos.create'));

        // Users without sede_id cannot use the POS.
        $response->assertStatus(403);
    }
}
