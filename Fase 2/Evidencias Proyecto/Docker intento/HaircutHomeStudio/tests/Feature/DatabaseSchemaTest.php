<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_domain_names_and_relationships_are_preserved(): void
    {
        $usuario = User::factory()->create(['nombre' => 'Ana']);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Corte de damas',
            'duracion_min' => 60,
            'precio' => 15000,
        ]);
        $reserva = Reserva::create([
            'usuario_id' => $usuario->id,
            'servicio_id' => $servicio->id,
            'fecha' => now()->addDay()->toDateString(),
            'hora' => '10:30',
            'lugar' => 'salon',
        ]);

        $this->assertSame('Ana', $reserva->usuario->nombre);
        $this->assertSame('Cortes', $reserva->servicio->categoria->nombre);
        $this->assertTrue($usuario->reservas->contains($reserva));
    }
}
