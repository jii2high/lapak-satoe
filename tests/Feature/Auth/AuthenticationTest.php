<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_username()
    {
        $user = User::factory()->create(['username' => 'budi']);

        $response = $this->post(route('login.store'), [
            'login' => 'budi',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_users_can_authenticate_using_email()
    {
        $user = User::factory()->create(['email' => 'budi@example.com']);

        $response = $this->post(route('login.store'), [
            'login' => 'Budi@Example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_sellers_are_redirected_to_the_seller_dashboard_after_login()
    {
        $user = User::factory()->withRole(Role::Seller, active: true)->create();

        $response = $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('seller.dashboard', absolute: false));
    }

    public function test_admins_are_redirected_to_the_admin_dashboard_after_login()
    {
        $user = User::factory()->withRole(Role::Admin, active: true)->create();

        $response = $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_login_redirects_to_the_intended_page()
    {
        $user = User::factory()->create();

        $this->get(route('account.edit'))->assertRedirect(route('login'));

        $response = $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('account.edit', absolute: false));
    }

    public function test_inactive_users_can_not_authenticate()
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'login' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_users_are_rate_limited()
    {
        $user = User::factory()->create(['username' => 'budi']);

        RateLimiter::increment(md5('login'.implode('|', ['budi', '127.0.0.1'])), amount: 5);

        $response = $this->post(route('login.store'), [
            'login' => 'budi',
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
    }
}
