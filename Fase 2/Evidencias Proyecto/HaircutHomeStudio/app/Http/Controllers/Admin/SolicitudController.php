<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\ReservationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SolicitudController extends Controller
{
    public function __construct(private readonly ReservationWorkflowService $workflow) {}

    public function index(): View
    {
        $this->workflow->actualizarRealizadas();

        return view('admin.solicitudes', [
            'solicitudes' => Reserva::query()
                ->with(['usuario', 'servicio', 'generacionesIa' => fn ($query) => $query->where('status', 'completed')->latest('id')])
                ->where('estado', 'pendiente')
                ->orderBy('fecha')
                ->orderBy('hora')
                ->get(),
        ]);
    }

    public function update(Request $request, Reserva $reserva): RedirectResponse
    {
        $datos = $request->validate([
            'accion' => ['required', Rule::in(['confirmar', 'rechazar'])],
        ]);
        $reserva->load(['usuario', 'servicio']);

        if ($reserva->estado !== 'pendiente') {
            return back()->with('error', 'La solicitud ya no está pendiente.');
        }

        $confirmar = $datos['accion'] === 'confirmar';
        $reserva->update(['estado' => $confirmar ? 'confirmada' : 'rechazada']);
        $asunto = $confirmar ? 'Reserva confirmada — Haircut Studio' : 'Reserva rechazada — Haircut Studio';
        $cuerpo = $confirmar
            ? "Hola {$reserva->usuario->nombre},\n\nTu reserva de {$reserva->servicio->nombre} el {$reserva->fecha->format('Y-m-d')} a las ".substr($reserva->hora, 0, 5)." fue CONFIRMADA.\n\nHaircut Studio"
            : "Hola {$reserva->usuario->nombre},\n\nTu solicitud de {$reserva->servicio->nombre} el {$reserva->fecha->format('Y-m-d')} no pudo confirmarse. El cupo quedó libre.\n\nHaircut Studio";

        try {
            Mail::raw($cuerpo, fn ($mensaje) => $mensaje->to($reserva->usuario->email)->subject($asunto));
        } catch (\Throwable) {
            // El cambio de estado no depende de que el servidor de correo esté configurado.
        }

        return back()->with('success', $confirmar
            ? 'Solicitud confirmada. Se intentó notificar al cliente por correo.'
            : 'Solicitud rechazada. El cupo quedó libre.');
    }
}
