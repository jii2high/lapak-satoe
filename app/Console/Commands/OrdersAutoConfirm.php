<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrdersAutoConfirm extends Command
{
    protected $signature = 'orders:auto-confirm';

    protected $description = 'Selesaikan order awaiting_buyer yang lewat auto_confirm_at';

    public function handle(OrderService $orders): int
    {
        $confirmed = 0;

        Order::query()
            ->where('status', OrderStatus::AwaitingBuyer->value)
            ->whereNotNull('auto_confirm_at')
            ->where('auto_confirm_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function (Collection $batch) use ($orders, &$confirmed): void {
                foreach ($batch as $order) {
                    try {
                        $orders->confirm($order);
                        $confirmed++;
                    } catch (ValidationException) {
                        // Buyer sudah mengonfirmasi duluan.
                    }
                }
            });

        $this->info("Order selesai otomatis: {$confirmed}.");

        return self::SUCCESS;
    }
}
