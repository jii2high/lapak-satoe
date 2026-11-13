<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * Block seller registration until the email is verified by OTP (PRD BR-16).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->email_verified_at === null) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Verifikasi email dulu sebelum mendaftar jadi penjual.',
            ]);

            return back();
        }

        return $next($request);
    }
}
