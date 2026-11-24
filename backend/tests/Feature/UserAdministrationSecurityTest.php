<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\UserAdministrationService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserAdministrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function service(): UserAdministrationService
    {
        return app(UserAdministrationService::class);
    }

    public function test_only_administrators_can_manage_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $observer = User::factory()->create(['role' => 'observer']);
        $technician = User::factory()->create(['role' => 'technician']);

        foreach ([$observer, $technician] as $actor) {
            $this->assertFalse(Gate::forUser($actor)->allows('viewAny', User::class));
            $this->assertFalse(Gate::forUser($actor)->allows('create', User::class));
            $this->assertFalse(Gate::forUser($actor)->allows('update', $admin));
            $this->assertFalse(Gate::forUser($actor)->allows('delete', $admin));
        }

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
    }

    public function test_bulk_deletion_is_denied_even_to_an_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertFalse(Gate::forUser($admin)->allows('deleteAny', User::class));
        $this->assertFalse(Gate::forUser($admin)->allows('forceDeleteAny', User::class));
    }

    public function test_administrator_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'admin']);

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));

        try {
            $this->service()->delete($admin, $admin);
            $this->fail('Self deletion should have been rejected.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('users', ['id' => $admin->id]);
        }
    }

    public function test_last_administrator_cannot_be_demoted_or_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));

        try {
            $this->service()->update($admin, $admin, ['role' => 'observer']);
            $this->fail('Demoting the last administrator should have failed.');
        } catch (ValidationException) {
            $this->assertSame('admin', $admin->fresh()->role);
        }

        try {
            $this->service()->delete($admin, $admin);
            $this->fail('Deleting the last administrator should have failed.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('users', 1);
        }
    }

    public function test_admin_cannot_change_own_role_even_when_there_is_another_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'admin']);

        $this->expectException(ValidationException::class);
        $this->service()->update($admin, $admin, ['role' => 'technician']);
    }

    public function test_can_demote_another_admin_but_never_the_last_one(): void
    {
        $first = User::factory()->create(['role' => 'admin']);
        $second = User::factory()->create(['role' => 'admin']);

        $this->service()->update($first, $second, ['role' => 'observer']);

        $this->assertSame('observer', $second->fresh()->role);
        $this->assertSame(1, User::query()->where('role', 'admin')->count());

        $this->expectException(ValidationException::class);
        $this->service()->update($first, $first, ['role' => 'technician']);
    }

    public function test_can_delete_another_administrator_only_when_one_will_remain(): void
    {
        $first = User::factory()->create(['role' => 'admin']);
        $second = User::factory()->create(['role' => 'admin']);

        $this->assertTrue(Gate::forUser($first)->allows('delete', $second));
        $this->service()->delete($first, $second);

        $this->assertDatabaseMissing('users', ['id' => $second->id]);
        $this->assertSame(1, User::query()->where('role', 'admin')->count());
    }

    public function test_non_admin_cannot_modify_or_delete_a_user_using_service(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $observer = User::factory()->create(['role' => 'observer']);
        $target = User::factory()->create(['role' => 'technician']);

        try {
            $this->service()->update($observer, $target, ['role' => 'admin']);
            $this->fail('The update should have been rejected.');
        } catch (AuthorizationException) {
            $this->assertSame('technician', $target->fresh()->role);
        }

        try {
            $this->service()->delete($observer, $target);
            $this->fail('The deletion should have been rejected.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('users', ['id' => $target->id]);
        }
    }

    public function test_password_is_hashed_once_by_model_cast_and_not_changed_when_omitted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'observer']);
        $originalHash = $target->password;

        $this->service()->update($admin, $target, ['name' => 'Nombre actualizado']);

        $this->assertSame($originalHash, $target->fresh()->password);

        $this->service()->update($admin, $target, ['password' => 'clave-de-prueba-segura-123']);

        $this->assertTrue(Hash::check('clave-de-prueba-segura-123', $target->fresh()->password));
        $this->assertNotSame('clave-de-prueba-segura-123', $target->fresh()->password);
    }

    public function test_admin_can_update_own_profile_without_changing_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->service()->update($admin, $admin, [
            'name' => 'Nuevo nombre',
            'password' => '',
        ]);

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame('Nuevo nombre', $admin->fresh()->name);
    }

    public function test_invalid_role_cannot_be_written_by_service(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'observer']);

        $this->expectException(ValidationException::class);
        $this->service()->update($admin, $target, ['role' => 'root']);
    }

    public function test_user_resource_is_visible_only_to_an_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $observer = User::factory()->create(['role' => 'observer']);

        $this->actingAs($admin);
        $this->assertTrue(UserResource::canViewAny());

        $this->actingAs($observer);
        $this->assertFalse(UserResource::canViewAny());
    }
}
