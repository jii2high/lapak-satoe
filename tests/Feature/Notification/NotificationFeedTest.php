<?php

namespace Tests\Feature\Notification;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Notifications\OrderAwaitingBuyer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesOrders;
use Tests\TestCase;

class NotificationFeedTest extends TestCase
{
    use MakesOrders, RefreshDatabase;

    private function notify(User $user): void
    {
        $store = Store::factory()->open()->create();
        $product = Product::factory()->for($store)->create();
        $order = $this->orderFor($store, $product);

        $user->notify(new OrderAwaitingBuyer($order));
    }

    public function test_guests_cannot_read_the_feed()
    {
        $this->getJson('/notifications')->assertUnauthorized();
    }

    public function test_the_feed_only_carries_the_notifications_of_the_signed_in_user()
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();

        $this->notify($buyer);
        $this->notify($other);

        $this->actingAs($buyer)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.data.type', 'order_awaiting_buyer')
            ->assertJsonPath('notifications.0.read_at', null);
    }

    public function test_marking_one_notification_read_lowers_the_unread_count()
    {
        $buyer = User::factory()->create();
        $this->notify($buyer);

        $id = $buyer->notifications()->sole()->id;

        $this->actingAs($buyer)
            ->postJson('/notifications/read', ['id' => $id])
            ->assertOk();

        self::assertNotNull($buyer->notifications()->findOrFail($id)->read_at);

        $this->actingAs($buyer)
            ->getJson('/notifications')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_marking_everything_read_takes_no_id()
    {
        $buyer = User::factory()->create();
        $this->notify($buyer);
        $this->notify($buyer);

        $this->actingAs($buyer)->postJson('/notifications/read')->assertOk();

        $this->actingAs($buyer)
            ->getJson('/notifications')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_a_user_cannot_mark_someone_elses_notification_read()
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();
        $this->notify($other);

        $id = $other->notifications()->sole()->id;

        $this->actingAs($buyer)
            ->postJson('/notifications/read', ['id' => $id])
            ->assertOk();

        self::assertNull($other->notifications()->findOrFail($id)->read_at);
    }
}
