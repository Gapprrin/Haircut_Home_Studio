<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_log_in_with_email(): void
    {
        $this->seed();

        $response = $this->post(route('login.store'), [
            'email' => 'admin@admin.cl',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('admin.servicios.index'));
        $this->assertAuthenticatedAs(User::query()->where('email', 'admin@admin.cl')->first());
    }

    public function test_client_cannot_enter_catalog_administration(): void
    {
        $cliente = User::factory()->create(['rol' => 'cliente']);

        $this->actingAs($cliente)
            ->get(route('admin.servicios.index'))
            ->assertRedirect(route('home'));
    }
}
