<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function index()
    {
        $this->authorize('boss');

        $sedes = Sede::paginate(15);
        return view('admin.settings.sedes.index', ['sedes' => $sedes]);
    }

    public function create()
    {
        $this->authorize('boss');
        return view('admin.settings.sedes.create');
    }

    public function store(Request $request)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:sedes',
            'ciudad' => 'required|string|max:255',
            'ubicacion' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        Sede::create($validated + ['activa' => true]);

        return redirect()->route('sedes.index')
            ->with('success', 'Sede creada exitosamente');
    }

    public function edit(Sede $sede)
    {
        $this->authorize('boss');
        return view('admin.settings.sedes.edit', ['sede' => $sede]);
    }

    public function update(Request $request, Sede $sede)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:sedes,nombre,' . $sede->id,
            'ciudad' => 'required|string|max:255',
            'ubicacion' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        $sede->update($validated);

        return redirect()->route('sedes.index')
            ->with('success', 'Sede actualizada exitosamente');
    }
}
