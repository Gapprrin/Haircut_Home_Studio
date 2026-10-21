<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.login', [
            'registro' => $request->string('modo')->toString() === 'registro',
            'nombreSugerido' => 'Cliente '.random_int(1000, 9999),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        // se almacena los datos del login
        $credenciales = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // esta linea, aplica la función de normalización, para ciertas credenciales.
        $credenciales['email'] = $this->normalizarIdentificador($credenciales['email']);

        // Se realiza la autenticación del usuario.
        if (! Auth::attempt($credenciales)) {
            return back()
                ->withErrors(['email' => 'Usuario o contraseña incorrectos.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->inicioPara(Auth::user()))
            ->with('success', 'Sesión iniciada.');
    }

    public function register(Request $request): RedirectResponse
    {
        // guardamos en un array los datos del registro.
        $datos = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('USUARIO', 'email')],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'email.unique' => 'Ese correo ya está registrado. Entra con tu contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        // Creamos el usuario en la bbdd.

        $usuario = User::create([
            'nombre_usuario' => trim($datos['nombre'] ?? '') ?: 'Cliente '.random_int(1000, 9999),
            'email' => strtolower($datos['email']),
            'contrasenia' => Hash::make($datos['password']),
            'id_rol' => 3,
        ]);

        // dejamos su sesión iniciada.
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('success', 'Listo. Ya puedes reservar.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // Esta función normaliza el email del administrador y del peluquero, con el fin de 
    // ingresar más rápido durante el desarrollo.
    private function normalizarIdentificador(string $valor): string
    {
        $valor = trim($valor);

        return match (strtolower($valor)) {
            'admin' => 'admin@admin.cl',
            'ricardo' => 'ricardo@me.com',
            default => str_contains($valor, '@') ? $valor : $valor.'@haircut.cl',
        };
    }

    private function inicioPara(User $usuario): string
    {
        return match (strtolower((string) $usuario->rol?->rol)) {
            'Admin' => route('admin.servicios.index'),
            'Peluquero' => route('peluquero.dashboard'),
            'Cliente' => route('home'),
            'CliAdmin' => route('home'),
            default => route('home'),
        };
    }
}
