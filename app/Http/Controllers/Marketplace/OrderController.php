<?php

namespace App\Http\Controllers\Marketplace;

use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\RunsOrderAction;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\OrderPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use RunsOrderAction;

    /**
     * @var array<int, OrderStatus>
     */
    private const ACTIVE = [
        OrderStatus::Unconfirmed,
        OrderStatus::Pending,
        OrderStatus::AwaitingBuyer,
        OrderStatus::Disputed,
    ];

    /**
     * @var array<int, OrderStatus>
     */
    private const HISTORY = [
        OrderStatus::Completed,
        OrderStatus::Rejected,
        OrderStatus::Cancelled,
    ];

    public function index(Request $request): Response
    {
        $tab = $request->query('tab') === 'riwayat' ? 'riwayat' : 'aktif';

        $orders = $request->user()
            ->orders()
            ->with(['store', 'buyer', 'items'])
            ->whereIn('status', $tab === 'riwayat' ? self::HISTORY : self::ACTIVE)
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => OrderPresenter::row($order));

        return Inertia::render('marketplace/orders', [
            'orders' => $orders,
            'tab' => $tab,
        ]);
    }

    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        Gate::authorize('cancelAsBuyer', $order);

        return $this->runOrderAction(
            fn () => $orders->cancelByBuyer($order),
            'Pesanan dibatalkan.',
        );
    }

    public function confirm(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        Gate::authorize('confirmAsBuyer', $order);

        return $this->runOrderAction(
            fn () => $orders->confirm($order),
            'Pesanan selesai. Terima kasih.',
        );
    }
}
