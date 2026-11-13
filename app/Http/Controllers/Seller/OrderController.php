<?php

namespace App\Http\Controllers\Seller;

use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\RunsOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\CancelOrderRequest;
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
        $store = $request->user()->store()->firstOrFail();

        $orders = $store->orders()
            ->with(['store', 'buyer', 'items'])
            ->whereIn('status', $tab === 'riwayat' ? self::HISTORY : self::ACTIVE)
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => OrderPresenter::row($order));

        return Inertia::render('seller/orders', [
            'orders' => $orders,
            'tab' => $tab,
        ]);
    }

    public function accept(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        Gate::authorize('actAsSeller', $order);

        return $this->runOrderAction(
            fn () => $orders->accept($order),
            'Pesanan diterima. Stok sudah dikurangi.',
        );
    }

    public function reject(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        Gate::authorize('actAsSeller', $order);

        return $this->runOrderAction(
            fn () => $orders->reject($order),
            'Pesanan ditolak.',
        );
    }

    public function cancel(CancelOrderRequest $request, Order $order, OrderService $orders): RedirectResponse
    {
        Gate::authorize('actAsSeller', $order);

        return $this->runOrderAction(
            fn () => $orders->cancelBySeller($order, (string) $request->string('note')),
            'Pesanan dibatalkan. Stok sudah dikembalikan.',
        );
    }

    public function complete(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        Gate::authorize('actAsSeller', $order);

        return $this->runOrderAction(
            fn () => $orders->complete($order),
            'Pesanan ditandai selesai. Menunggu konfirmasi buyer.',
        );
    }
}
