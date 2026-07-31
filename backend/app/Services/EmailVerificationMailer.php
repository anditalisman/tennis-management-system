<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class EmailVerificationMailer
{
    private const TTL_HOURS = 24;

    public function send(User $user): void
    {
        $expires = now()->addHours(self::TTL_HOURS)->timestamp;
        $signature = User::emailVerificationSignature($user->id, $expires);
        $link = rtrim((string) config('services.spa.url'), '/')."/verifikasi-email?id={$user->id}&expires={$expires}&signature={$signature}";

        Notification::queue(
            $user,
            Notification::CHANNEL_EMAIL,
            'Verifikasi email Anda — Zul Tennis Clinic',
            "Halo {$user->name},\n\n".
            "Klik link berikut untuk mengaktifkan akun Anda (berlaku 24 jam):\n{$link}\n\n".
            'Anda tidak bisa masuk ke portal sebelum email ini diverifikasi. Jika Anda tidak merasa mendaftar di Zul Tennis Clinic, abaikan email ini.',
        );
    }
}
