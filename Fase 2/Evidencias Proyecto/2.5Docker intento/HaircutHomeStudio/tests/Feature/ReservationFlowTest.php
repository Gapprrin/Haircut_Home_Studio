<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_reserved_block_cannot_be_booked_twice(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Corte',
            'duracion_min' => 60,
            'precio' => 15000,
            'activo' => true,
        ]);
        $primero = User::factory()->create();
        $segundo = User::factory()->create();
        $datos = [
            'servicio_id' => $servicio->id,
            'fecha' => '2026-09-11',
            'hora' => '10:30',
            'lugar' => 'salon',
        ];

        $this->actingAs($primero)->post(route('reservas.store'), $datos)
            ->assertRedirect(route('reservas.index'));
        $this->actingAs($segundo)->from(route('reservas.create'))->post(route('reservas.store'), $datos)
            ->assertRedirect(route('reservas.create'))
            ->assertSessionHas('error');

        $this->assertSame(1, Reserva::query()->count());
    }

    public function test_hairdresser_can_confirm_a_pending_request(): void
    {
        Mail::fake();
        $peluquero = User::factory()->create(['rol' => 'peluquero']);
        $cliente = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Corte',
            'duracion_min' => 60,
            'precio' => 15000,
        ]);
        $reserva = Reserva::create([
            'usuario_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'fecha' => now()->addDay()->toDateString(),
            'hora' => '10:30',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($peluquero)
            ->patch(route('peluquero.solicitudes.update', $reserva), ['accion' => 'confirmar'])
            ->assertSessionHas('success');

        $this->assertSame('confirmada', $reserva->fresh()->estado);
    }
}
