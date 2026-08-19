<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\WhatsappOtpService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request, WhatsappOtpService $otp): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        // Same response whether or not the account exists — otherwise this
        // endpoint becomes an account-enumeration oracle (throttle:auth on
        // the route caps spam either way).
        if ($user) {
            $otp->generateAndSendPasswordReset($user);
        }

        return response()->json([
            'data' => ['message' => 'Jika akun ditemukan, kode reset kata sandi sudah dikirim via WhatsApp.'],
        ]);
    }

    public function reset(ResetPasswordRequest $request, WhatsappOtpService $otp): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();
        abort_unless($user, 422, 'Akun tidak ditemukan.');

        abort_unless(
            $otp->verifyPasswordResetCode($user, $request->validated('code')),
            422,
            'Kode reset salah atau sudah kedaluwarsa.',
        );

        $user->forceFill(['password' => $request->validated('password')])->save();

        // Force re-login everywhere — a password reset is exactly the
        // moment an old, possibly-compromised session should stop working.
        $user->tokens()->delete();

        return response()->json(['data' => ['message' => 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.']]);
    }
}
