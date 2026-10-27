<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffWelcomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $usuario = $request->user();
        $rol = strtolower((string) ($usuario?->rol?->rol ?? ''));

        return view('staff.welcome', [
            'nombre' => $usuario?->nombre_usuario ?: 'usuario',
            'rol' => $rol,
        ]);
    }
}
