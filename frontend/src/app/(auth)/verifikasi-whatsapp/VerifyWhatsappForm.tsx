"use client";

import { useActionState } from "react";
import Link from "next/link";
import { verifyWhatsappAction, resendWhatsappVerificationAction } from "@/lib/actions/auth";
import { Input } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

export function VerifyWhatsappForm({ email }: { email: string }) {
  const [state, formAction, pending] = useActionState(verifyWhatsappAction, undefined);
  const [resendState, resendFormAction, resendPending] = useActionState(resendWhatsappVerificationAction, undefined);

  if (state?.success) {
    return (
      <div className="text-center">
        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-(--color-good)/12 text-(--color-good)">
          ✓
        </div>
        <h1 className="mt-4 font-display text-2xl font-bold text-(--color-ink-900)">Nomor WhatsApp Terverifikasi</h1>
        <p className="mt-3 text-sm text-(--color-ink-500)">Akun Anda sudah aktif. Silakan masuk ke portal.</p>
        <Link href="/login" className="mt-6 inline-block">
          <Button>Masuk ke Portal</Button>
        </Link>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4">
      <h1 className="text-center font-display text-2xl font-bold text-(--color-ink-900)">Verifikasi WhatsApp</h1>
      <p className="text-center text-sm text-(--color-ink-500)">
        Kami mengirim kode verifikasi 6 digit ke nomor WhatsApp Anda. Masukkan kode tersebut untuk mengaktifkan akun.
      </p>

      <form action={formAction} className="flex flex-col gap-4">
        <input type="hidden" name="email" value={email} />
        {state?.error && <Alert tone="crit">{state.error}</Alert>}
        <Input
          label="Kode Verifikasi"
          name="code"
          inputMode="numeric"
          pattern="[0-9]{6}"
          maxLength={6}
          autoComplete="one-time-code"
          required
          className="text-center text-lg tracking-[0.5em]"
        />
        <Button type="submit" loading={pending} className="w-full">
          Verifikasi
        </Button>
      </form>

      <div className="rounded-lg border border-(--color-ink-900)/10 p-3">
        {resendState?.message ? (
          <p className="text-sm text-(--color-good)">{resendState.message}</p>
        ) : (
          <form action={resendFormAction} className="flex items-center justify-between gap-2">
            <input type="hidden" name="email" value={email} />
            <p className="text-sm text-(--color-ink-500)">Tidak menerima kode?</p>
            <Button type="submit" variant="outline" size="sm" loading={resendPending}>
              Kirim ulang
            </Button>
          </form>
        )}
        {resendState?.error && <p className="mt-2 text-sm text-(--color-crit)">{resendState.error}</p>}
      </div>
    </div>
  );
}
