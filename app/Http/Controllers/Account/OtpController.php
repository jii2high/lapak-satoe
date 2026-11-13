<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\VerifyOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function send(Request $request): RedirectResponse
    {
        $key = 'otp-send:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors([
                'code' => 'Tunggu '.RateLimiter::availableIn($key).' detik sebelum minta kode baru.',
            ]);
        }

        $this->otp->send($request->user());
        RateLimiter::hit($key, (int) config('lapak.otp.resend_seconds'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kode dikirim ke emailmu.',
        ]);

        return back();
    }

    public function verify(VerifyOtpRequest $request): RedirectResponse
    {
        $this->otp->verify($request->user(), $request->validated('code'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Email terverifikasi.',
        ]);

        return back();
    }
}