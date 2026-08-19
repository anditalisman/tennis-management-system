"use client";

import { useActionState, useState } from "react";
import Link from "next/link";
import { forgotPasswordAction, resetPasswordAction } from "@/lib/actions/auth";
import { Input } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

export function ForgotPasswordForm() {
  const [email, setEmail] = useState("");

  const [forgotState, forgotFormAction, forgotPending] = useActionState(forgotPasswordAction, undefined);
  const [resetState, resetFormAction, resetPending] = useActionState(resetPasswordAction, undefined);

  if (resetState?.success) {
    return (
      <div className="text-center">
        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-(--color-good)/12 text-(--color-good)">
          ✓
        </div>
        <h1 className="mt-4 font-display text-2xl font-bold text-(--color-ink-900)">Kata Sandi Berhasil Diubah</h1>
        <p className="mt-3 text-sm text-(--color-ink-500)">Silakan masuk dengan kata sandi baru Anda.</p>
        <Link href="/login" className="mt-6 inline-block">
          <Button>Masuk ke Portal</Button>
        </Link>
      </div>
    );
  }

  if (forgotState?.message) {
    return (
      <div className="flex flex-col gap-4">
        <h1 className="text-center font-display text-2xl font-bold text-(--color-ink-900)">Reset Kata Sandi</h1>
        <p className="text-center text-sm text-(--color-ink-500)">
          Kode reset sudah dikirim ke WhatsApp akun <span className="font-semibold">{email}</span>. Masukkan kode
          tersebut beserta kata sandi baru Anda.
        </p>

        <form
          action={(formData) => {
            formData.set("email", email);
            resetFormAction(formData);
          }}
          className="flex flex-col gap-4"
        >
          {resetState?.error && <Alert tone="crit">{resetState.error}</Alert>}
          <Input
            label="Kode Reset"
            name="code"
            inputMode="numeric"
            pattern="[0-9]{6}"
            maxLength={6}
            autoComplete="one-time-code"
            required
            error={resetState?.fieldErrors?.code}
            className="text-center text-lg tracking-[0.5em]"
          />
          <Input
            label="Kata Sandi Baru"
            name="password"
            type="password"
            autoComplete="new-password"
            required
            hint="Minimal 8 karakter, kombinasi huruf besar/kecil dan angka."
            error={resetState?.fieldErrors?.password}
          />
          <Input
            label="Konfirmasi Kata Sandi Baru"
            name="password_confirmation"
            type="password"
            autoComplete="new-password"
            required
          />
          <Button type="submit" loading={resetPending} className="w-full">
            Ubah Kata Sandi
          </Button>
        </form>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4">
      <h1 className="text-center font-display text-2xl font-bold text-(--color-ink-900)">Lupa Kata Sandi?</h1>
      <p className="text-center text-sm text-(--color-ink-500)">
        Masukkan email akun Anda — kami akan mengirim kode reset ke WhatsApp yang terdaftar.
      </p>

      <form
        action={(formData) => {
          setEmail(String(formData.get("email") ?? "").trim());
          forgotFormAction(formData);
        }}
        className="flex flex-col gap-4"
      >
        {forgotState?.error && <Alert tone="crit">{forgotState.error}</Alert>}
        <Input label="Email" name="email" type="email" autoComplete="email" required />
        <Button type="submit" loading={forgotPending} className="w-full">
          Kirim Kode Reset
        </Button>
      </form>

      <p className="text-center text-sm text-(--color-ink-500)">
        Sudah ingat kata sandi?{" "}
        <Link href="/login" className="font-semibold text-(--color-court-600) hover:underline">
          Masuk
        </Link>
      </p>
    </div>
  );
}
