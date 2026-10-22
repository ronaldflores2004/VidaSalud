<?php

namespace App\Http\Controllers\P3_GestionPacientes;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\Bitacora;
use App\Models\P3_GestionPacientes\Paciente;
use App\Models\P1_SeguridadAcceso\Rol;
use App\Models\P1_SeguridadAcceso\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * CU8 Registrarse como paciente: el paciente ingresa sus datos desde la web y obtiene una cuenta.
 * Si la recepción ya lo había registrado (mismo CI y sin cuenta), se vincula la cuenta a ese
 * registro en lugar de duplicarlo (RN01).
 */
class RegistroPacienteController extends Controller
{
    public function mostrar(): View
    {
        return view('P3_GestionPacientes.registro');
    }

    public function registrar(Request $request): RedirectResponse
    {
        $request->merge([
            'ci' => strtoupper(trim((string) $request->ci)),
            'email' => Str::lower(trim((string) $request->email)),
        ]);

        $existente = Paciente::where('ci', $request->ci)->first();

        $datos = $request->validate([
            'ci' => ['required', 'regex:/^[0-9]{5,12}(-?[A-Z0-9]{1,3})?$/',
                // Si ya existe un paciente con ese CI y ya tiene cuenta, no puede registrarse de nuevo
                function ($attr, $valor, $fail) use ($existente) {
                    if ($existente && $existente->user_id) {
                        $fail('Ya existe una cuenta para ese CI. Inicie sesión o acérquese a recepción.');
                    }
                }],
            'nombres' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'apellidos' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'sexo' => ['required', 'in:M,F,O'],
            'telefono' => ['required', 'regex:/^[0-9+\s-]{7,20}$/'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'acepta' => ['accepted'],
        ], [
            'ci.regex' => 'El CI debe tener entre 5 y 12 dígitos (puede llevar complemento, ej. 1234567-1B).',
            'nombres.regex' => 'Los nombres solo pueden contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
            'telefono.regex' => 'Ingrese un teléfono válido (7 a 20 dígitos).',
            'email.unique' => 'Ese correo ya tiene una cuenta. Inicie sesión.',
            'acepta.accepted' => 'Debe aceptar el uso de sus datos para la atención médica.',
        ], ['fecha_nacimiento' => 'fecha de nacimiento', 'password' => 'contraseña']);

        $usuario = DB::transaction(function () use ($datos, $existente) {
            $usuario = User::create([
                'name' => $datos['nombres'] . ' ' . $datos['apellidos'],
                'username' => Str::lower($datos['ci']),
                'email' => $datos['email'],
                'password' => $datos['password'],
                'rol_id' => Rol::where('nombre', Rol::PACIENTE)->value('id'),
            ]);

            $perfil = collect($datos)->only(['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'email'])->all();

            if ($existente) {
                $existente->update($perfil + ['user_id' => $usuario->id, 'activo' => true]);
            } else {
                Paciente::create($perfil + ['codigo' => Paciente::siguienteCodigo(), 'user_id' => $usuario->id]);
            }

            return $usuario;
        });

        Bitacora::registrar('REGISTRO_WEB', 'P3_GestionPacientes', $usuario, 'El paciente se registró desde la web', $usuario->id);
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('ok', 'Bienvenido(a). Su cuenta fue creada; ya puede reservar una ficha.');
    }
}
