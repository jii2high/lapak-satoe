<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_is_displayed_with_masked_email()
    {
        $user = User::factory()->create([
            'display_name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('account.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/account')
                ->where('auth.user.display_name', 'Budi Santoso')
                ->where('auth.user.username', 'budi')
                ->where('auth.user.email_masked', 'b***@example.com')
                ->where('auth.user.email_verified', true)
                ->missing('auth.user.email')
                ->has('roles', 1)
                ->where('roles.0.value', Role::Buyer->value),
            );
    }

    public function test_display_name_can_be_updated()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('account.display-name.update'), [
                'display_name' => 'Nama Baru',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('Nama Baru', $user->refresh()->display_name);
    }

    public function test_username_is_lowercased_and_must_be_unique()
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create(['username' => 'budi']);

        $this->actingAs($user)
            ->patch(route('account.username.update'), [
                'username' => 'BudiBaru',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('budibaru', $user->refresh()->username);

        $this->actingAs($user)
            ->patch(route('account.username.update'), [
                'username' => 'Taken',
            ])
            ->assertSessionHasErrors('username');
    }

    public function test_email_is_lowercased_and_resets_verification()
    {
        $user = User::factory()->create([
            'email' => 'lama@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('account.email.update'), [
                'email' => 'Baru@Example.com',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('baru@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_password_can_be_updated()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_password_requires_the_current_password()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('account.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasErrors('current_password');
    }

    public function test_users_can_switch_to_a_role_they_own()
    {
        $user = User::factory()->withRole(Role::Seller)->create();

        $this->actingAs($user)
            ->post(route('account.switch-role'), ['role' => Role::Seller->value])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('seller.dashboard', absolute: false));

        $this->assertSame(Role::Seller, $user->refresh()->active_role);

        $this->actingAs($user)
            ->post(route('account.switch-role'), ['role' => Role::Buyer->value])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home', absolute: false));

        $this->assertSame(Role::Buyer, $user->refresh()->active_role);
    }

    public function test_users_can_not_switch_to_a_role_they_do_not_own()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.switch-role'), ['role' => Role::Admin->value])
            ->assertSessionHasErrors('role');

        $this->assertSame(Role::Buyer, $user->refresh()->active_role);
    }

    public function test_preference_page_is_displayed()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.preference'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('settings/preference'));
    }

    public function test_settings_prefix_redirects_to_the_account_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertRedirect('/settings/account');
    }

    public function test_legacy_account_urls_redirect_to_settings()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/akun')->assertRedirect('/settings/account');
        $this->actingAs($user)
            ->get('/akun/preferensi')
            ->assertRedirect('/settings/preference');
    }
}
