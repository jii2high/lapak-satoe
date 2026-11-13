<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    /**
     * Order dibuat lebih dulu, lalu browser diarahkan ke wa.me (FR-CART-3).
     */
    public function store(Request $request, Store $store, CheckoutService $checkout): Response
    {
        try {
            $url = $checkout->checkout($request->user(), $store);
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->validator->errors()->first(),
            ]);

            throw $exception;
        }

        return Inertia::location($url);
    }
}
