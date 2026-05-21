<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sede;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $users = User::with('sede')->paginate(15);

        return view('admin.settings.users.index', compact('users'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $sedes = Sede::where('activa', true)->get();

        return view('admin.settings.users.create', compact('sedes'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|in:boss,supervisor,operador',
            'sede_id'  => 'required_if:role,operador|exists:sedes,id|nullable',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role'     => $validated['role'],
            'sede_id'  => $validated['sede_id'] ?? null,
            'active'   => true,
        ]);

        $user->assignRole($validated['role']);

        ActivityLogger::log(
            'user.create',
            "Usuario creado: {$user->name} ({$user->role})",
            $user,
            [],
            ['name' => $user->name, 'role' => $user->role]
        );

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $sedes = Sede::where('activa', true)->get();

        return view('admin.settings.users.edit', compact('user', 'sedes'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->can('users.manage'), 403);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $user->id,
            'role'    => 'required|in:boss,supervisor,operador',
            'sede_id' => 'required_if:role,operador|exists:sedes,id|nullable',
            'active'  => 'boolean',
        ]);

        $oldRole = $user->role;
        $user->update($validated);

        if ($oldRole !== $validated['role']) {
            $user->syncRoles([$validated['role']]);
            ActivityLogger::log(
                'user.role_change',
                "Rol cambiado: {$user->name} | {$oldRole} → {$validated['role']}",
                $user,
                ['role' => $oldRole],
                ['role' => $validated['role']]
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
}
