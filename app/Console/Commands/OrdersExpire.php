<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrdersExpire extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Batalkan order unconfirmed dan pending yang lewat expires_at';

    public function handle(OrderService $orders): int
    {
        $expired = 0;

        Order::query()
            ->whereIn('status', [OrderStatus::Unconfirmed->value, OrderStatus::Pending->value])
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function (Collection $batch) use ($orders, &$expired): void {
                foreach ($batch as $order) {
                    try {
                        $orders->expire($order);
                        $expired++;
                    } catch (ValidationException) {
                        // Order sudah pindah status di jalur lain.
                    }
                }
            });

        $this->info("Order dibatalkan otomatis: {$expired}.");

        return self::SUCCESS;
    }
}
