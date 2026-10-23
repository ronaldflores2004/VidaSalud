<?php

namespace App\Http\Controllers\P2_GestionMedica\CU4_GestionarEspecialidades;

use App\Http\Controllers\Controller;
use App\Models\P2_GestionMedica\Especialidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU4 Gestionar especialidades.
 * No se permiten dos especialidades con el mismo nombre normalizado (RF04).
 */
class EspecialidadController extends Controller
{
    public function index(Request $request): View
    {
        $especialidades = Especialidad::withCount('medicos')
            ->when($request->q, fn ($q, $t) => $q->where('nombre', 'ilike', "%{$t}%"))
            ->orderBy('nombre')->paginate(10)->withQueryString();

        return view('P2_GestionMedica.CU4_GestionarEspecialidades.index', compact('especialidades'));
    }

    public function create(): View
    {
        return view('P2_GestionMedica.CU4_GestionarEspecialidades.form', ['especialidad' => new Especialidad(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Especialidad::create($this->validar($request));

        return redirect()->route('especialidades.index')->with('ok', 'Especialidad registrada correctamente.');
    }

    public function edit(Especialidad $especialidad): View
    {
        return view('P2_GestionMedica.CU4_GestionarEspecialidades.form', compact('especialidad'));
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
        return $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[\pL\s\-\.]+$/u',
                // RF04: no se repite el nombre normalizado (índice único sobre f_normalizar(nombre))
                function ($attr, $valor, $fail) use ($actual) {
                    if (Especialidad::nombreRepetido($valor, $actual?->id)) {
                        $fail('Ya existe una especialidad con ese nombre.');
                    }
                }],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
        ]);
    }
}
