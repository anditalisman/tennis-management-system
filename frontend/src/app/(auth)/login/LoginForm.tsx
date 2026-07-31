"use client";

import { useActionState } from "react";
import Link from "next/link";
import { loginAction, resendVerificationAction } from "@/lib/actions/auth";
import { Input } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

export function LoginForm({ next }: { next?: string }) {
  const [state, formAction, pending] = useActionState(loginAction, undefined);
  const [resendState, resendFormAction, resendPending] = useActionState(resendVerificationAction, undefined);
  const isUnverified = state?.error?.includes("belum diverifikasi") ?? false;

  return (
    <div className="flex flex-col gap-4">
      <form action={formAction} className="flex flex-col gap-4">
        <input type="hidden" name="next" value={next ?? ""} />
        {state?.error && <Alert tone="crit">{state.error}</Alert>}
        <Input label="Email" name="email" type="email" autoComplete="email" required error={state?.fieldErrors?.email} />
        <Input
          label="Kata sandi"
          name="password"
          type="password"
          autoComplete="current-password"
          required
          error={state?.fieldErrors?.password}
        />
        <Button type="submit" loading={pending} className="mt-2 w-full">
          Masuk
        </Button>
      </form>

      {isUnverified && (
        <div className="rounded-lg border border-(--color-ink-900)/10 p-3">
          {resendState?.message ? (
            <p className="text-sm text-(--color-good)">{resendState.message}</p>
          ) : (
            <>
              <p className="text-sm text-(--color-ink-500)">Tidak menerima email verifikasi?</p>
              <form action={resendFormAction} className="mt-2 flex flex-wrap items-end gap-2">
                <div className="min-w-0 flex-1">
                  <Input label="Email" name="email" type="email" required />
                </div>
                <Button type="submit" variant="outline" size="sm" loading={resendPending}>
                  Kirim ulang
                </Button>
              </form>
              {resendState?.error && <p className="mt-2 text-sm text-(--color-crit)">{resendState.error}</p>}
            </>
          )}
        </div>
      )}

      <p className="text-center text-sm text-(--color-ink-500)">
        Belum punya akun?{" "}
        <Link href="/pendaftaran" className="font-semibold text-(--color-court-600) hover:underline">
          Daftar sekarang
        </Link>
      </p>
    </div>
  );
}
