<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class ProductionDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_creates_the_current_catalog_and_only_own_staff_accounts(): void
    {
        $this->install()->assertSuccessful();

        $this->assertDatabaseCount('USUARIO', 2);
        $this->assertDatabaseCount('RESERVA', 0);
        $this->assertDatabaseCount('DETALLE_RESERVA', 0);
        $this->assertDatabaseCount('PRODUCTO', 15);
        $this->assertDatabaseCount('AI_GENERATIONS', 0);
        $this->assertDatabaseCount('SERVICIO', 17);
        $this->assertDatabaseCount('ESTADO_RESERVA', 6);
        $this->assertDatabaseCount('TIPO_RESERVA', 2);
        $admin = User::where('id_rol', 1)->firstOrFail();
        $this->assertTrue(Hash::check('SecureAdminPassword2026!', $admin->contrasenia));
        $this->assertFalse($admin->esAdminImagen());
        $this->get(route('home'))->assertOk();
        $this->get(route('productos.index'))->assertOk();
        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'SecureAdminPassword2026!'])
            ->assertRedirect(route('staff.welcome'));
        $this->get(route('admin.servicios.index'))->assertOk();
        $this->get(route('admin.catalogo.index'))->assertOk();
    }

    public function test_installed_hairdresser_can_open_availability_and_reservation_panels(): void
    {
        $this->install()->assertSuccessful();
        $this->actingAs(User::where('id_rol', 2)->firstOrFail());
        $this->get(route('peluquero.disponibilidad.edit'))->assertOk();
        $this->get(route('peluquero.solicitudes.index'))->assertOk();
        $this->get(route('peluquero.dashboard'))->assertOk();
    }

    public function test_install_refuses_to_change_an_existing_database(): void
    {
        $this->install()->assertSuccessful();
        $this->artisan('studio:install')->expectsOutputToContain('La base ya contiene datos')->assertFailed();
        $this->assertDatabaseCount('USUARIO', 2);
        $this->assertDatabaseCount('SERVICIO', 17);
    }

    public function test_first_client_can_register_and_open_booking_and_simulator(): void
    {
        config()->set(['messaging.driver' => 'log', 'ai.enabled' => true, 'ai.provider' => 'fake']);
        $this->install()->assertSuccessful();
        $this->post(route('register.store'), [
            'nombre' => 'First Client',
            'email' => 'first-client@example.com',
            'password' => 'SecureClientPassword2026!',
            'password_confirmation' => 'SecureClientPassword2026!',
        ])->assertSessionHasNoErrors()->assertRedirect(route('home'));
        $this->assertDatabaseCount('USUARIO', 3);
        $this->get(route('reservas.create'))->assertOk();
        $this->get(route('ai.index'))->assertOk();
    }

    public function test_install_rejects_mismatched_passwords_without_writing_data(): void
    {
        $this->install('DifferentPassword2026!')->assertFailed();
        $this->assertDatabaseCount('USUARIO', 0);
        $this->assertDatabaseCount('ROL', 0);
        $this->assertDatabaseCount('SERVICIO', 0);
    }

    public function test_https_proxy_generates_secure_links(): void
    {
        config()->set('trustedproxy.proxies', '*');
        $this->withServerVariables([
            'REMOTE_ADDR' => '172.20.0.2',
            'HTTP_HOST' => 'haircuthomestudio.tech',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.10',
            'SERVER_PORT' => 80,
            'HTTPS' => 'off',
        ])->get('http://haircuthomestudio.tech/login')->assertOk()
            ->assertSee('https://haircuthomestudio.tech/login', false)
            ->assertDontSee('http://haircuthomestudio.tech/login', false);
    }

    private function install(string $confirmation = 'SecureAdminPassword2026!'): PendingCommand
    {
        return $this->artisan('studio:install')
            ->expectsQuestion('Nombre del administrador', 'Studio Admin')
            ->expectsQuestion('Correo del administrador', 'studio-admin@example.com')
            ->expectsQuestion('Contraseña del administrador (mínimo 12 caracteres)', 'SecureAdminPassword2026!')
            ->expectsQuestion('Repite la contraseña del administrador', $confirmation)
            ->expectsQuestion('Nombre del peluquero', 'Studio Hairdresser')
            ->expectsQuestion('Correo del peluquero', 'studio-hairdresser@example.com')
            ->expectsQuestion('Contraseña del peluquero (mínimo 12 caracteres)', 'SecureHairdresserPassword2026!')
            ->expectsQuestion('Repite la contraseña del peluquero', 'SecureHairdresserPassword2026!');
    }
}
