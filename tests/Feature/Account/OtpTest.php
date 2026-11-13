<?php

namespace Tests\Feature\Account;

use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    private function requestCode(User $user): TestResponse
    {
        RateLimiter::clear('otp-send:'.$user->id);

        return $this->actingAs($user)->post(route('account.otp.send'));
    }

    private function lastCode(User $user): string
    {
        $code = null;

        Notification::assertSentTo(
            $user,
            EmailOtpNotification::class,
            function (EmailOtpNotification $notification) use ($user, &$code): bool {
                preg_match(
                    '/(\d{6})/',
                    implode(' ', $notification->toMail($user)->introLines),
                    $matches,
                );

                $code = $matches[1] ?? null;

                return true;
            },
        );

        return (string) $code;
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->post(route('account.otp.send'))->assertRedirect(route('login'));
        $this->post(route('account.otp.verify'), ['code' => '123456'])
            ->assertRedirect(route('login'));
    }

    public function test_an_unverified_user_receives_a_six_digit_code()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->requestCode($user)->assertRedirect();

        $this->assertDatabaseCount('email_otps', 1);

        $otp = $user->emailOtps()->firstOrFail();

        $this->assertSame(0, $otp->attempts);
        $this->assertSame(6, strlen($this->lastCode($user)));
        $this->assertTrue(Hash::check($this->lastCode($user), $otp->code_hash));
    }

    public function test_a_second_code_is_rate_limited_within_a_minute()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->requestCode($user)->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('account.otp.send'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('email_otps', 1);
    }

    public function test_an_already_verified_user_can_not_request_a_code()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->requestCode($user)->assertSessionHasErrors('code');

        $this->assertDatabaseCount('email_otps', 0);
    }

    public function test_verifying_with_the_correct_code_marks_the_email_verified()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->requestCode($user);

        $this->actingAs($user)
            ->post(route('account.otp.verify'), [
                'code' => $this->lastCode($user),
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseCount('email_otps', 0);
    }

    public function test_a_wrong_code_is_counted_and_reports_the_remaining_attempts()
    {
        $user = User::factory()->unverified()->create();

        $user->emailOtps()->create([
            'code_hash' => Hash::make('111111'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->actingAs($user)
            ->post(route('account.otp.verify'), ['code' => '222222'])
            ->assertSessionHasErrors([
                'code' => 'Kode salah. Kamu punya 4 percobaan lagi.',
            ]);

        $this->assertSame(1, $user->emailOtps()->firstOrFail()->attempts);
    }

    public function test_the_code_stops_working_after_five_failed_attempts()
    {
        $user = User::factory()->unverified()->create();

        $user->emailOtps()->create([
            'code_hash' => Hash::make('111111'),
            'attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->actingAs($user)
            ->post(route('account.otp.verify'), ['code' => '111111'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_an_expired_code_is_rejected_and_deleted()
    {
        $user = User::factory()->unverified()->create();

        $user->emailOtps()->create([
            'code_hash' => Hash::make('111111'),
            'attempts' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->post(route('account.otp.verify'), ['code' => '111111'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseCount('email_otps', 0);
    }

    public function test_the_code_must_be_six_digits()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('account.otp.verify'), ['code' => '123'])
            ->assertSessionHasErrors('code');
    }
}
