<?php

namespace App\Http\Controllers\GestionMedica;

use App\Http\Controllers\Controller;
use App\Models\Especialidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU4 Gestionar especialidades médicas.
 * No se permiten dos especialidades con el mismo nombre normalizado (RF04).
 */
class EspecialidadController extends Controller
{
    public function index(Request $request): View
    {
        $especialidades = Especialidad::withCount('medicos')
            ->when($request->q, fn ($q, $t) => $q->where('nombre', 'ilike', "%{$t}%"))
            ->orderBy('nombre')->paginate(10)->withQueryString();

        return view('especialidades.index', compact('especialidades'));
    }

    public function create(): View
    {
        return view('especialidades.form', ['especialidad' => new Especialidad(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Especialidad::create($this->validar($request));

        return redirect()->route('especialidades.index')->with('ok', 'Especialidad registrada correctamente.');
    }

    public function edit(Especialidad $especialidad): View
    {
        return view('especialidades.form', compact('especialidad'));
    }

    public function update(Request $request, Especialidad $especialidad): RedirectResponse
    {
        $especialidad->update($this->validar($request, $especialidad));

        return redirect()->route('especialidades.index')->with('ok', 'Especialidad actualizada correctamente.');
    }

    public function cambiarEstado(Especialidad $especialidad): RedirectResponse
    {
        $especialidad->update(['activo' => ! $especialidad->activo]);

        return back()->with('ok', 'Especialidad ' . ($especialidad->activo ? 'activada' : 'desactivada') . '.');
    }

    private function validar(Request $request, ?Especialidad $actual = null): array
    {
        // Se valida la unicidad sobre el nombre normalizado ("Pediatría" == "pediatria")
        $request->merge(['nombre_normalizado' => Especialidad::normalizar((string) $request->nombre)]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[\pL\s\-\.]+$/u'],
            'nombre_normalizado' => [Rule::unique('especialidades')->ignore($actual)],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre_normalizado.unique' => 'Ya existe una especialidad con ese nombre.',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
        ]);
        unset($datos['nombre_normalizado']);

        return $datos;
    }
}
