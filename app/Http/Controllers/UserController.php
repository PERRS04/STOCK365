<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sede;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('boss');

        $users = User::with('sede')->paginate(15);
        return view('admin.settings.users.index', ['users' => $users]);
    }

    public function create()
    {
        $this->authorize('boss');

        $sedes = Sede::where('activa', true)->get();
        return view('admin.settings.users.create', ['sedes' => $sedes]);
    }

    public function store(Request $request)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:boss,operator',
            'sede_id' => 'required_if:role,operator|exists:sedes,id|nullable',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'sede_id' => $validated['sede_id'] ?? null,
            'active' => true,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function edit(User $user)
    {
        $this->authorize('boss');

        $sedes = Sede::where('activa', true)->get();
        return view('admin.settings.users.edit', [
            'user' => $user,
            'sedes' => $sedes,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:boss,operator',
            'sede_id' => 'required_if:role,operator|exists:sedes,id|nullable',
            'active' => 'boolean',
        ]);

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function deactivate(User $user)
    {
        $this->authorize('boss');
        $user->update(['active' => false]);

        return redirect()->back()
            ->with('success', 'Usuario desactivado');
    }
}
