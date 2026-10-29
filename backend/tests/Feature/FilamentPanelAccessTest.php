<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Evita depender de archivos compilados
        // por Vite durante las pruebas HTTP.
        $this->withoutVite();
    }

    public function test_authorized_roles_can_access_admin_panel(): void
    {
        $panel = Filament::getPanel('admin');

        foreach (['admin', 'technician', 'observer'] as $role) {
            $user = User::factory()->make([
                'role' => $role,
            ]);

            $this->assertInstanceOf(
                FilamentUser::class,
                $user
            );

            $this->assertTrue(
                $user->canAccessPanel($panel),
                "El rol {$role} debería tener acceso al panel."
            );
        }
    }

    public function test_unsupported_roles_cannot_access_admin_panel(): void
    {
        $panel = Filament::getPanel('admin');

        foreach (['guest', 'suspended', '', null] as $role) {
            $user = User::factory()->make([
                'role' => $role,
            ]);

            $this->assertFalse(
                $user->canAccessPanel($panel)
            );
        }
    }

    public function test_users_are_not_automatically_authorized_for_other_panels(): void
    {
        $otherPanel = Panel::make()->id('other');

        foreach (['admin', 'technician', 'observer'] as $role) {
            $user = User::factory()->make([
                'role' => $role,
            ]);

            $this->assertFalse(
                $user->canAccessPanel($otherPanel)
            );
        }
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_unsupported_role_receives_forbidden_response(): void
    {
        $user = User::factory()->create([
            'role' => 'suspended',
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_observer_can_open_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'observer',
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }
}
