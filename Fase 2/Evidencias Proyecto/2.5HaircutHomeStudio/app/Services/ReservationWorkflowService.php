<?php

namespace App\Services;

use App\Models\Reserva;

class ReservationWorkflowService
{
    public function actualizarRealizadas(): int
    {
        $actualizadas = 0;

        Reserva::query()->where('estado', 'confirmada')->get()->each(function (Reserva $reserva) use (&$actualizadas): void {
            if ($reserva->fecha->setTimeFromTimeString($reserva->hora)->isPast()) {
                $reserva->update(['estado' => 'realizada']);
                $actualizadas++;
            }
        });

        return $actualizadas;
    }
}
