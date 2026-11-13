<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Enums\StoreStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRole(Role::Admin, active: true)->create([
            'display_name' => 'Root Admin',
            'username' => 'rootadmin',
            'email' => 'root@example.com',
        ]);
    }

    public function test_guests_are_redirected_to_login()
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->delete(route('admin.users.destroy', User::factory()->create()))
            ->assertRedirect(route('login'));
    }

    public function test_non_admins_can_not_manage_users()
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('home'));

        $this->actingAs($buyer)
            ->delete(route('admin.users.destroy', $buyer))
            ->assertRedirect(route('home'));
    }

    public function test_the_list_masks_emails_and_flags_the_signed_in_admin()
    {
        $admin = $this->admin();

        User::factory()->create([
            'username' => 'budisantoso',
            'email' => 'budi@gmail.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users')
                ->has('users.data', 2)
                ->where('users.data.0.username', 'budisantoso')
                ->where('users.data.0.email', 'b***@gmail.com')
                ->where('users.data.0.is_self', false)
                ->where('users.data.1.is_self', true)
                ->where('users.data.1.is_admin', true)
                ->where('filters.q', ''));
    }

    public function test_the_search_filter_matches_username_display_name_and_email()
    {
        $admin = $this->admin();

        User::factory()->create([
            'display_name' => 'Budi Santoso',
            'username' => 'budisantoso',
            'email' => 'budi@gmail.com',
        ]);
        User::factory()->create([
            'display_name' => 'Siti Aminah',
            'username' => 'sitiminah',
            'email' => 'siti@yahoo.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'budisan']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.username', 'budisantoso'));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Aminah']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.username', 'sitiminah'));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'yahoo.com']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.username', 'sitiminah'));
    }

    public function test_an_admin_can_change_an_email_and_the_verification_is_reset()
    {
        $admin = $this->admin();
        $user = User::factory()->create(['email' => 'lama@example.com']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.email.update', $user), [
                'email' => 'Baru@Example.com',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'baru@example.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_a_taken_email_is_rejected()
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.email.update', $user), [
                'email' => $admin->email,
            ])
            ->assertSessionHasErrors('email');

        $this->assertNotSame($admin->email, $user->refresh()->email);
    }

    public function test_an_admin_can_not_change_their_own_email()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.users.email.update', $admin), [
                'email' => 'pindah@example.com',
            ])
            ->assertForbidden();

        $this->assertSame('root@example.com', $admin->refresh()->email);
    }

    public function test_an_admin_can_send_a_password_reset_link()
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.reset', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_an_admin_can_not_reset_their_own_password()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.reset', $admin))
            ->assertForbidden();
    }

    public function test_deactivating_a_user_disconnects_their_sessions()
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->sessionFor($user);
        $this->sessionFor($other);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.toggle', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($user->refresh()->is_active);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['user_id' => $other->id]);
    }

    public function test_a_deactivated_user_can_be_reactivated()
    {
        $admin = $this->admin();
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.toggle', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($user->refresh()->is_active);
    }

    public function test_the_signed_in_admin_can_not_be_toggled_or_deleted()
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.toggle', $admin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_another_admin_account_is_protected()
    {
        $admin = $this->admin();
        $other = User::factory()->withRole(Role::Admin, active: true)->create();

        $this->actingAs($admin)
            ->post(route('admin.users.toggle', $other))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $other))
            ->assertForbidden();

        $this->assertFalse($other->refresh()->trashed());
    }

    public function test_deleting_is_blocked_while_the_user_has_an_active_order()
    {
        $admin = $this->admin();
        $buyer = User::factory()->create();
        $store = Store::factory()->open()->create();

        Order::factory()->create([
            'buyer_id' => $buyer->id,
            'store_id' => $store->id,
            'status' => OrderStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $buyer))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($buyer->refresh()->trashed());
    }

    public function test_deleting_is_blocked_while_the_store_has_an_active_order()
    {
        $admin = $this->admin();
        $seller = User::factory()->withRole(Role::Seller, active: true)->create();
        $store = Store::factory()->open()->for($seller)->create();

        Order::factory()->create([
            'store_id' => $store->id,
            'status' => OrderStatus::AwaitingBuyer,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $seller))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($seller->refresh()->trashed());
    }

    public function test_deleting_a_seller_closes_the_store_and_hides_its_products()
    {
        $admin = $this->admin();
        $seller = User::factory()->withRole(Role::Seller, active: true)->create([
            'username' => 'sari',
            'email' => 'sari@example.com',
        ]);
        $store = Store::factory()->open()->for($seller)->create();
        $product = Product::factory()->for($store)->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $seller))
            ->assertRedirect(route('admin.users.index'));

        $trashed = User::withTrashed()->findOrFail($seller->id);

        $this->assertTrue($trashed->trashed());
        $this->assertSame("deleted_{$seller->id}_sari", $trashed->username);
        $this->assertSame("deleted_{$seller->id}_sari@example.com", $trashed->email);
        $this->assertSame(StoreStatus::Closed, $store->refresh()->status);
        $this->assertSame(0, Product::query()->where('store_id', $store->id)->count());
        $this->assertNotNull(
            Product::withTrashed()->findOrFail($product->id)->deleted_at,
        );
    }

    public function test_a_deleted_account_can_reuse_its_number_and_is_hidden_from_the_list()
    {
        $admin = $this->admin();
        $user = User::factory()->create(['username' => 'sari']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
    }

    private function sessionFor(User $user): void
    {
        DB::table('sessions')->insert([
            'id' => 'session-'.$user->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
    }
}
