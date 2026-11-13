<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function index(Request $request): Response
    {
        return Inertia::render('marketplace/cart', [
            'groups' => $this->carts->groups($this->carts->forUser($request->user())),
        ]);
    }

    /**
     * Keranjang guest (localStorage) digabung ke keranjang server (FR-CART-1).
     */
    public function merge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['present', 'array', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $this->carts->merge($request->user(), $data['items']);

        return back();
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $this->guardOwnership($request, $item);

        $qty = $request->validate(['qty' => ['required', 'integer', 'min:1']])['qty'];

        try {
            $this->carts->setQty($item, (int) $qty);
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->validator->errors()->first(),
            ]);

            throw $exception;
        }

        return back();
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        $this->guardOwnership($request, $item);

        $this->carts->remove($item);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item dihapus dari keranjang.']);

        return back();
    }

    private function guardOwnership(Request $request, CartItem $item): void
    {
        abort_unless($item->cart->user_id === $request->user()->id, 404);
    }
}
