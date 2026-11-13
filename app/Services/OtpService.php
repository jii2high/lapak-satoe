<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /**
     * Send a fresh code and drop the previous ones (PRD BR-19).
     */
    public function send(User $user): void
    {
        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'code' => 'Emailmu sudah terverifikasi.',
            ]);
        }

        $length = (int) config('lapak.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        $user->emailOtps()->delete();

        $user->emailOtps()->create([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('lapak.otp.expires_minutes')),
        ]);

        $user->notify(new EmailOtpNotification($code));
    }

    /**
     * Verify a code, counting the attempt even when the code is wrong.
     */
    public function verify(User $user, string $code): void
    {
        $otp = $user->emailOtps()->latest('id')->first();

        if ($otp === null) {
            throw ValidationException::withMessages([
                'code' => 'Belum ada kode aktif. Minta kode baru.',
            ]);
        }

        if ($otp->expires_at->isPast()) {
            $otp->delete();

            throw ValidationException::withMessages([
                'code' => 'Kode sudah kedaluwarsa. Minta kode baru.',
            ]);
        }

        $maxAttempts = (int) config('lapak.otp.max_attempts');

        if ($otp->attempts >= $maxAttempts) {
            throw ValidationException::withMessages([
                'code' => 'Percobaan habis. Minta kode baru.',
            ]);
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            $remaining = max(0, $maxAttempts - $otp->attempts);

            throw ValidationException::withMessages([
                'code' => $remaining === 0
                    ? 'Kode salah. Percobaan habis, minta kode baru.'
                    : "Kode salah. Kamu punya {$remaining} percobaan lagi.",
            ]);
        }

        $user->email_verified_at = now();
        $user->save();

        $otp->delete();
    }
}
