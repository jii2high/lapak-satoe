<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Support\EmailMask;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private readonly CartService $carts) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'display_name' => $user->display_name,
                    'username' => $user->username,
                    'email_masked' => EmailMask::mask($user->email),
                    'email_verified' => $user->email_verified_at !== null,
                    'active_role' => $user->active_role->value,
                    'wishlist_product_ids' => $user->wishlists()
                        ->whereNotNull('product_id')
                        ->pluck('product_id')
                        ->all(),
                    'wishlist_store_ids' => $user->wishlists()
                        ->whereNotNull('store_id')
                        ->pluck('store_id')
                        ->all(),
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'cart_count' => $user === null ? 0 : $this->carts->count($user),
            'violation_types' => config('lapak.violation_types'),
        ];
    }
}
