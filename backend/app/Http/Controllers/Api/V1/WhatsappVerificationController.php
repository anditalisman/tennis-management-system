<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendWhatsappVerificationRequest;
use App\Http\Requests\Auth\VerifyWhatsappRequest;
use App\Models\User;
use App\Services\WhatsappOtpService;
use Illuminate\Http\JsonResponse;

class WhatsappVerificationController extends Controller
{
    public function verify(VerifyWhatsappRequest $request, WhatsappOtpService $otp): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();
        abort_unless($user, 422, 'Akun tidak ditemukan.');

        if ($user->hasVerifiedWhatsapp()) {
            return response()->json(['data' => ['message' => 'Akun sudah terverifikasi. Silakan masuk.']]);
        }

        abort_unless($otp->verify($user, $request->validated('code')), 422, 'Kode verifikasi salah atau sudah kedaluwarsa.');

        return response()->json(['data' => ['message' => 'Nomor WhatsApp berhasil diverifikasi. Silakan masuk ke portal.']]);
    }

    public function resend(ResendWhatsappVerificationRequest $request, WhatsappOtpService $otp): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        // Same response whether or not the account exists / is already
        // verified — otherwise this endpoint becomes an account-enumeration
        // oracle (throttle:auth on the route caps resend spam either way).
        if ($user && ! $user->hasVerifiedWhatsapp()) {
            $otp->generateAndSend($user);
        }

        return response()->json([
            'data' => ['message' => 'Jika akun ditemukan dan belum terverifikasi, kode baru sudah dikirim via WhatsApp.'],
        ]);
    }
}
