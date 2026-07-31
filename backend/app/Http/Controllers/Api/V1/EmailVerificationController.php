<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use App\Services\EmailVerificationMailer;
use Illuminate\Http\JsonResponse;

class EmailVerificationController extends Controller
{
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $id = $request->validated('id');
        $expires = $request->validated('expires');
        $signature = $request->validated('signature');

        $expected = User::emailVerificationSignature($id, $expires);
        abort_unless(hash_equals($expected, $signature), 403, 'Link verifikasi tidak valid.');
        abort_if(now()->timestamp > $expires, 403, 'Link verifikasi sudah kedaluwarsa. Minta link verifikasi baru.');

        $user = User::query()->findOrFail($id);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json(['data' => ['message' => 'Email berhasil diverifikasi. Silakan masuk ke portal.']]);
    }

    public function resend(ResendVerificationRequest $request, EmailVerificationMailer $mailer): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        // Same response whether or not the email exists / is already
        // verified — otherwise this endpoint becomes an account-enumeration
        // oracle.
        if ($user && ! $user->hasVerifiedEmail()) {
            $mailer->send($user);
        }

        return response()->json([
            'data' => ['message' => 'Jika email terdaftar dan belum diverifikasi, tautan verifikasi baru sudah dikirim.'],
        ]);
    }
}
