<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Temporal: sin control de roles mientras se prueba el flujo.
        // Más adelante se vuelve a validar $roles contra $usuario->rol.
        if (! $request->user()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
