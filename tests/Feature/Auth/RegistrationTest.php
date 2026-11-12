<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'username' => 'TestUser',
            'email' => 'Test@Example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('username', 'testuser')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));

        $this->assertSame('testuser', $user->display_name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame(Role::Buyer, $user->active_role);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role' => Role::Buyer->value,
        ]);

        Notification::assertNothingSent();
    }

    public function test_username_and_email_must_be_unique()
    {
        User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $this->post(route('register.store'), [
            'username' => 'TestUser',
            'email' => 'Test@Example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['username', 'email']);

        $this->assertGuest();
    }
}
