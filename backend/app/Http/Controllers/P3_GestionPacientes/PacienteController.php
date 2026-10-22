<?php

namespace App\Http\Controllers\P3_GestionPacientes;

use App\Http\Controllers\Controller;
use App\Models\P3_GestionPacientes\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('P3_GestionPacientes.pacientes.index', compact('pacientes'));
    }

    public function create(): View
    {
        return view('P3_GestionPacientes.pacientes.form', ['paciente' => new Paciente(['activo' => true, 'codigo' => Paciente::siguienteCodigo()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $paciente = Paciente::create($this->validar($request) + ['codigo' => Paciente::siguienteCodigo()]);

        return redirect()->route('pacientes.show', $paciente)->with('ok', "Paciente {$paciente->codigo} registrado correctamente.");
    }

    public function show(Paciente $paciente): View
    {
        $paciente->load(['citas.medico', 'citas.especialidad']);

        return view('P3_GestionPacientes.pacientes.show', compact('paciente'));
    }

    public function edit(Paciente $paciente): View
    {
        return view('P3_GestionPacientes.pacientes.form', compact('paciente'));
    }

    public function update(Request $request, Paciente $paciente): RedirectResponse
    {
        $paciente->update($this->validar($request, $paciente));

        return redirect()->route('pacientes.show', $paciente)->with('ok', 'Datos del paciente actualizados.');
    }

    /** Desactivación lógica: el paciente y sus citas permanecen en la base de datos (RN11). */
    public function cambiarEstado(Paciente $paciente): RedirectResponse
    {
        $paciente->update(['activo' => ! $paciente->activo]);

        return back()->with('ok', 'Paciente ' . ($paciente->activo ? 'activado' : 'desactivado') . '.');
    }

    private function validar(Request $request, ?Paciente $actual = null): array
    {
        $request->merge(['ci' => $request->filled('ci') ? strtoupper(trim($request->ci)) : null]);

        return $request->validate([
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
            'contacto_emergencia_nombre' => ['nullable', 'string', 'max:120'],
            'contacto_emergencia_telefono' => ['nullable', 'regex:/^[0-9+\s-]{7,20}$/'],
        ], [
            'ci.unique' => 'Ya existe un paciente registrado con ese CI.',
            'ci.regex' => 'El CI debe tener entre 5 y 12 dígitos (puede llevar complemento, ej. 1234567-1B).',
            'nombres.regex' => 'Los nombres solo pueden contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'telefono.regex' => 'Ingrese un teléfono válido (7 a 20 dígitos).',
            'contacto_emergencia_telefono.regex' => 'Ingrese un teléfono de emergencia válido.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ], ['fecha_nacimiento' => 'fecha de nacimiento', 'tipo_sangre' => 'tipo de sangre']);
    }
}
