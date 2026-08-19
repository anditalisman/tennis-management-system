<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class WhatsappOtpService
{
    private const TTL_MINUTES = 5;

    public function generateAndSend(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        Cache::put($this->cacheKey($user), $code, now()->addMinutes(self::TTL_MINUTES));

        Notification::queue(
            $user,
            Notification::CHANNEL_WHATSAPP,
            'Kode Verifikasi — Zul Tennis Clinic',
            "Kode verifikasi Anda: {$code}\n\n".
            'Berlaku '.self::TTL_MINUTES." menit. Jangan bagikan kode ini kepada siapa pun. Anda tidak bisa masuk ke portal sebelum nomor WhatsApp ini diverifikasi.",
        );
    }

    public function verify(User $user, string $code): bool
    {
        $expected = Cache::get($this->cacheKey($user));

        if (! $expected || ! hash_equals($expected, $code)) {
            return false;
        }

        Cache::forget($this->cacheKey($user));
        $user->markWhatsappAsVerified();

        return true;
    }

    private function cacheKey(User $user): string
    {
        return "whatsapp_otp:{$user->id}";
    }
}
