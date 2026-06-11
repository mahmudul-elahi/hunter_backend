<?php

use App\Models\OtpCode;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->seed(RoleSeeder::class);
});

function latestOtpFor(string $email, string $type): ?OtpCode
{
    return OtpCode::where('email', $email)->where('type', $type)->latest('id')->first();
}

function registerJane(): void
{
    test()->postJson('/api/auth/register', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertCreated();
}

test('registration otp verifies the email and is removed afterwards', function () {
    registerJane();

    $otp = latestOtpFor('jane@example.com', 'email_verification');

    $this->postJson('/api/auth/verify-email-otp', [
        'email' => 'jane@example.com',
        'code' => $otp->code,
    ])->assertSuccessful();

    expect(User::where('email', 'jane@example.com')->first()->email_verified_at)->not->toBeNull();
    expect(OtpCode::where('email', 'jane@example.com')->where('type', 'email_verification')->count())->toBe(0);
});

test('after a resend only the latest verification otp works', function () {
    registerJane();

    $firstOtp = latestOtpFor('jane@example.com', 'email_verification')->code;

    $this->postJson('/api/auth/resend-verification-otp', [
        'email' => 'jane@example.com',
    ])->assertSuccessful();

    $latestOtp = latestOtpFor('jane@example.com', 'email_verification')->code;

    if ($firstOtp !== $latestOtp) {
        $this->postJson('/api/auth/verify-email-otp', [
            'email' => 'jane@example.com',
            'code' => $firstOtp,
        ])->assertStatus(422);
    }

    $this->postJson('/api/auth/verify-email-otp', [
        'email' => 'jane@example.com',
        'code' => $latestOtp,
    ])->assertSuccessful();

    expect(User::where('email', 'jane@example.com')->first()->email_verified_at)->not->toBeNull();
    expect(OtpCode::where('email', 'jane@example.com')->where('type', 'email_verification')->count())->toBe(0);
});

test('an expired verification otp is rejected and a resend issues a working code', function () {
    registerJane();

    $firstOtp = latestOtpFor('jane@example.com', 'email_verification')->code;

    $this->travel(11)->minutes();

    $this->postJson('/api/auth/verify-email-otp', [
        'email' => 'jane@example.com',
        'code' => $firstOtp,
    ])->assertStatus(422);

    $this->postJson('/api/auth/resend-verification-otp', [
        'email' => 'jane@example.com',
    ])->assertSuccessful();

    $latestOtp = latestOtpFor('jane@example.com', 'email_verification')->code;

    $this->postJson('/api/auth/verify-email-otp', [
        'email' => 'jane@example.com',
        'code' => $latestOtp,
    ])->assertSuccessful();
});

test('password reset uses the latest otp and clears all reset otps afterwards', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $user->assignRole('user');

    $this->postJson('/api/auth/forgot-password', ['email' => 'jane@example.com'])->assertSuccessful();

    $firstOtp = latestOtpFor('jane@example.com', 'password_reset')->code;

    $this->postJson('/api/auth/forgot-password', ['email' => 'jane@example.com'])->assertSuccessful();

    $latestOtp = latestOtpFor('jane@example.com', 'password_reset')->code;

    if ($firstOtp !== $latestOtp) {
        $this->postJson('/api/auth/verify-otp', [
            'email' => 'jane@example.com',
            'code' => $firstOtp,
        ])->assertStatus(422);
    }

    $this->postJson('/api/auth/verify-otp', [
        'email' => 'jane@example.com',
        'code' => $latestOtp,
    ])->assertSuccessful();

    $this->postJson('/api/auth/reset-password', [
        'email' => 'jane@example.com',
        'code' => $latestOtp,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertSuccessful();

    expect(OtpCode::where('email', 'jane@example.com')->where('type', 'password_reset')->count())->toBe(0);
});

test('forgot password does not issue an otp for an unknown email', function () {
    $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonPath('errors.email.0', 'Email does not exist.');

    expect(latestOtpFor('missing@example.com', 'password_reset'))->toBeNull();
    Notification::assertNothingSent();
});

test('verification otps do not interfere with password reset otps', function () {
    registerJane();

    $verificationOtp = latestOtpFor('jane@example.com', 'email_verification')->code;

    $this->postJson('/api/auth/forgot-password', ['email' => 'jane@example.com'])->assertSuccessful();

    $this->postJson('/api/auth/verify-email-otp', [
        'email' => 'jane@example.com',
        'code' => $verificationOtp,
    ])->assertSuccessful();
});
