<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $notificationId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $notification = Notification::query()->with('user')->findOrFail($this->notificationId);

        $response = match ($notification->channel) {
            Notification::CHANNEL_EMAIL => $this->sendEmail($notification),
            Notification::CHANNEL_WHATSAPP => $this->sendWhatsapp($notification),
            // Telegram requires a bot token that isn't configured in this
            // environment yet — logged as a stub so the retry/log pipeline is still
            // exercised end-to-end and ready for a real provider to be plugged in.
            default => $this->sendStub($notification),
        };

        $notification->update(['status' => Notification::STATUS_SENT]);

        NotificationLog::query()->create([
            'notification_id' => $notification->id,
            'status' => Notification::STATUS_SENT,
            'retry_count' => $this->attempts() - 1,
            'provider_response' => $response,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $notification = Notification::query()->find($this->notificationId);
        if (! $notification) {
            return;
        }

        $notification->update(['status' => Notification::STATUS_FAILED]);

        NotificationLog::query()->create([
            'notification_id' => $notification->id,
            'status' => Notification::STATUS_FAILED,
            'retry_count' => $this->attempts(),
            'provider_response' => ['error' => $exception?->getMessage()],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sendEmail(Notification $notification): array
    {
        Mail::raw($notification->body, function ($message) use ($notification) {
            $message->to($notification->user->email)->subject($notification->title);
        });

        return ['channel' => 'email', 'to' => $notification->user->email];
    }

    /**
     * Sends through a self-hosted OpenWA gateway (POST
     * /api/sessions/{id}/messages/send-text) when WHATSAPP_PROVIDER=openwa;
     * otherwise falls back to the stub so registration/OTP flows keep
     * working end-to-end (as a logged no-op) before a gateway is wired up.
     *
     * @return array<string, mixed>
     */
    private function sendWhatsapp(Notification $notification): array
    {
        if (config('services.whatsapp.provider') !== 'openwa') {
            return $this->sendStub($notification);
        }

        $chatId = User::toWhatsappChatId($notification->user->phone);

        if (! $chatId) {
            throw new RuntimeException("Nomor WhatsApp tidak valid untuk user #{$notification->user_id}.");
        }

        $response = Http::baseUrl(rtrim((string) config('services.openwa.base_url'), '/'))
            ->withToken((string) config('services.openwa.api_key'))
            ->timeout((int) config('services.openwa.timeout', 15))
            ->post('/api/sessions/'.config('services.openwa.session_id').'/messages/send-text', [
                'chatId' => $chatId,
                'text' => $notification->body,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("OpenWA HTTP {$response->status()}: ".($response->json('message') ?? $response->body()));
        }

        return ['channel' => 'whatsapp', 'to' => $chatId, 'provider_response' => $response->json()];
    }

    /**
     * @return array<string, mixed>
     */
    private function sendStub(Notification $notification): array
    {
        return [
            'channel' => $notification->channel,
            'note' => 'Provider belum dikonfigurasi — payload dicatat untuk integrasi mendatang.',
        ];
    }
}
