<?php

namespace App\Http\Controllers\P2_GestionMedica\CU5_GestionarMedicos;

use App\Http\Controllers\Controller;
use App\Models\P2_GestionMedica\Especialidad;
use App\Models\P2_GestionMedica\Medico;
use App\Models\P1_SeguridadAcceso\Rol;
use App\Models\P1_SeguridadAcceso\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU5 Gestionar médicos: registrar, listar, buscar por nombre o especialidad,
 * modificar, asignar especialidades y activar/desactivar (RF03).
 */
class MedicoController extends Controller
{
    public function index(Request $request): View
    {
        $medicos = Medico::with('especialidades')
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w
                ->where('nombres', 'ilike', "%{$t}%")
                ->orWhere('apellidos', 'ilike', "%{$t}%")
                ->orWhere('codigo', 'ilike', "%{$t}%")))
            ->when($request->especialidad, fn ($q, $id) => $q->whereHas('especialidades', fn ($e) => $e->whereKey($id)))
            ->orderBy('apellidos')->paginate(10)->withQueryString();

        return view('P2_GestionMedica.CU5_GestionarMedicos.index', [
            'medicos' => $medicos,
            'especialidades' => Especialidad::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Medico(['activo' => true, 'codigo' => Medico::siguienteCodigo()]));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        DB::transaction(function () use ($datos) {
            $medico = Medico::create($datos + ['codigo' => Medico::siguienteCodigo()]);
            $medico->especialidades()->sync($datos['especialidades']);
        });

        return redirect()->route('medicos.index')->with('ok', 'Médico registrado correctamente.');
    }

    public function show(Medico $medico): View
    {
        $medico->load('especialidades', 'horarios', 'usuario');

        return view('P2_GestionMedica.CU5_GestionarMedicos.show', compact('medico'));
    }

    public function edit(Medico $medico): View
    {
        return $this->formulario($medico->load('especialidades'));
    }

    public function update(Request $request, Medico $medico): RedirectResponse
    {
        $datos = $this->validar($request, $medico);

        try {
            DB::transaction(function () use ($medico, $datos) {
                $medico->update($datos);
                $medico->especialidades()->sync($datos['especialidades']);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // La cita guarda (médico, especialidad) como clave foránea compuesta: no se puede
            // quitar una especialidad con la que el médico ya tiene citas registradas.
            if ($e->getCode() === '23503') {
                return back()->withInput()->with('error', 'No se puede quitar una especialidad con la que el médico ya tiene citas registradas.');
            }
            throw $e;
        }

        return redirect()->route('medicos.show', $medico)->with('ok', 'Datos del médico actualizados.');
    }

    /** Un médico inactivo no recibe nuevas citas, pero conserva su historial (RN04, RN11). */
    public function cambiarEstado(Medico $medico): RedirectResponse
    {
        $medico->update(['activo' => ! $medico->activo]);

        return back()->with('ok', 'Médico ' . ($medico->activo ? 'activado' : 'desactivado') . '.');
    }

    private function formulario(Medico $medico): View
    {
        return view('P2_GestionMedica.CU5_GestionarMedicos.form', [
            'medico' => $medico,
            'especialidades' => Especialidad::where('activo', true)->orderBy('nombre')->get(),
            // Cuentas con rol Médico que todavía no están vinculadas a otro médico
            'cuentas' => User::whereHas('rol', fn ($r) => $r->where('nombre', Rol::MEDICO))
                ->where(fn ($q) => $q->whereDoesntHave('medico')->orWhere('id', $medico->user_id))
                ->orderBy('name')->get(),
        ]);
    }

    private function validar(Request $request, ?Medico $actual = null): array
    {
        return $request->validate([
            'registro_profesional' => ['required', 'string', 'max:30', Rule::unique('medicos')->ignore($actual)],
            'nombres' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'apellidos' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'telefono' => ['required', 'regex:/^[0-9+\s-]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'especialidades' => ['required', 'array', 'min:1'],
            'especialidades.*' => ['integer', 'exists:especialidades,id'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('medicos')->ignore($actual)],
        ], [
            'nombres.regex' => 'Los nombres solo pueden contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'telefono.regex' => 'Ingrese un teléfono válido (7 a 20 dígitos).',
            'especialidades.required' => 'Seleccione al menos una especialidad.',
        ]);
    }
}
