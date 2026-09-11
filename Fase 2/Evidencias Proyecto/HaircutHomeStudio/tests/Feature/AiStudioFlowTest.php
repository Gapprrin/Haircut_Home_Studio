<?php

namespace Tests\Feature;

use App\Models\AiGeneration;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiStudioFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('ai.enabled', true);
        config()->set('ai.provider', 'fake');
        config()->set('ai.process_sync', true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_client_sees_active_cut_and_color_services_but_not_treatments(): void
    {
        $cliente = User::factory()->create();
        $corte = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $color = Categoria::create(['nombre' => 'Color', 'slug' => 'color']);
        $tratamiento = Categoria::create(['nombre' => 'Tratamientos', 'slug' => 'tratamientos']);
        $this->service($corte, 'Corte de damas');
        $this->service($color, 'Balayage');
        $this->service($tratamiento, 'Botox capilar');

        $this->actingAs($cliente)
            ->get(route('ai.index'))
            ->assertOk()
            ->assertSee('Simulador de estilo')
            ->assertSee('Corte de damas')
            ->assertSee('Balayage')
            ->assertDontSee('Botox capilar')
            ->assertSee('<strong>5</strong>', false)
            ->assertSee('disponibles');
    }

    public function test_unlimited_admin_can_open_studio_and_bypasses_request_throttle(): void
    {
        $admin = User::factory()->create([
            'nombre' => 'Admin Test Imagen',
            'rol' => 'admin',
            'ai_sin_limite' => true,
        ]);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $this->service($categoria, 'Corte de damas');

        $this->actingAs($admin)
            ->get(route('ai.index'))
            ->assertOk()
            ->assertSee('Cuenta de prueba sin límite de imágenes');

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($admin)
                ->post(route('ai.store'), [])
                ->assertRedirect()
                ->assertSessionHasErrors('photo');
        }
    }

    public function test_client_can_generate_a_private_preview_and_other_users_cannot_see_it(): void
    {
        $cliente = User::factory()->create();
        $otroCliente = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Color', 'slug' => 'color']);
        $servicio = $this->service($categoria, 'Balayage');

        $response = $this->actingAs($cliente)->post(route('ai.store'), [
            'photo' => $this->portrait(),
            'servicio_id' => $servicio->id,
            'consent' => '1',
        ]);

        $generation = AiGeneration::query()->firstOrFail();
        $response->assertRedirect(route('ai.show', $generation));
        $this->assertSame('completed', $generation->status);
        $this->assertSame($servicio->id, $generation->servicio_id);
        Storage::disk('local')->assertExists($generation->input_path);
        Storage::disk('local')->assertExists($generation->output_path);

        $this->actingAs($cliente)
            ->get(route('ai.image', [$generation, 'output']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($otroCliente)
            ->get(route('ai.image', [$generation, 'output']))
            ->assertNotFound();
    }

    public function test_identical_photo_and_service_reuse_the_result_without_consuming_quota(): void
    {
        $cliente = User::factory()->create();
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = $this->service($categoria, 'Corte de damas');
        $payload = [
            'photo' => $this->portrait(),
            'servicio_id' => $servicio->id,
            'consent' => '1',
        ];

        $this->actingAs($cliente)->post(route('ai.store'), $payload)->assertRedirect();
        $first = AiGeneration::query()->firstOrFail();

        $this->actingAs($cliente)->post(route('ai.store'), [
            'photo' => $this->portrait(),
            'servicio_id' => $servicio->id,
            'consent' => '1',
        ])->assertRedirect(route('ai.show', $first));

        $this->assertSame(1, AiGeneration::query()->count());
    }

    public function test_completed_preview_can_be_attached_to_a_reservation_and_seen_by_hairdresser(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $servicio = Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Corte de damas',
            'duracion_min' => 60,
            'precio' => 15000,
            'activo' => true,
        ]);
        $cliente = User::factory()->create();
        $peluquero = User::factory()->create(['rol' => 'peluquero']);
        $image = file_get_contents(public_path('img/collage/03.jpg'));
        Storage::disk('local')->put('ai/input/attached.jpg', $image);
        Storage::disk('local')->put('ai/output/attached.jpg', $image);
        $generation = AiGeneration::create([
            'usuario_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'preset' => 'service:'.$servicio->id,
            'input_path' => 'ai/input/attached.jpg',
            'output_path' => 'ai/output/attached.jpg',
            'input_hash' => hash('sha256', $image),
            'status' => 'completed',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'completed_at' => now(),
            'expires_at' => now()->addHours(72),
        ]);

        $this->actingAs($cliente)->post(route('reservas.store'), [
            'servicio_id' => $servicio->id,
            'fecha' => '2026-09-11',
            'hora' => '10:30',
            'lugar' => 'salon',
            'ai_generation_id' => $generation->id,
        ])->assertRedirect(route('reservas.index'));

        $generation->refresh();
        $this->assertNotNull($generation->reserva_id);
        $this->assertTrue($generation->expires_at->greaterThan('2026-09-12 11:30:00'));
        $this->actingAs($peluquero)
            ->get(route('ai.image', [$generation, 'output']))
            ->assertOk();
    }

    public function test_preview_cannot_be_attached_to_a_different_service(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        Configuracion::create(['hora_inicio' => '10:30:00', 'hora_fin' => '19:30:00', 'dias_atencion' => '2,3,4,5,6']);
        $categoria = Categoria::create(['nombre' => 'Cortes', 'slug' => 'corte']);
        $simulatedService = $this->service($categoria, 'Corte de damas');
        $differentService = $this->service($categoria, 'Peinado');
        $cliente = User::factory()->create();
        $image = file_get_contents(public_path('img/collage/03.jpg'));
        Storage::disk('local')->put('ai/input/mismatch.jpg', $image);
        Storage::disk('local')->put('ai/output/mismatch.jpg', $image);
        $generation = AiGeneration::create([
            'usuario_id' => $cliente->id,
            'servicio_id' => $simulatedService->id,
            'preset' => 'service:'.$simulatedService->id,
            'input_path' => 'ai/input/mismatch.jpg',
            'output_path' => 'ai/output/mismatch.jpg',
            'input_hash' => hash('sha256', $image),
            'status' => 'completed',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'completed_at' => now(),
            'expires_at' => now()->addHours(72),
        ]);

        $this->actingAs($cliente)->from(route('reservas.create'))->post(route('reservas.store'), [
            'servicio_id' => $differentService->id,
            'fecha' => '2026-09-11',
            'hora' => '10:30',
            'lugar' => 'salon',
            'ai_generation_id' => $generation->id,
        ])->assertRedirect(route('reservas.create'))
            ->assertSessionHasErrors('ai_generation_id');

        $this->assertNull($generation->fresh()->reserva_id);
        $this->assertDatabaseCount('reservas', 0);
    }

    private function portrait(): UploadedFile
    {
        return new UploadedFile(
            public_path('img/collage/03.jpg'),
            'portrait.jpg',
            'image/jpeg',
            null,
            true,
        );
    }

    private function service(Categoria $categoria, string $nombre): Servicio
    {
        return Servicio::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'duracion_min' => 60,
            'precio' => 15000,
            'activo' => true,
        ]);
    }
}
