<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get(route('seller.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_buyers_can_not_access_seller_or_admin_pages()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('seller.dashboard'))->assertRedirect(route('home'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('home'));
    }

    public function test_sellers_can_access_the_seller_dashboard()
    {
        $user = User::factory()->withRole(Role::Seller, active: true)->create();

        $this->actingAs($user)->get(route('seller.dashboard'))->assertOk();
    }

    public function test_sellers_can_not_access_the_admin_dashboard()
    {
        $user = User::factory()->withRole(Role::Seller, active: true)->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('home'));
    }

    public function test_admins_can_access_the_admin_dashboard()
    {
        $user = User::factory()->withRole(Role::Admin, active: true)->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_a_role_that_is_owned_but_not_active_is_rejected()
    {
        $user = User::factory()->withRole(Role::Seller)->create();

        $this->actingAs($user)->get(route('seller.dashboard'))->assertRedirect(route('home'));
    }

    public function test_sellers_are_redirected_from_home_to_the_seller_dashboard()
    {
        $user = User::factory()->withRole(Role::Seller, active: true)->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('seller.dashboard'));
    }

    public function test_admins_are_redirected_from_home_to_the_admin_dashboard()
    {
        $user = User::factory()->withRole(Role::Admin, active: true)->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_role_prefixes_redirect_to_their_dashboards()
    {
        $seller = User::factory()->withRole(Role::Seller, active: true)->create();
        $admin = User::factory()->withRole(Role::Admin, active: true)->create();

        $this->actingAs($seller)
            ->get('/seller')
            ->assertRedirect('/seller/dashboard');
        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/dashboard');
    }
}
