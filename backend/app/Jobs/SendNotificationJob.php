<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
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
            // WhatsApp/Telegram require a BSP/bot token that isn't configured in this
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
