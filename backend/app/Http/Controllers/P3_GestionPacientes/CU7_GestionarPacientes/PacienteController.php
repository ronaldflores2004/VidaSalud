<?php

namespace App\Http\Controllers\P3_GestionPacientes\CU7_GestionarPacientes;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P3_GestionPacientes\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU7 Gestionar pacientes: registrar, listar, buscar por CI/nombre/apellido,
 * modificar, ver sus citas y desactivar sin perder el historial (RF02).
 */
class PacienteController extends Controller
{
    public function index(Request $request): View
    {
        $pacientes = Paciente::buscar($request->q)
            ->when($request->estado !== null && $request->estado !== '', fn ($q) => $q->where('activo', $request->estado === '1'))
            ->orderBy('apellidos')->orderBy('nombres')
            ->paginate(10)->withQueryString();

        return view('P3_GestionPacientes.CU7_GestionarPacientes.index', compact('pacientes'));
    }

    public function create(): View
    {
        return view('P3_GestionPacientes.CU7_GestionarPacientes.form', ['paciente' => new Paciente(['activo' => true, 'codigo' => Paciente::siguienteCodigo()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        // Normalización: los datos se reparten en pacientes, historias_clinicas y contactos_emergencia
        $paciente = DB::transaction(function () use ($datos) {
            $paciente = Paciente::create($datos['paciente'] + ['codigo' => Paciente::siguienteCodigo()]);
            $paciente->asegurarHistoria(['alergias' => $datos['alergias']]);
            if ($datos['contacto']) {
                $paciente->contactos()->create($datos['contacto']);
            }

            return $paciente;
        });
        Bitacora::registrar('CREAR', 'P3_GestionPacientes', $paciente, "Paciente {$paciente->codigo}");

        return redirect()->route('pacientes.show', $paciente)->with('ok', "Paciente {$paciente->codigo} registrado correctamente.");
    }

    public function show(Paciente $paciente): View
    {
        $paciente->load(['citas.medico', 'citas.especialidad', 'contactos', 'historia']);

        return view('P3_GestionPacientes.CU7_GestionarPacientes.show', compact('paciente'));
    }

    public function edit(Paciente $paciente): View
    {
        return view('P3_GestionPacientes.CU7_GestionarPacientes.form', ['paciente' => $paciente->load('contactos', 'historia')]);
    }

    public function update(Request $request, Paciente $paciente): RedirectResponse
    {
        $datos = $this->validar($request, $paciente);

        DB::transaction(function () use ($paciente, $datos) {
            $paciente->update($datos['paciente']);
            $paciente->asegurarHistoria()->update(['alergias' => $datos['alergias']]);
            $contacto = $paciente->contactos()->oldest('id')->first();
            if ($datos['contacto']) {
                $contacto ? $contacto->update($datos['contacto']) : $paciente->contactos()->create($datos['contacto']);
            }
        });

        return redirect()->route('pacientes.show', $paciente)->with('ok', 'Datos del paciente actualizados.');
    }

    /** Desactivación lógica: el paciente y sus citas permanecen en la base de datos (RN11). */
    public function cambiarEstado(Paciente $paciente): RedirectResponse
    {
        $paciente->update(['activo' => ! $paciente->activo]);

        return back()->with('ok', 'Paciente ' . ($paciente->activo ? 'activado' : 'desactivado') . '.');
    }

    /**
     * Valida el formulario y separa los datos por tabla:
     * ['paciente' => […], 'alergias' => …, 'contacto' => […] | null]
     */
    private function validar(Request $request, ?Paciente $actual = null): array
    {
        $request->merge(['ci' => $request->filled('ci') ? strtoupper(trim($request->ci)) : null,
            'acepta_notificaciones' => $request->boolean('acepta_notificaciones')]);

        $v = $request->validate([
            'ci' => ['nullable', 'regex:/^[0-9]{5,12}(-?[A-Z0-9]{1,3})?$/', Rule::unique('pacientes')->ignore($actual)],
            'nombres' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'apellidos' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'sexo' => ['required', 'in:M,F,O'],
            'telefono' => ['required', 'regex:/^[0-9+\s-]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'tipo_sangre' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'alergias' => ['nullable', 'string', 'max:255'],
            'acepta_notificaciones' => ['boolean'],
            // Contacto de emergencia: si se llena uno de los tres campos, se piden los tres
            'contacto_emergencia_nombre' => ['nullable', 'required_with:contacto_emergencia_parentesco,contacto_emergencia_telefono', 'string', 'max:120'],
            'contacto_emergencia_parentesco' => ['nullable', 'required_with:contacto_emergencia_nombre,contacto_emergencia_telefono', 'string', 'max:40'],
            'contacto_emergencia_telefono' => ['nullable', 'required_with:contacto_emergencia_nombre,contacto_emergencia_parentesco', 'regex:/^[0-9+\s-]{7,20}$/'],
        ], [
            'ci.unique' => 'Ya existe un paciente registrado con ese CI.',
            'ci.regex' => 'El CI debe tener entre 5 y 12 dígitos (puede llevar complemento, ej. 1234567-1B).',
            'nombres.regex' => 'Los nombres solo pueden contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'telefono.regex' => 'Ingrese un teléfono válido (7 a 20 dígitos).',
            'contacto_emergencia_telefono.regex' => 'Ingrese un teléfono de emergencia válido.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ], ['fecha_nacimiento' => 'fecha de nacimiento', 'tipo_sangre' => 'tipo de sangre',
            'contacto_emergencia_nombre' => 'nombre del contacto', 'contacto_emergencia_parentesco' => 'parentesco',
            'contacto_emergencia_telefono' => 'teléfono de emergencia']);

        return [
            'paciente' => collect($v)->only(['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'email',
                'direccion', 'tipo_sangre', 'acepta_notificaciones'])->all(),
            'alergias' => $v['alergias'] ?? null,
            'contacto' => ! empty($v['contacto_emergencia_nombre']) ? [
                'nombre' => $v['contacto_emergencia_nombre'],
                'parentesco' => $v['contacto_emergencia_parentesco'],
                'telefono' => $v['contacto_emergencia_telefono'],
            ] : null,
        ];
    }
}
