<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

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
        $credenciales = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $credenciales['email'] = $this->normalizarIdentificador($credenciales['email']);

        if (! Auth::attempt($credenciales)) {
            return back()
                ->withErrors(['email' => 'Usuario o contraseña incorrectos.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->inicioPara($request->user()))
            ->with('success', 'Sesión iniciada.');
    }

    public function register(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:usuarios,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'email.unique' => 'Ese correo ya está registrado. Entra con tu contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $usuario = User::create([
            'nombre' => trim($datos['nombre'] ?? '') ?: 'Cliente '.random_int(1000, 9999),
            'email' => strtolower($datos['email']),
            'password' => Hash::make($datos['password']),
            'rol' => 'cliente',
            'es_invitado' => false,
            'origen' => 'email',
        ]);

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('reservas.create'))
            ->with('success', 'Listo. Ya puedes reservar.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

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
        return match ($usuario->rol) {
            'admin' => route('admin.servicios.index'),
            'peluquero' => route('peluquero.dashboard'),
            default => route('reservas.create'),
        };
    }
}
