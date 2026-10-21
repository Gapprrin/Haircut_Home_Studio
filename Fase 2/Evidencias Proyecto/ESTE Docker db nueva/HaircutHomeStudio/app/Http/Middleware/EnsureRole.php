<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $rol = strtolower((string) $usuario?->rol?->rol);

        if (! $usuario || ! in_array($rol, $roles, true)) {
            $destino = match ($rol) {
                'admin' => route('admin.servicios.index'),
                'peluquero' => route('peluquero.dashboard'),
                default => route('home'),
            };

            return redirect($destino)->with('error', 'No tienes permisos para acceder a esa sección.');
        }

        return $next($request);
    }
}
