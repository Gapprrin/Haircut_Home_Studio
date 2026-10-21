<?php

namespace App\Services;

use App\Models\Reserva;

class ReservationWorkflowService
{
    public function actualizarRealizadas(): int
    {
        $actualizadas = 0;

        // Usamos whereHas para filtrar a través de la relación del estado
        Reserva::query()->whereHas('estadoReserva', function ($query) {
            $query->where('estado_reserva', 'Agendada');
        })->get()->each(function (Reserva $reserva) use (&$actualizadas): void {
            if ($reserva->fecha->setTimeFromTimeString($reserva->hora)->isPast()) {
                $reserva->update(['id_estado_reserva' => 1]);
                $actualizadas++;
            }
        });

        return $actualizadas;
    }
}
