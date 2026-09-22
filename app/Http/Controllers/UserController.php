<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\User;
use App\Models\Sede;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $users = User::with(['sede', 'almacen'])->paginate(15);

        return view('admin.settings.users.index', compact('users'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $sedes     = Sede::where('activa', true)->get();
        $almacenes = Almacen::where('activo', true)->get();

        return view('admin.settings.users.create', compact('sedes', 'almacenes'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:8|confirmed',
            'role'          => 'required|in:boss,supervisor,operador',
            // Allow '' (from "Sin ubicación" radio) as well as null, sede, almacen
            'location_type' => ['nullable', Rule::in(['sede', 'almacen', ''])],
            'sede_id'       => 'nullable|exists:sedes,id',
            'almacen_id'    => 'nullable|exists:almacenes,id',
        ]);

        [$sedeId, $almacenId] = $this->resolveLocation($validated);

        if ($sedeId === false) {
            return back()->withErrors(['sede_id' => 'Debes seleccionar una sede.'])->withInput();
        }
        if ($almacenId === false) {
            return back()->withErrors(['almacen_id' => 'Debes seleccionar un depósito.'])->withInput();
        }

        if ($validated['role'] === 'operador' && $sedeId === null && $almacenId === null) {
            return back()->withErrors(['location_type' => 'Un operador debe tener una sede o depósito asignado.'])->withInput();
        }

        $user = User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => bcrypt($validated['password']),
            'role'       => $validated['role'],
            'sede_id'    => $sedeId,
            'almacen_id' => $almacenId,
            'active'     => true,
        ]);

        $user->assignRole($validated['role']);

        ActivityLogger::log(
            'user.create',
            "Usuario creado: {$user->name} ({$user->role})",
            $user,
            [],
            [
                'name'       => $user->name,
                'role'       => $user->role,
                'sede_id'    => $sedeId !== null ? (int) $sedeId : null,
                'almacen_id' => $almacenId !== null ? (int) $almacenId : null,
            ]
        );

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $sedes     = Sede::where('activa', true)->get();
        $almacenes = Almacen::where('activo', true)->get();

        return view('admin.settings.users.edit', compact('user', 'sedes', 'almacenes'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'role'          => 'required|in:boss,supervisor,operador',
            'location_type' => ['nullable', Rule::in(['sede', 'almacen', ''])],
            'sede_id'       => 'nullable|exists:sedes,id',
            'almacen_id'    => 'nullable|exists:almacenes,id',
            'active'        => 'boolean',
        ]);

        [$sedeId, $almacenId] = $this->resolveLocation($validated);

        if ($sedeId === false) {
            return back()->withErrors(['sede_id' => 'Debes seleccionar una sede.'])->withInput();
        }
        if ($almacenId === false) {
            return back()->withErrors(['almacen_id' => 'Debes seleccionar un depósito.'])->withInput();
        }

        if ($validated['role'] === 'operador' && $sedeId === null && $almacenId === null) {
            return back()->withErrors(['location_type' => 'Un operador debe tener una sede o depósito asignado.'])->withInput();
        }

        // Normalize to int for type-safe comparison — avoids 5 !== "5" false positives.
        $oldRole      = $user->role;
        $oldSedeId    = $user->sede_id    !== null ? (int) $user->sede_id    : null;
        $oldAlmacenId = $user->almacen_id !== null ? (int) $user->almacen_id : null;
        $newSedeId    = $sedeId    !== null ? (int) $sedeId    : null;
        $newAlmacenId = $almacenId !== null ? (int) $almacenId : null;

        $user->update([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role'       => $validated['role'],
            'sede_id'    => $sedeId,
            'almacen_id' => $almacenId,
            'active'     => $validated['active'] ?? $user->active,
        ]);

        $roleChanged     = $oldRole !== $validated['role'];
        $locationChanged = $oldSedeId !== $newSedeId || $oldAlmacenId !== $newAlmacenId;

        if ($roleChanged) {
            $user->syncRoles([$validated['role']]);
            ActivityLogger::log(
                'user.role_change',
                "Rol cambiado: {$user->name} | {$oldRole} → {$validated['role']}",
                $user,
                ['role' => $oldRole],
                ['role' => $validated['role']]
            );
        }

        if ($locationChanged) {
            ActivityLogger::log(
                'user.location_change',
                "Ubicación actualizada: {$user->name}",
                $user,
                ['sede_id' => $oldSedeId,    'almacen_id' => $oldAlmacenId],
                ['sede_id' => $newSedeId, 'almacen_id' => $newAlmacenId]
            );
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function deactivate(User $user)
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $user->update(['active' => false]);

        ActivityLogger::log(
            'user.deactivate',
            "Usuario desactivado: {$user->name}",
            $user,
            ['active' => true],
            ['active' => false]
        );

        return redirect()->back()->with('success', 'Usuario desactivado');
    }

    /**
     * Resolves location IDs from validated data.
     * Returns [sedeId, almacenId] where each is int|null.
     * Returns [false, null] if sede was selected but no id provided.
     * Returns [null, false] if almacen was selected but no id provided.
     */
    private function resolveLocation(array $validated): array
    {
        // Treat '' (Sin ubicación radio) identically to null
        $locationType = ($validated['location_type'] ?? null) ?: null;

        if ($locationType === 'sede') {
            return empty($validated['sede_id'])
                ? [false, null]
                : [$validated['sede_id'], null];
        }

        if ($locationType === 'almacen') {
            return empty($validated['almacen_id'])
                ? [null, false]
                : [null, $validated['almacen_id']];
        }

        return [null, null];
    }
}
