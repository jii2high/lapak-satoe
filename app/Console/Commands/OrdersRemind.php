<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\OrderReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class OrdersRemind extends Command
{
    protected $signature = 'orders:remind';

    protected $description = 'Kirim pengingat 12 jam sekali ke seller untuk order unconfirmed dan pending';

    public function handle(): int
    {
        $sent = 0;

        Order::query()
            ->whereIn('status', [OrderStatus::Unconfirmed->value, OrderStatus::Pending->value])
            ->whereNull('reminded_at')
            ->where('remind_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function (Collection $orders) use (&$sent): void {
                foreach ($orders as $order) {
                    // Klaim atomik: worker kedua atau jalan berikutnya tidak mengirim ulang.
                    $claimed = Order::query()
                        ->whereKey($order->id)
                        ->whereNull('reminded_at')
                        ->update(['reminded_at' => now()]);

                    if ($claimed === 0) {
                        continue;
                    }

                    $order->store->user?->notify(new OrderReminder($order));
                    $sent++;
                }
            });

        $this->info("Pengingat terkirim: {$sent}.");

        return self::SUCCESS;
    }
}
