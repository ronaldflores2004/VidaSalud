<?php

namespace App\Http\Controllers\P1_SeguridadAcceso\CU3_GestionarUsuarios;

use App\Http\Controllers\Controller;
use App\Models\P1_SeguridadAcceso\Rol;
use App\Models\P1_SeguridadAcceso\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * CU3 Gestionar usuarios: registrar, listar, buscar, modificar, asignar rol y activar/desactivar.
 * Solo accesible por el Administrador.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $usuarios = User::with('rol')
            ->when($request->q, function ($q, $texto) {
                $q->where(fn ($w) => $w->where('name', 'ilike', "%{$texto}%")
                    ->orWhere('username', 'ilike', "%{$texto}%")
                    ->orWhere('email', 'ilike', "%{$texto}%"));
            })
            ->when($request->rol, fn ($q, $rol) => $q->where('rol_id', $rol))
            ->orderBy('name')
            ->paginate(10)->withQueryString();

        return view('P1_SeguridadAcceso.CU3_GestionarUsuarios.index', ['usuarios' => $usuarios, 'roles' => Rol::orderBy('nombre')->get()]);
    }

    public function create(): View
    {
        return view('P1_SeguridadAcceso.CU3_GestionarUsuarios.form', ['usuario' => new User(['activo' => true]), 'roles' => Rol::orderBy('nombre')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        User::create($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario registrado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('P1_SeguridadAcceso.CU3_GestionarUsuarios.form', ['usuario' => $usuario, 'roles' => Rol::orderBy('nombre')->get()]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);
        if (empty($datos['password'])) {
            unset($datos['password']); // si no escribe una nueva contraseña se conserva la actual
        }
        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado correctamente.');
    }

    /** Activa o desactiva la cuenta. El administrador no puede desactivarse a sí mismo. */
    public function cambiarEstado(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puede desactivar su propia cuenta.');
        }
        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('ok', 'El usuario fue ' . ($usuario->activo ? 'activado' : 'desactivado') . '.');
    }

    private function validar(Request $request, ?User $usuario = null): array
    {
        $request->merge([
            'username' => Str::lower(trim((string) $request->username)),
            'email' => Str::lower(trim((string) $request->email)),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'username' => ['required', 'alpha_dash', 'min:4', 'max:40', Rule::unique('users')->ignore($usuario)],
            'email' => ['required', 'email', 'max:120', Rule::unique('users')->ignore($usuario)],
            'rol_id' => ['required', 'exists:roles,id'],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], [
            'name' => 'nombre completo', 'username' => 'nombre de usuario', 'email' => 'correo electrónico',
            'rol_id' => 'rol', 'password' => 'contraseña',
        ]);
    }
}
